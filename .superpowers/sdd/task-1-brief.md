### Task 1: Crear tabla `rol_mensajes` y `rol_alertas`

**Files:**
- Create: `scripts/migrate-mensajes-alertas.php`

**Interfaces:**
- Produces: Tablas `mybb_rol_mensajes` y `mybb_rol_alertas` en la BD MyBB

- [ ] **Step 1: Crear script de migración**

```php
<?php
/**
 * Migración: tablas de mensajes directos y alertas.
 * Ejecutar una vez: php scripts/migrate-mensajes-alertas.php
 */

define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

$PREFIX = TABLE_PREFIX;

// ── Tabla de mensajes directos (hilos de conversación) ──
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mensajes (
        mid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        thread_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'hilo de conversación',
        origen_pid INT UNSIGNED NOT NULL COMMENT 'pid del personaje que envía',
        destino_pid INT UNSIGNED NOT NULL COMMENT 'pid del personaje que recibe',
        asunto VARCHAR(200) NOT NULL DEFAULT '',
        cuerpo TEXT NOT NULL,
        leido TINYINT(1) NOT NULL DEFAULT 0,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (mid),
        KEY idx_thread (thread_id),
        KEY idx_destino_leido (destino_pid, leido),
        KEY idx_origen (origen_pid),
        KEY idx_dateline (dateline)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Tabla de alertas ──
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_alertas (
        aid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        pid INT UNSIGNED NOT NULL COMMENT 'personaje destino de la alerta',
        uid INT UNSIGNED NOT NULL COMMENT 'MyBB user dueño del personaje',
        tipo ENUM('mensaje_nuevo','personaje_aprobado','personaje_rechazado','personaje_moderado','staff_asignado') NOT NULL DEFAULT 'mensaje_nuevo',
        titulo VARCHAR(200) NOT NULL DEFAULT '',
        cuerpo TEXT NOT NULL,
        link VARCHAR(300) NOT NULL DEFAULT '',
        leido TINYINT(1) NOT NULL DEFAULT 0,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (aid),
        KEY idx_pid_leido (pid, leido),
        KEY idx_uid_leido (uid, leido),
        KEY idx_dateline (dateline)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "Migracion completada: tablas rol_mensajes y rol_alertas creadas.\n";
```

- [ ] **Step 2: Ejecutar migración**

```bash
cd C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG
php scripts/migrate-mensajes-alertas.php
```

Expected: "Migracion completada: tablas rol_mensajes y rol_alertas creadas."

- [ ] **Step 3: Verificar tablas**

```sql
SHOW CREATE TABLE mybb_rol_mensajes;
SHOW CREATE TABLE mybb_rol_alertas;
```

- [ ] **Step 4: Commit**

```bash
git add scripts/migrate-mensajes-alertas.php
git commit -m "feat: añadir tablas rol_mensajes y rol_alertas"
```
