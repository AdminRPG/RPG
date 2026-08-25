<?php
/**
 * One Piece: 7 Seas · Restaurar la BD al estado limpio de referencia
 * -----------------------------------------------------------------
 * Recrea la base de datos (DROP + CREATE), importa el dump limpio
 * (backups/rpg_forum_limpio_*.sql) y limpia los caches de MyBB.
 *
 * Uso:
 *   php scripts/restore-backup.php                     # últ. dump en backups/
 *   php scripts/restore-backup.php --dump=ruta.sql     # dump concreto
 *   php scripts/restore-backup.php --db=nombre_bd      # BD destino (default: inc/config.php)
 *   php scripts/restore-backup.php --yes               # sin confirmación
 *
 * Requisitos:
 *   - Cliente mysql accesible (env MYSQL_BIN o en PATH; se buscan rutas
 *     típicas de Laragon/XAMPP si no).
 *   - inc/config.php con las credenciales de la BD destino.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ── Argumentos ──
$opts = getopt('', ['dump::', 'db::', 'yes']);
$dumpArg = $opts['dump'] ?? null;
$dbName  = $opts['db'] ?? null;
$autoYes = isset($opts['yes']);

// ── Credenciales desde inc/config.php ──
$config = [];
require dirname(__DIR__) . '/inc/config.php';
$dbCfg = $config['database'] ?? null;
if (!$dbCfg || empty($dbCfg['database'])) {
    fwrite(STDERR, "Error: no se pudo leer inc/config.php (credenciales de BD).\n");
    exit(1);
}
$targetDb  = $dbName ?: $dbCfg['database'];
$prefix    = $dbCfg['table_prefix'] ?? 'mybb_';
$host      = $dbCfg['hostname'] ?? 'localhost';
$user      = $dbCfg['username'] ?? 'root';
$pass      = $dbCfg['password'] ?? '';

// ── Localizar el dump ──
$backupsDir = dirname(__DIR__) . '/backups';
if ($dumpArg) {
    $dumpFile = $dumpArg;
    if (!is_file($dumpFile)) {
        $dumpFile = $backupsDir . '/' . $dumpArg;
    }
} else {
    $candidates = glob($backupsDir . '/rpg_forum_limpio_*.sql') ?: [];
    usort($candidates, fn($a, $b) => filemtime($b) <=> filemtime($a));
    $dumpFile = $candidates[0] ?? null;
}
if (!$dumpFile || !is_file($dumpFile)) {
    fwrite(STDERR, "Error: no hay dump en backups/ (backups/rpg_forum_limpio_*.sql).\n");
    fwrite(STDERR, "Pasa uno con --dump=ruta.sql o genera el dump con mysqldump (ver docs/RESTAURAR-BACKUP.md).\n");
    exit(1);
}

// ── Localizar el cliente mysql ──
function find_mysql(): ?string
{
    $env = getenv('MYSQL_BIN');
    if ($env && is_file($env)) {
        return $env;
    }
    $out = [];
    exec('where mysql 2>nul', $out, $code);
    if ($code === 0 && !empty($out[0]) && is_file(trim($out[0]))) {
        return trim($out[0]);
    }
    // Rutas típicas: Laragon, XAMPP
    $globs = [
        'C:/laragon/bin/mysql/*/bin/mysql.exe',
        'C:/laragon/bin/mariadb/*/bin/mysql.exe',
        'C:/xampp/mysql/bin/mysql.exe',
        'C:/wamp64/bin/mysql/*/bin/mysql.exe',
    ];
    foreach ($globs as $g) {
        $found = glob($g) ?: [];
        if ($found) {
            return $found[0];
        }
    }
    return null;
}
$mysql = find_mysql();
if (!$mysql) {
    fwrite(STDERR, "Error: no encuentro el cliente mysql. Defínelo con la env var MYSQL_BIN.\n");
    exit(1);
}

// ── Resumen y confirmación ──
$dumpSize = round(filesize($dumpFile) / 1048576, 1);
echo "=== Restaurar BD de One Piece: 7 Seas ===\n";
echo "  BD destino : {$targetDb}\n";
echo "  Dump       : {$dumpFile} ({$dumpSize} MB)\n";
echo "  Cliente    : {$mysql}\n";
echo "  Nota       : se BORRARÁ la BD {$targetDb} y se recreará vacía.\n\n";
if (!$autoYes) {
    echo "¿Continuar? (yes/no): ";
    $line = trim(fgets(STDIN));
    if (strtolower($line) !== 'yes') {
        echo "Abortado.\n";
        exit(0);
    }
}

// ── 1. DROP + CREATE (sin BD seleccionada) ──
$admin = new mysqli($host, $user, $pass);
if ($admin->connect_error) {
    fwrite(STDERR, "Error de conexión MySQL: " . $admin->connect_error . "\n");
    exit(1);
}
$admin->set_charset('utf8mb4');
echo "[1/3] Recreando BD {$targetDb} ... ";
$sql = "DROP DATABASE IF EXISTS `{$targetDb}`; CREATE DATABASE `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
if ($admin->multi_query($sql)) {
    while ($admin->more_results()) {
        $admin->next_result();
    }
    echo "OK\n";
} else {
    fwrite(STDERR, "ERROR: " . $admin->error . "\n");
    exit(1);
}
$admin->close();

// ── 2. Importar el dump ──
echo "[2/3] Importando dump ... ";
$cmd = [$mysql, '--default-character-set=utf8mb4', '--host=127.0.0.1', '-u', $user, $targetDb];
if ($pass !== '') {
    $cmd[] = '-p' . $pass;
}
$descriptors = [
    0 => ['file', $dumpFile, 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$proc = proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    fwrite(STDERR, "ERROR: no se pudo lanzar el cliente mysql.\n");
    exit(1);
}
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$code = proc_close($proc);
if ($code !== 0) {
    fwrite(STDERR, "ERROR (exit {$code})\n" . $stderr . "\n");
    exit(1);
}
echo "OK\n";

// ── 3. Limpiar caches de MyBB ──
$db = new mysqli($host, $user, $pass, $targetDb);
if ($db->connect_error) {
    fwrite(STDERR, "Error de conexión a {$targetDb}: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');
$titles = ['templates', 'themes', 'default_theme', 'forums', 'stats', 'settings', 'plugins'];
$in = "'" . implode("','", array_map(fn($t) => $db->real_escape_string($t), $titles)) . "'";
$db->query("DELETE FROM `{$prefix}datacache` WHERE title IN ({$in})");
$rows = $db->affected_rows;
echo "[3/3] Caches de MyBB limpiados ({$rows} entradas de datacache).\n";
$db->close();

// ── Verificación rápida ──
$db = new mysqli($host, $user, $pass, $targetDb);
$t = $db->query("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = '{$targetDb}'");
$tables = $t ? (int)$t->fetch_assoc()['n'] : 0;
$db->close();

echo "\n=== DONE ===\n";
echo "  {$tables} tablas restauradas en {$targetDb}.\n\n";
echo "Siguientes pasos (si el tema se quedó atrás respecto al repo):\n";
echo "  php scripts/sync-theme.php import\n";
echo "  php scripts/sync-theme.php verify\n";
echo "Recarga la portada con Ctrl+Shift+R.\n";
