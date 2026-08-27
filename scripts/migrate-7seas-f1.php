<?php
/**
 * One Piece: 7 Seas · Migración F1 — puntero de personaje activo (decisión D1.1)
 * ----------------------------------------------------------------------------------
 * Añade `personaje_tabla` a `mybb_rol_cuentas` para que el puntero de personaje
 * activo (personaje_activo) deje de ser ambiguo entre las tablas de la era anterior
 * (mybb_rol_personajes, `rol`) y el esquema nuevo (mybb_ope_personajes, `ope`).
 *
 * - Idempotente: solo añade la columna si no existe.
 * - No toca datos existentes (personaje_tabla por defecto 'rol').
 *
 * Ejecutar:
 *   php scripts/migrate-7seas-f1.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

echo "=== Migración F1 — puntero de personaje activo ===\n";

$tabla = 'mybb_rol_cuentas';
$q = $db->query("SHOW COLUMNS FROM `$tabla` LIKE 'personaje_tabla'");
if ($q && $q->num_rows > 0) {
    echo "  [OK] $tabla.personaje_tabla ya existe (nada que hacer).\n";
} else {
    $ok = $db->query("ALTER TABLE `$tabla` ADD COLUMN `personaje_tabla` ENUM('rol','ope') NOT NULL DEFAULT 'rol' AFTER `personaje_activo`");
    if ($ok === false) {
        fwrite(STDERR, "  [ERROR] ALTER $tabla.personaje_tabla: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] $tabla.personaje_tabla creado (ENUM('rol','ope') DEFAULT 'rol').\n";
}

echo "=== DONE ===\n";
