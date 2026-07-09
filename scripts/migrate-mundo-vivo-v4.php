<?php
/**
 * Migracion v4: automatizacion total de Mundo Vivo (Fases 0-3).
 *  - Tabla rol_mv_audit: rastro de cada publicacion (topes aplicados, misiones
 *    resueltas/creadas, quien publico).
 *  - rol_mv_misiones.notas_resolucion: por que la IA marco una mision como
 *    completada/fallida (para que quede constancia sin ensuciar el resumen original).
 *
 * Idempotente: se puede re-ejecutar sin duplicar nada.
 * Ejecutar: php scripts/migrate-mundo-vivo-v4.php
 */

define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

$PREFIX = TABLE_PREFIX;

echo "== v4: tabla de auditoria ==\n";
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_audit (
        audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ciclo_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        uid_publicador INT UNSIGNED NOT NULL DEFAULT 0,
        caps_aplicados_json LONGTEXT NULL COMMENT 'lista de topes anti-escalada recortados en esta publicacion',
        caps_aplicados_n SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        misiones_resueltas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        misiones_creadas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (audit_id),
        KEY idx_ciclo (ciclo_id),
        KEY idx_dateline (dateline)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  [+] rol_mv_audit lista\n";

echo "== v4: columna de resolucion de misiones ==\n";
if (!$db->field_exists('notas_resolucion', 'rol_mv_misiones')) {
    $db->write_query("ALTER TABLE {$PREFIX}rol_mv_misiones ADD COLUMN notas_resolucion MEDIUMTEXT NULL COMMENT 'por que la IA resolvio esta mision asi' AFTER estado");
    echo "  [+] rol_mv_misiones.notas_resolucion\n";
} else {
    echo "  [=] rol_mv_misiones.notas_resolucion ya existe\n";
}

echo "\nMigracion v4 completada.\n";
