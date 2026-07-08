# Flujo de Aprobación + Mensajes Directos + Alertas — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar el flujo completo de aprobación de personajes (creación → revisión staff → aprobar/moderar/rechazar), sistema de mensajes directos por personaje, sistema de alertas con campana en navbar, y página de revisión visual de fichas para staff.

**Architecture:** Se añaden 2 tablas SQL (`rol_mensajes`, `rol_alertas`), 3 páginas PHP nuevas (`revisar-personaje.php`, `mensajes.php`, `alertas.php`), se modifica el navbar para incluir menú de mensajes y campana de alertas, se actualiza `zona-staff.php` y `personajes.php` para el nuevo flujo.

**Tech Stack:** PHP 7.4+, MyBB 1.8 DB layer, MySQL/InnoDB, Vanilla JS, CSS custom properties (Foundry Brutalism).

## Global Constraints

- **Paleta y diseño**: Foundry Brutalism con tokens CSS (`--iron`, `--ember`, `--paper`, `--rivet`, etc.), tipografía Big Shoulders Display / Space Mono / Archivo.
- **Bordes**: 2px solid #000 en todos los componentes. Sombras sólidas sin blur.
- **Idioma**: Todo el contenido en español.
- **Tablas**: Prefijo `mybb_rol_` para tablas nuevas (consistente con las existentes).
- **Seguridad**: `verify_post_check()` en todos los POST, `htmlspecialchars_uni()` en todo output, `(int)` cast en todas las queries.
- **Staff**: `$staff_level` se lee de `$mybb->user['iforge_staff_level']` (plugin `iforge_rol.php`).
- **Navbar**: Toda la UI de navegación se genera en `iforge_rol_navbar_html()` dentro del plugin.

---

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

---

### Task 2: Modificar `iforge_rol_navbar_html()` — añadir campana de alertas y enlace a mensajes

**Files:**
- Modify: `inc/plugins/iforge_rol.php:166-218`

**Interfaces:**
- Consumes: Tablas `rol_alertas` y `rol_mensajes` (Task 1)
- Produces: Navbar actualizado con contador de alertas y enlace a mensajes

- [ ] **Step 1: Añadir función helper para contar alertas no leídas**

Dentro de `iforge_rol.php`, antes de la función del navbar:

```php
function iforge_rol_alertas_no_leidas(int $uid): int {
    global $db;
    if (!$db->table_exists('rol_alertas')) return 0;
    $q = $db->simple_select('rol_alertas', 'COUNT(*) as cnt', "uid = {$uid} AND leido = 0");
    return (int)$db->fetch_field($q, 'cnt');
}
```

- [ ] **Step 2: Añadir función helper para contar mensajes no leídos**

```php
function iforge_rol_mensajes_no_leidos(int $pid): int {
    global $db;
    if (!$db->table_exists('rol_mensajes') || $pid <= 0) return 0;
    $q = $db->query("
        SELECT COUNT(DISTINCT thread_id) as cnt
        FROM " . TABLE_PREFIX . "rol_mensajes
        WHERE destino_pid = {$pid} AND leido = 0
    ");
    return (int)$db->fetch_field($q, 'cnt');
}
```

- [ ] **Step 3: Modificar `iforge_rol_navbar_html()` — añadir campana y enlace mensajes**

Reemplazar el bloque del lado derecho (líneas 194-207 actuales) con:

```php
// ── Lado derecho ──
$bburl = $mybb->settings['bburl'];
$right = '<div class="iforge-nav-right">';

if ($loggedin) {
    // Campana de alertas
    $alertas_count = iforge_rol_alertas_no_leidas($uid);
    $right .= '<a href="' . $bburl . '/alertas.php" class="iforge-nav-bell" title="Alertas">';
    $right .= '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
    if ($alertas_count > 0) {
        $right .= '<span class="iforge-bell-badge">' . $alertas_count . '</span>';
    }
    $right .= '</a>';

    // Menú de usuario
    $right .= '<div class="iforge-user-menu">';
    $right .= '<button type="button" class="iforge-user-name" onclick="this.nextElementSibling.classList.toggle(\'open\')" aria-expanded="false">' . htmlspecialchars_uni($displayName) . '</button>';
    $right .= '<div class="iforge-dropdown">';
    $right .= '<a href="' . $bburl . '/personajes.php" class="iforge-dropdown-item">Mis personajes</a>';
    
    // Mensajes (solo si hay personaje activo)
    if ($activePid > 0) {
        $msgs_count = iforge_rol_mensajes_no_leidos($activePid);
        $msg_label = 'Mensajes';
        if ($msgs_count > 0) $msg_label .= ' (' . $msgs_count . ')';
        $right .= '<a href="' . $bburl . '/mensajes.php" class="iforge-dropdown-item">' . $msg_label . '</a>';
    }
    
    $right .= '<a href="' . $bburl . '/alertas.php" class="iforge-dropdown-item">Alertas';
    if ($alertas_count > 0) $right .= ' (' . $alertas_count . ')';
    $right .= '</a>';
    $right .= '<hr class="iforge-dropdown-divider">';
    $right .= '<a href="' . $bburl . '/usercp.php" class="iforge-dropdown-item">Panel</a>';
    $right .= '<a href="' . $bburl . '/member.php?action=profile&amp;uid=' . $uid . '" class="iforge-dropdown-item">Perfil</a>';
    $right .= '<hr class="iforge-dropdown-divider">';
    $right .= '<a href="' . $bburl . '/member.php?action=logout&amp;logoutkey=' . $logoutkey . '" class="iforge-dropdown-item">Salir</a>';
    $right .= '</div></div>';
} else {
    $right .= '<a href="' . $bburl . '/member.php?action=register" class="iforge-nav-cta">Reg&iacute;strate</a>';
    $right .= '<a href="' . $bburl . '/member.php?action=login" class="iforge-btn-ghost iforge-btn-sm">Acceder</a>';
}
$right .= '</div>';
```

- [ ] **Step 4: Añadir CSS para la campana en `iforge_rol_navbar_css()`**

Después de la línea que cierra el bloque `.iforge-btn-ghost`:

```css
#iforge-navbar .iforge-nav-bell{position:relative;display:flex;align-items:center;justify-content:center;padding:6px 8px;color:var(--paper-dim);transition:color .12s}
#iforge-navbar .iforge-nav-bell:hover{color:var(--ember-hi)}
#iforge-navbar .iforge-nav-bell svg{fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
#iforge-navbar .iforge-bell-badge{position:absolute;top:0;right:2px;min-width:16px;height:16px;padding:0 4px;border-radius:8px;background:var(--crack);color:#fff;font-family:var(--mono);font-size:.58rem;font-weight:700;line-height:16px;text-align:center}
```

- [ ] **Step 5: Verificar que el navbar carga sin errores**

Navegar a `http://localhost/iforge/index.php` y verificar que:
- Campana aparece a la derecha del nombre
- Menú desplegable tiene "Mensajes" y "Alertas"
- Sin errores de consola

- [ ] **Step 6: Commit**

```bash
git add inc/plugins/iforge_rol.php
git commit -m "feat: añadir campana de alertas y enlace mensajes al navbar"
```

---

### Task 3: Modificar `personajes.php` — quitar aprobación, añadir flag "En revisión"

**Files:**
- Modify: `personajes.php`

**Interfaces:**
- Consumes: `rol_personajes.estado`
- Produces: Página de personajes sin panel de aprobación staff, con flags de estado visibles

- [ ] **Step 1: Eliminar la sección de aprobación de staff**

Eliminar líneas 353-397 completas (todo el bloque `<?php if ($staff_level >= 1): ?>` con la placa de "Aprobación de expedientes").

- [ ] **Step 2: Eliminar el POST handler `approve_char` y `reject_char`**

Eliminar líneas 83-96 (el bloque que procesa `approve_char` y `reject_char` del lado staff).

- [ ] **Step 3: Mejorar el badge de estado "En revisión" en las tarjetas de personaje**

En la sección donde se renderiza cada personaje (línea ~435, dentro del `.pjcard-body`), asegurar que el chip de estado sea prominente cuando está `en revision`:

```php
$est = iforge_estado_label($pj['estado']);
echo '<span class="pjcard-chip" style="background:' . $est[1] . '">' . $est[0] . '</span>';
```

Y en el CSS (añadir al bloque `<style>`) dar más peso visual al estado "En revisión":

```css
.pjcard-chip{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 11px;border:2px solid #000;display:inline-block}
.pjcard-chip[style*="--h6"]{animation:pulse-revision 2s ease-in-out infinite}
@keyframes pulse-revision{0%,100%{box-shadow:0 0 0 0 rgba(255,203,147,.4)}50%{box-shadow:0 0 0 6px rgba(255,203,147,0)}}
```

- [ ] **Step 4: Verificar en navegador**

Navegar a `http://localhost/iforge/personajes.php` como staff y verificar:
- No aparece la sección "Aprobación de expedientes"
- Los personajes en `estado = 'revision'` muestran chip "En revisión" con animación

- [ ] **Step 5: Commit**

```bash
git add personajes.php
git commit -m "feat: quitar panel de aprobación de personajes.php, añadir flag visual En revisión"
```

---

### Task 4: Crear `revisar-personaje.php` — página de revisión visual para staff

**Files:**
- Create: `revisar-personaje.php`

**Interfaces:**
- Consumes: `rol_personajes` con `pid` vía GET, `iforge_rol_navbar_html()`, `iforge_rol_data.php`
- Produces: Página completa con ficha visual del personaje + botones Aprobar/Moderar/Rechazar
- Produce: Alerta para el jugador al aprobar/rechazar/moderar

- [ ] **Step 1: Crear `revisar-personaje.php`**

```php
<?php
/**
 * I-Forge · Revisión de expediente (Staff)
 * Vista detallada de ficha con botones Aprobar / Moderar / Rechazar.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'revisar-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/iforge_rol_data.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');
$pid       = (int)($mybb->get_input('pid', MyBB::INPUT_INT));

// Staff level
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['iforge_staff_level'])) {
        $staff_level = (int)$mybb->user['iforge_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) $staff_level = (int)$db->fetch_field($cq, 'staff_level');
    }
}

// Cargar personaje
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($q);
}

if (!$pj) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

$datos     = $pj['datos'] ? json_decode($pj['datos'], true) : array();
$inventario = $pj['inventario'] ? json_decode($pj['inventario'], true) : array();
$economia   = $pj['economia'] ? json_decode($pj['economia'], true) : array();
$bio        = $pj['bio'] ? json_decode($pj['bio'], true) : array();

// Dueño
$owner_name = '?';
if ($pj['uid'] > 0) {
    $uq = $db->simple_select('users', 'username', "uid = " . (int)$pj['uid'], array('limit' => 1));
    $owner_name = htmlspecialchars_uni($db->fetch_field($uq, 'username'));
}

// POST: aprobar / rechazar / moderar
$flash = ''; $flash_kind = 'ok';
if ($loggedin && $staff_level >= 1 && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga la página.';
        $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        $mensaje_staff = trim($mybb->get_input('mensaje_staff'));

        if ($action === 'approve') {
            $db->update_query('rol_personajes', array('estado' => 'aprobado', 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ($db->table_exists('rol_tramites')) {
                $db->update_query('rol_tramites', array('estado' => 'aprobado', 'lastedit' => TIME_NOW), "pid = {$pid} AND tipo = 'crear_personaje'");
            }
            // Alerta para el jugador
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_aprobado',
                    'titulo' => 'Personaje aprobado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido aprobado. Ya puedes activarlo desde la página de Personaje.',
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha "' . htmlspecialchars_uni($pj['nombre']) . '" aprobada.';
            // Recargar estado
            $pj['estado'] = 'aprobado';

        } elseif ($action === 'reject') {
            $db->update_query('rol_personajes', array('estado' => 'rechazado', 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ($db->table_exists('rol_tramites')) {
                $db->update_query('rol_tramites', array('estado' => 'rechazado', 'lastedit' => TIME_NOW), "pid = {$pid} AND tipo = 'crear_personaje'");
            }
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_rechazado',
                    'titulo' => 'Personaje rechazado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido rechazado.' . ($mensaje_staff !== '' ? ' Motivo: ' . $db->escape_string($mensaje_staff) : ''),
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha "' . htmlspecialchars_uni($pj['nombre']) . '" rechazada.';
            $pj['estado'] = 'rechazado';

        } elseif ($action === 'moderate' && $mensaje_staff !== '') {
            // Moderar: enviar MD al personaje
            if ($db->table_exists('rol_mensajes')) {
                // Buscar staff pid del narrador
                $staff_pid = 0;
                $sq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
                if ($db->num_rows($sq)) $staff_pid = (int)$db->fetch_field($sq, 'pid');
                
                // Crear hilo
                $thread_id = TIME_NOW; // Simple unique thread ID
                $db->insert_query('rol_mensajes', array(
                    'thread_id' => $thread_id,
                    'origen_pid' => $staff_pid,
                    'destino_pid' => $pid,
                    'asunto' => 'Moderación: ' . $db->escape_string($pj['nombre']),
                    'cuerpo' => $db->escape_string($mensaje_staff),
                    'leido' => 0,
                    'dateline' => TIME_NOW
                ));
            }
            // Alerta
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_moderado',
                    'titulo' => 'Ficha moderada: cambios solicitados',
                    'cuerpo' => 'El staff ha solicitado cambios en tu personaje "' . $db->escape_string($pj['nombre']) . '". Revisa tus mensajes para ver los detalles.',
                    'link' => $bburl . '/mensajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Mensaje de moderación enviado a ' . htmlspecialchars_uni($pj['nombre']) . '.';
            $pj['estado'] = 'revision'; // Sigue en revisión
        }
    }
}

// Iniciales para avatar
$initials = '';
$parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
foreach ($parts as $p) { if ($p !== '') $initials .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'); }
$initials = mb_substr($initials, 0, 2, 'UTF-8');
$initials_e = htmlspecialchars_uni($initials);

// Función local para heat
function iforge_heat_var($rango) {
    $map = ['F'=>'--h1','E'=>'--h1','D'=>'--h2','C'=>'--h3','B'=>'--h4','A'=>'--h5','S'=>'--h6','SS'=>'--h7','M'=>'--h8','M+'=>'--h9'];
    return $map[$rango] ?? '--h1';
}

// Función local para label de estado
function _estado_label($estado) {
    switch ($estado) {
        case 'aprobado': return ['Aprobado', 'var(--patina-hi)'];
        case 'revision': return ['En revisión', 'var(--h6)'];
        case 'rechazado': return ['Rechazado', 'var(--crack)'];
        default: return ['Borrador', 'var(--rivet)'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Revisar: <?php echo htmlspecialchars_uni($pj['nombre']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#0b3157; --iron-plate:#10477B; --iron-hi:#175a95; --iron-edge:#082742;
  --rivet:#3d6f9e;
  --concrete:#eef6fc; --concrete-2:#dbecf9; --concrete-line:#b2d3ea;
  --ink:#0a2f52; --ink-2:#1c5285; --ash:#5c83a7; --paper:#eaf4fb; --paper-dim:#a9c6e0;
  --ember:#FFCB93; --ember-hi:#FFE9A3; --patina:#41A4E0; --patina-hi:#63b8ea; --crack:#e63b2e;
  --h1:#10477B; --h2:#2f6ea8; --h3:#458CC5; --h4:#41A4E0; --h5:#63b8ea;
  --h6:#FFCB93; --h7:#ffdcae; --h8:#FFE9A3; --h9:#fff6d8;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
.wrap{max-width:1100px;margin:0 auto;padding:0 18px}

/* BREADCRUMB */
.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1100px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim);text-decoration:none}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

/* BOTONES */
.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:12px 20px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{background:var(--ember-hi);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-danger{background:var(--crack);color:#fff}
.btn-danger:hover{background:var(--red-hi);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:7px 13px;font-size:.7rem}

/* SHEAD */
.shead{display:flex;align-items:baseline;gap:14px;margin:20px 0 14px}
.shead h1{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

/* FLASH */
.flash{font-family:var(--mono);font-size:.72rem;padding:10px 14px;border:2px solid #000;margin-bottom:14px}
.flash.ok{background:var(--iron-plate);color:var(--h6);border-color:var(--patina)}
.flash.warn{background:var(--iron-plate);color:var(--ember);border-color:var(--ember)}

/* FICHA */
.sheet{border:2px solid #000;background:var(--iron-plate);margin-bottom:14px}
.sheet-h{background:var(--iron-edge);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:2px solid #000}
.sheet-h-left{display:flex;align-items:center;gap:12px}
.sheet-av{width:56px;height:56px;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-weight:900;font-size:1.4rem;color:var(--ember-hi)}
.sheet-name{font-family:var(--disp);font-weight:800;font-size:1.7rem;text-transform:uppercase;color:var(--paper);line-height:1}
.sheet-owner{font-family:var(--mono);font-size:.62rem;color:var(--ash);text-transform:uppercase;margin-top:2px}
.sheet-badge{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;padding:5px 11px;border:2px solid #000;display:inline-block}
.sheet-b{padding:18px}

.sheet-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.sheet-block{border:2px solid #000;background:var(--iron);overflow:hidden}
.sheet-block-h{background:var(--iron-edge);padding:8px 12px;font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--paper-dim);border-bottom:2px solid #000}
.sheet-block-b{padding:12px}

.stat-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--iron-hi);font-size:.82rem}
.stat-row:last-child{border-bottom:none}
.stat-row .sn{font-family:var(--mono);font-size:.64rem;font-weight:700;color:var(--paper-dim);text-transform:uppercase;min-width:36px}
.stat-row .sl{color:var(--paper-dim);flex:1}
.stat-row .sv{font-family:var(--mono);font-size:.7rem;font-weight:700}
.stat-row .rb{font-family:var(--disp);font-weight:900;font-size:.85rem;padding:2px 8px;border:2px solid #000;line-height:1}

.chip-list{display:flex;flex-wrap:wrap;gap:6px}
.chip-item{font-family:var(--mono);font-size:.62rem;font-weight:700;padding:4px 10px;border:1px solid var(--rivet);color:var(--paper-dim)}
.chip-item.good{color:var(--patina-hi);border-color:var(--patina)}
.chip-item.bad{color:var(--crack);border-color:var(--crack)}

.text-block{font-size:.84rem;color:var(--paper-dim);line-height:1.6}
.text-block strong{color:var(--paper)}
.text-block p{margin-bottom:8px}

/* ACCIONES */
.actions-bar{border:2px solid #000;background:var(--iron-plate);margin:14px 0}
.actions-bar-in{padding:14px 18px;display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap}
.actions-bar .ab-label{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--paper-dim);margin-bottom:4px}
.actions-bar button{margin-left:auto}
.actions-bar textarea{flex:1;min-width:280px;min-height:60px;background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--mono);font-size:.72rem;padding:8px 10px;resize:vertical}
.actions-bar textarea:focus{outline:none;border-color:var(--ember)}

/* FOOTER */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}

/* RESPONSIVE */
@media(max-width:768px){
  .sheet-grid{grid-template-columns:1fr}
  .actions-bar-in{flex-direction:column;align-items:stretch}
  .actions-bar button{margin-left:0}
}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
    <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1>Revisar expediente</h1>
    <span class="code">// staff</span>
    <span class="rule"></span>
  </div>

  <?php if ($flash !== ''): ?>
    <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
  <?php endif; ?>

  <!-- HEADER FICHA -->
  <div class="sheet">
    <div class="sheet-h">
      <div class="sheet-h-left">
        <div class="sheet-av"><?php echo htmlspecialchars_uni(mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8')); ?></div>
        <div>
          <div class="sheet-name"><?php echo htmlspecialchars_uni($pj['nombre']); ?></div>
          <div class="sheet-owner">de <?php echo $owner_name; ?> · creado <?php echo date('d/m/Y', (int)$pj['dateline']); ?></div>
        </div>
      </div>
      <?php $est = _estado_label($pj['estado']); ?>
      <span class="sheet-badge" style="background:<?php echo $est[1]; ?>;color:var(--iron)"><?php echo $est[0]; ?></span>
    </div>

    <div class="sheet-b">
      <div class="sheet-grid">
        <!-- COL IZQUIERDA -->
        <div>
          <!-- IDENTIDAD -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Identidad</div>
            <div class="sheet-block-b">
              <div class="stat-row"><span class="sn">Raza</span><span class="sl"><?php echo htmlspecialchars_uni(ucfirst($datos['raza_principal'] ?? '?')); ?><?php echo !empty($datos['hibrido']) ? ' / ' . htmlspecialchars_uni(ucfirst($datos['raza_secundaria'] ?? '')) : ''; ?></span></div>
              <?php if (!empty($datos['apodo'])): ?>
                <div class="stat-row"><span class="sn">Apodo</span><span class="sl"><?php echo htmlspecialchars_uni($datos['apodo']); ?></span></div>
              <?php endif; ?>
              <div class="stat-row"><span class="sn">Edad</span><span class="sl"><?php echo htmlspecialchars_uni($datos['edad'] ?? '?'); ?></span></div>
              <div class="stat-row"><span class="sn">Género</span><span class="sl"><?php echo htmlspecialchars_uni($datos['genero'] ?? '?'); ?></span></div>
              <div class="stat-row"><span class="sn">Facción</span><span class="sl"><?php echo htmlspecialchars_uni(ucfirst($datos['faccion'] ?? '?')); ?></span></div>
              <?php if (!empty($datos['tiene_d'])): ?>
                <div class="stat-row"><span class="sn">D.</span><span class="sl" style="color:var(--ember-hi);font-weight:700">Portador de la D.</span></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- STATS -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Stats · Rango <?php echo htmlspecialchars_uni($pj['rango'] ?? '?'); ?></div>
            <div class="sheet-block-b">
              <?php 
              $pilares = array('Cuerpo'=>['FUE','DES','VIG','AGI'], 'Mente'=>['INT','ING','CON','PER'], 'Espíritu'=>['CAR','CTR','VOL','SEN']);
              $labels = array('FUE'=>'Fuerza','DES'=>'Destreza','VIG'=>'Vigor','AGI'=>'Agilidad','INT'=>'Intelecto','ING'=>'Ingenio','CON'=>'Concentración','PER'=>'Percepción','CAR'=>'Carisma','CTR'=>'Control','VOL'=>'Voluntad','SEN'=>'Sensibilidad');
              $stats = $datos['stats_efectivas'] ?? $datos['stats_base'] ?? array();
              if (!empty($stats)):
                foreach ($pilares as $pilarName => $keys): ?>
                  <div style="font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--ash);margin:8px 0 4px"><?php echo $pilarName; ?></div>
                  <?php foreach ($keys as $k):
                    $v = (int)($stats[$k] ?? 0);
                    $rangos = ['F','E','D','C','B','A','S','SS','M','M+'];
                    $rl = $rangos[max(0, min(9, $v))] ?? '?';
                    $hv = iforge_heat_var($rl);
                  ?>
                    <div class="stat-row">
                      <span class="sn"><?php echo $k; ?></span>
                      <span class="sl"><?php echo $labels[$k] ?? $k; ?></span>
                      <span class="sv" style="color:var(<?php echo $hv; ?>)"><?php echo $rl; ?></span>
                    </div>
                  <?php endforeach;
                endforeach;
              else: ?>
                <p style="color:var(--paper-dim);font-size:.82rem">Sin datos de stats.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- COL DERECHA -->
        <div>
          <!-- VIRTUDES Y DEFECTOS -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Virtudes <?php echo !empty($datos['pc_gastado']) ? '(+' . (int)$datos['pc_gastado'] . ' PC)' : ''; ?></div>
            <div class="sheet-block-b">
              <?php if (!empty($datos['virtudes'])): ?>
                <div class="chip-list">
                  <?php foreach ($datos['virtudes'] as $v): ?>
                    <span class="chip-item good"><?php echo htmlspecialchars_uni(is_array($v) ? ($v['nombre'] ?? '?') : $v); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p style="color:var(--paper-dim);font-size:.78rem">Sin virtudes.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Defectos <?php echo !empty($datos['pc_devuelto']) ? '(+' . (int)$datos['pc_devuelto'] . ' PC)' : ''; ?></div>
            <div class="sheet-block-b">
              <?php if (!empty($datos['defectos'])): ?>
                <div class="chip-list">
                  <?php foreach ($datos['defectos'] as $d): ?>
                    <span class="chip-item bad"><?php echo htmlspecialchars_uni(is_array($d) ? ($d['nombre'] ?? '?') : $d); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p style="color:var(--paper-dim);font-size:.78rem">Sin defectos.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- EQUIPO -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Equipo · <?php echo htmlspecialchars_uni(number_format((int)($economia['berries'] ?? 0))); ?> berries</div>
            <div class="sheet-block-b">
              <?php if (!empty($inventario['arma'])): ?>
                <div class="stat-row"><span class="sl">Arma</span><span class="sv" style="color:var(--paper-dim)"><?php echo htmlspecialchars_uni($inventario['arma']); ?></span></div>
              <?php endif; ?>
              <?php if (!empty($inventario['objeto_personal'])): ?>
                <div class="stat-row"><span class="sl">Objeto personal</span><span class="sv" style="color:var(--paper-dim)"><?php echo htmlspecialchars_uni($inventario['objeto_personal']); ?></span></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- BIO -->
      <?php if (!empty($bio)): ?>
        <div class="sheet-block" style="margin-top:14px">
          <div class="sheet-block-h">Historia y personalidad</div>
          <div class="sheet-block-b">
            <div class="text-block">
              <?php if (!empty($bio['concepto'])): ?><p><strong>Concepto:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['concepto'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['historia_pasado'])): ?><p><strong>Pasado:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['historia_pasado'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['motivacion'])): ?><p><strong>Motivación:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['motivacion'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['relaciones'])): ?><p><strong>Relaciones:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['relaciones'])); ?></p><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- BARRA DE ACCIONES (solo si está en revisión) -->
  <?php if ($pj['estado'] === 'revision'): ?>
  <div class="actions-bar">
    <form method="post" action="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo $pid; ?>">
      <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
      <div class="actions-bar-in">
        <div>
          <div class="ab-label">Aprobar ficha</div>
          <button type="submit" name="action" value="approve" class="btn btn-hot btn-sm">Aprobar</button>
        </div>
        <div>
          <div class="ab-label">Rechazar ficha</div>
          <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Rechazar</button>
        </div>
        <div style="flex:1;min-width:250px">
          <div class="ab-label">Moderar (enviar cambios solicitados por MD)</div>
          <textarea name="mensaje_staff" placeholder="Describe los cambios necesarios..."></textarea>
        </div>
        <button type="submit" name="action" value="moderate" class="btn btn-ghost btn-sm">Enviar moderación</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
```

- [ ] **Step 2: Verificar en navegador**

Navegar a `http://localhost/iforge/revisar-personaje.php?pid=1` (con un personaje en estado `revision`) y verificar:
- Ficha visual completa (identidad, stats, virtudes, defectos, equipo, historia)
- Botones Aprobar / Rechazar / Moderar visibles
- Al hacer clic en Aprobar, el estado cambia y se crea alerta
- Al hacer clic en Moderar, se envía MD y se crea alerta

- [ ] **Step 3: Commit**

```bash
git add revisar-personaje.php
git commit -m "feat: página de revisión de expedientes para staff con aprobar/moderar/rechazar"
```

---

### Task 5: Crear `mensajes.php` — sistema de mensajes directos

**Files:**
- Create: `mensajes.php`

**Interfaces:**
- Consumes: `rol_mensajes`, `rol_personajes` (personaje activo)
- Produces: Interfaz de bandeja de mensajes con hilos, lectura y respuesta

- [ ] **Step 1: Crear `mensajes.php`**

```php
<?php
/**
 * I-Forge · Mensajes Directos
 * Bandeja de mensajes por personaje: lista de hilos, lectura, envío y respuesta.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mensajes.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');
$activePid = (int)($mybb->user['iforge_active_pid'] ?? 0);

// Fallback: buscar personaje activo
if ($activePid <= 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $aq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($aq)) $activePid = (int)$db->fetch_field($aq, 'pid');
}

$staff_level = 0;
if ($loggedin && isset($mybb->user['iforge_staff_level'])) {
    $staff_level = (int)$mybb->user['iforge_staff_level'];
}

// POST: enviar mensaje
$flash = ''; $flash_kind = 'ok';
if ($loggedin && $activePid > 0 && $mybb->request_method === 'post' && $db->table_exists('rol_mensajes')) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.'; $flash_kind = 'warn';
    } else {
        $thread_id = (int)($mybb->get_input('thread_id', MyBB::INPUT_INT));
        $destino_pid = (int)($mybb->get_input('destino_pid', MyBB::INPUT_INT));
        $asunto = trim($mybb->get_input('asunto'));
        $cuerpo = trim($mybb->get_input('cuerpo'));

        if ($destino_pid <= 0 || $cuerpo === '') {
            $flash = 'Faltan campos obligatorios.'; $flash_kind = 'warn';
        } else {
            if ($thread_id <= 0) $thread_id = TIME_NOW;
            $db->insert_query('rol_mensajes', array(
                'thread_id' => $thread_id,
                'origen_pid' => $activePid,
                'destino_pid' => $destino_pid,
                'asunto' => $db->escape_string($asunto),
                'cuerpo' => $db->escape_string($cuerpo),
                'leido' => 0,
                'dateline' => TIME_NOW
            ));
            // Alerta para el destinatario
            if ($db->table_exists('rol_alertas')) {
                $du = $db->simple_select('rol_personajes', 'uid', "pid = {$destino_pid}", array('limit' => 1));
                $dest_uid = (int)$db->fetch_field($du, 'uid');
                $db->insert_query('rol_alertas', array(
                    'pid' => $destino_pid, 'uid' => $dest_uid,
                    'tipo' => 'mensaje_nuevo',
                    'titulo' => 'Nuevo mensaje',
                    'cuerpo' => 'Has recibido un mensaje nuevo.',
                    'link' => $bburl . '/mensajes.php?t=' . $thread_id,
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Mensaje enviado.';
        }
    }
}

// Marcar hilo como leído
$thread_open = (int)($mybb->get_input('t', MyBB::INPUT_INT));
if ($thread_open > 0 && $activePid > 0 && $db->table_exists('rol_mensajes')) {
    $db->update_query('rol_mensajes', array('leido' => 1), "thread_id = {$thread_open} AND destino_pid = {$activePid}");
}

// Cargar hilos (conversaciones)
$hilos = array();
if ($activePid > 0 && $db->table_exists('rol_mensajes')) {
    $hq = $db->query("
        SELECT m.*, 
               CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END as otro_pid,
               (SELECT nombre FROM " . TABLE_PREFIX . "rol_personajes WHERE pid = CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END LIMIT 1) as otro_nombre,
               (SELECT COUNT(*) FROM " . TABLE_PREFIX . "rol_mensajes WHERE thread_id = m.thread_id AND destino_pid = {$activePid} AND leido = 0) as no_leidos
        FROM " . TABLE_PREFIX . "rol_mensajes m
        WHERE m.origen_pid = {$activePid} OR m.destino_pid = {$activePid}
        GROUP BY m.thread_id
        ORDER BY MAX(m.dateline) DESC
    ");
    while ($row = $db->fetch_array($hq)) $hilos[] = $row;
}

// Cargar mensajes del hilo abierto
$mensajes_hilo = array();
if ($thread_open > 0 && $db->table_exists('rol_mensajes')) {
    $mq = $db->query("
        SELECT m.*, 
               (SELECT nombre FROM " . TABLE_PREFIX . "rol_personajes WHERE pid = m.origen_pid LIMIT 1) as origen_nombre
        FROM " . TABLE_PREFIX . "rol_mensajes m
        WHERE m.thread_id = {$thread_open} AND (m.origen_pid = {$activePid} OR m.destino_pid = {$activePid})
        ORDER BY m.dateline ASC
    ");
    while ($row = $db->fetch_array($mq)) $mensajes_hilo[] = $row;
}

// Lista de personajes para enviar nuevo mensaje (solo aprobados)
$personajes_destino = array();
if ($activePid > 0 && $db->table_exists('rol_personajes')) {
    $dq = $db->simple_select('rol_personajes', 'pid, nombre', "pid != {$activePid} AND estado = 'aprobado'", array('order_by' => 'nombre'));
    while ($row = $db->fetch_array($dq)) $personajes_destino[] = $row;
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) { if ($p !== '') $initials .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'); }
    $initials = mb_substr($initials, 0, 2, 'UTF-8');
}
$initials_e = htmlspecialchars_uni($initials);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Mensajes</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#0b3157; --iron-plate:#10477B; --iron-hi:#175a95; --iron-edge:#082742;
  --rivet:#3d6f9e; --paper:#eaf4fb; --paper-dim:#a9c6e0; --ash:#5c83a7;
  --ember:#FFCB93; --ember-hi:#FFE9A3; --patina:#41A4E0; --crack:#e63b2e;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
a{color:var(--ember-hi);text-decoration:none}
.wrap{max-width:1300px;margin:0 auto;padding:0 18px}

.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1300px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

.shead{display:flex;align-items:baseline;gap:14px;margin:20px 0 14px}
.shead h1{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:10px 18px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:6px 12px;font-size:.7rem}

.flash{font-family:var(--mono);font-size:.72rem;padding:10px 14px;border:2px solid #000;margin-bottom:14px}
.flash.ok{background:var(--iron-plate);color:var(--h6);border-color:var(--patina)}
.flash.warn{background:var(--iron-plate);color:var(--ember);border-color:var(--ember)}

.msg-shell{display:grid;grid-template-columns:300px 1fr;gap:0;border:2px solid #000;min-height:600px}
.msg-sidebar{background:var(--iron-edge);border-right:2px solid #000;overflow-y:auto}
.msg-sidebar-in{padding:0}
.msg-thread{display:block;width:100%;padding:12px 14px;background:transparent;border:none;border-bottom:1px solid var(--iron);cursor:pointer;text-align:left;color:var(--paper-dim);font-family:var(--body);font-size:.82rem;transition:background .12s}
.msg-thread:hover{background:var(--iron-plate)}
.msg-thread.active{background:var(--iron-plate);border-left:3px solid var(--ember)}
.msg-thread .th-name{font-weight:600;color:var(--paper);margin-bottom:3px}
.msg-thread .th-subject{font-size:.74rem;color:var(--paper-dim)}
.msg-thread .th-meta{font-family:var(--mono);font-size:.58rem;color:var(--ash);margin-top:4px}
.msg-thread .th-badge{display:inline-block;min-width:20px;height:18px;background:var(--crack);color:#fff;font-family:var(--mono);font-size:.56rem;font-weight:700;border-radius:9px;text-align:center;line-height:18px;padding:0 5px;margin-left:6px}

.msg-main{padding:0;display:flex;flex-direction:column;background:var(--iron)}
.msg-list{flex:1;overflow-y:auto;padding:16px 20px;max-height:460px}
.msg-bubble{margin-bottom:14px;max-width:80%}
.msg-bubble.mine{margin-left:auto}
.msg-bubble .b-head{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--ash);margin-bottom:4px}
.msg-bubble .b-body{background:var(--iron-plate);border:2px solid #000;padding:10px 14px;font-size:.84rem;color:var(--paper-dim);line-height:1.55}
.msg-bubble.mine .b-body{background:var(--iron-edge);color:var(--paper)}
.msg-bubble .b-time{font-family:var(--mono);font-size:.54rem;color:var(--ash);margin-top:4px;text-align:right}

.msg-form{border-top:2px solid #000;padding:14px 20px;background:var(--iron-plate)}
.msg-form textarea{width:100%;min-height:80px;background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--body);font-size:.82rem;padding:10px 12px;resize:vertical;margin-bottom:8px}
.msg-form textarea:focus{outline:none;border-color:var(--ember)}
.msg-form .form-row{display:flex;gap:10px;align-items:flex-end}
.msg-form select{background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--mono);font-size:.7rem;padding:8px 10px}
.msg-form input[type="text"]{background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--body);font-size:.82rem;padding:8px 10px;flex:1}

.empty-state{text-align:center;padding:48px 20px;color:var(--paper-dim)}
.empty-state .big{font-family:var(--disp);font-weight:800;font-size:1.6rem;text-transform:uppercase;color:var(--paper);margin-bottom:8px}
.empty-state p{font-family:var(--mono);font-size:.72rem;max-width:48ch;margin:0 auto;line-height:1.6}

.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}

@media(max-width:768px){
  .msg-shell{grid-template-columns:1fr}
  .msg-sidebar{border-right:none;border-bottom:2px solid #000;max-height:200px}
  .msg-list{max-height:300px}
}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <b>Mensajes</b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1>Mensajes</h1>
    <span class="code">// mensajería directa</span>
    <span class="rule"></span>
  </div>

  <?php if ($flash !== ''): ?>
    <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
  <?php endif; ?>

  <?php if ($activePid <= 0): ?>
    <div class="empty-state">
      <div class="big">Sin personaje activo</div>
      <p>Activa un personaje desde la página de Personaje para usar la mensajería.</p>
    </div>
  <?php else: ?>
    <div class="msg-shell">
      <!-- SIDEBAR: lista de hilos -->
      <div class="msg-sidebar">
        <div class="msg-sidebar-in">
          <div style="padding:10px 14px;border-bottom:2px solid #000;display:flex;justify-content:space-between;align-items:center">
            <span style="font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;color:var(--ash)">Conversaciones</span>
            <button onclick="document.getElementById('newMsgForm').style.display='block';document.getElementById('threadView').style.display='none'" class="btn btn-ghost btn-sm" style="font-size:.6rem;padding:4px 8px">+ Nuevo</button>
          </div>
          <?php if (empty($hilos)): ?>
            <div style="padding:20px;text-align:center;font-family:var(--mono);font-size:.64rem;color:var(--ash)">Sin mensajes aún.</div>
          <?php else: ?>
            <?php foreach ($hilos as $h): ?>
              <a href="?t=<?php echo (int)$h['thread_id']; ?>" class="msg-thread<?php echo $thread_open === (int)$h['thread_id'] ? ' active' : ''; ?>">
                <div class="th-name"><?php echo htmlspecialchars_uni($h['otro_nombre'] ?? '?'); ?></div>
                <div class="th-subject"><?php echo htmlspecialchars_uni($h['asunto']); ?></div>
                <div class="th-meta"><?php echo date('d/m H:i', (int)$h['dateline']); ?><?php if ((int)$h['no_leidos'] > 0): ?><span class="th-badge"><?php echo (int)$h['no_leidos']; ?></span><?php endif; ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- MAIN: mensajes del hilo + formulario -->
      <div class="msg-main">
        <?php if ($thread_open > 0 && !empty($mensajes_hilo)): ?>
          <div class="msg-list" id="threadView">
            <?php foreach ($mensajes_hilo as $msg): 
              $isMine = (int)$msg['origen_pid'] === $activePid;
            ?>
              <div class="msg-bubble<?php echo $isMine ? ' mine' : ''; ?>">
                <div class="b-head"><?php echo htmlspecialchars_uni($msg['origen_nombre'] ?? '?'); ?></div>
                <div class="b-body"><?php echo nl2br(htmlspecialchars_uni($msg['cuerpo'])); ?></div>
                <div class="b-time"><?php echo date('d/m/Y H:i', (int)$msg['dateline']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Responder en hilo -->
          <form method="post" action="<?php echo $bburl; ?>/mensajes.php?t=<?php echo $thread_open; ?>" class="msg-form">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="thread_id" value="<?php echo $thread_open; ?>">
            <?php 
              // Encontrar el otro pid en el hilo
              $otro_pid = 0;
              foreach ($hilos as $h) {
                if ((int)$h['thread_id'] === $thread_open) { $otro_pid = (int)$h['otro_pid']; break; }
              }
            ?>
            <input type="hidden" name="destino_pid" value="<?php echo $otro_pid; ?>">
            <input type="hidden" name="asunto" value="Re: <?php echo htmlspecialchars_uni($mensajes_hilo[0]['asunto'] ?? ''); ?>">
            <textarea name="cuerpo" placeholder="Escribe tu respuesta..."></textarea>
            <button type="submit" class="btn btn-hot btn-sm">Responder</button>
          </form>

        <?php else: ?>
          <div id="threadView">
            <div class="empty-state">
              <div class="big">Selecciona una conversación</div>
              <p>Elige un hilo de la izquierda o crea uno nuevo.</p>
            </div>
          </div>

          <!-- Nuevo mensaje -->
          <form method="post" action="<?php echo $bburl; ?>/mensajes.php" class="msg-form" id="newMsgForm" style="display:block">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <div class="form-row" style="margin-bottom:8px">
              <select name="destino_pid" required>
                <option value="">Destinatario...</option>
                <?php foreach ($personajes_destino as $dp): ?>
                  <option value="<?php echo (int)$dp['pid']; ?>"><?php echo htmlspecialchars_uni($dp['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="asunto" placeholder="Asunto..." required>
            </div>
            <textarea name="cuerpo" placeholder="Escribe tu mensaje..." required></textarea>
            <button type="submit" class="btn btn-hot btn-sm">Enviar mensaje</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
```

- [ ] **Step 2: Verificar**

Navegar a `http://localhost/iforge/mensajes.php` y verificar:
- Lista de hilos a la izquierda
- Lectura de mensajes con burbujas estilo chat
- Envío de nuevo mensaje con selector de destinatario
- Respuesta en hilo existente

- [ ] **Step 3: Commit**

```bash
git add mensajes.php
git commit -m "feat: sistema de mensajes directos por personaje"
```

---

### Task 6: Crear `alertas.php` — página de alertas con campana

**Files:**
- Create: `alertas.php`

**Interfaces:**
- Consumes: `rol_alertas`
- Produces: Página de listado de alertas con botón "Marcar todas como leídas"

- [ ] **Step 1: Crear `alertas.php`**

```php
<?php
/**
 * I-Forge · Alertas
 * Centro de notificaciones: mensajes, aprobaciones, rechazos, moderaciones.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'alertas.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// POST: marcar leídas
if ($loggedin && $mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'), true)) {
    if ($mybb->get_input('mark_all') && $db->table_exists('rol_alertas')) {
        $db->update_query('rol_alertas', array('leido' => 1), "uid = {$uid}");
    }
    if (($aid = (int)($mybb->get_input('mark_one', MyBB::INPUT_INT))) > 0 && $db->table_exists('rol_alertas')) {
        $db->update_query('rol_alertas', array('leido' => 1), "aid = {$aid} AND uid = {$uid}");
    }
}

// Cargar alertas
$alertas = array();
if ($loggedin && $db->table_exists('rol_alertas')) {
    $aq = $db->simple_select('rol_alertas', '*', "uid = {$uid}", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 50));
    while ($row = $db->fetch_array($aq)) $alertas[] = $row;
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) { if ($p !== '') $initials .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'); }
    $initials = mb_substr($initials, 0, 2, 'UTF-8');
}
$initials_e = htmlspecialchars_uni($initials);

$tipo_iconos = [
    'mensaje_nuevo'        => '✉',
    'personaje_aprobado'   => '✓',
    'personaje_rechazado'  => '✕',
    'personaje_moderado'   => '↻',
    'staff_asignado'       => '⚑',
];
$tipo_colores = [
    'mensaje_nuevo'        => 'var(--patina)',
    'personaje_aprobado'   => 'var(--patina-hi)',
    'personaje_rechazado'  => 'var(--crack)',
    'personaje_moderado'   => 'var(--h6)',
    'staff_asignado'       => 'var(--ember)',
];

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Alertas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#0b3157; --iron-plate:#10477B; --iron-hi:#175a95; --iron-edge:#082742;
  --rivet:#3d6f9e; --paper:#eaf4fb; --paper-dim:#a9c6e0; --ash:#5c83a7;
  --ember:#FFCB93; --ember-hi:#FFE9A3; --patina:#41A4E0; --crack:#e63b2e;
  --h6:#FFCB93;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
a{color:var(--ember-hi);text-decoration:none}
.wrap{max-width:900px;margin:0 auto;padding:0 18px}

.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:900px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

.shead{display:flex;align-items:baseline;gap:14px;margin:20px 0 14px}
.shead h1{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

.btn{font-family:var(--mono);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:8px 16px;border:2px solid #000;cursor:pointer;display:inline-block}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:5px 10px;font-size:.64rem}

.alert-list{border:2px solid #000}
.alert-item{display:flex;align-items:flex-start;gap:14px;padding:13px 16px;border-bottom:1px solid var(--iron-hi);background:var(--iron-plate);transition:background .12s}
.alert-item:last-child{border-bottom:none}
.alert-item:hover{background:var(--iron-hi)}
.alert-item.unread{border-left:4px solid var(--ember)}
.alert-item .al-icon{width:36px;height:36px;flex:0 0 auto;display:flex;align-items:center;justify-content:center;font-size:1.1rem;border:2px solid #000}
.alert-item .al-body{flex:1;min-width:0}
.alert-item .al-title{font-weight:600;font-size:.88rem;color:var(--paper);margin-bottom:3px}
.alert-item .al-text{font-size:.78rem;color:var(--paper-dim);line-height:1.45}
.alert-item .al-time{font-family:var(--mono);font-size:.58rem;color:var(--ash);margin-top:4px}
.alert-item .al-action{flex:0 0 auto}

.empty-state{text-align:center;padding:48px 20px;color:var(--paper-dim)}
.empty-state .big{font-family:var(--disp);font-weight:800;font-size:1.6rem;text-transform:uppercase;color:var(--paper);margin-bottom:8px}
.empty-state p{font-family:var(--mono);font-size:.72rem}

.bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.bar .count{font-family:var(--mono);font-size:.62rem;color:var(--ash);text-transform:uppercase}

.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <b>Alertas</b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1>Alertas</h1>
    <span class="code">// centro de notificaciones</span>
    <span class="rule"></span>
  </div>

  <?php if (empty($alertas)): ?>
    <div class="empty-state">
      <div class="big">No tienes alertas</div>
      <p>Aquí aparecerán mensajes nuevos, aprobaciones y otras notificaciones.</p>
    </div>
  <?php else: ?>
    <div class="bar">
      <span class="count"><?php echo count($alertas); ?> alerta(s)</span>
      <form method="post" style="display:inline">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <button type="submit" name="mark_all" value="1" class="btn btn-ghost btn-sm">Marcar todas como leídas</button>
      </form>
    </div>

    <div class="alert-list">
      <?php foreach ($alertas as $al): 
        $icon = $tipo_iconos[$al['tipo']] ?? '●';
        $color = $tipo_colores[$al['tipo']] ?? 'var(--rivet)';
        $unread = !(int)$al['leido'];
      ?>
        <div class="alert-item<?php echo $unread ? ' unread' : ''; ?>">
          <div class="al-icon" style="color:<?php echo $color; ?>"><?php echo $icon; ?></div>
          <div class="al-body">
            <div class="al-title"><?php echo htmlspecialchars_uni($al['titulo']); ?></div>
            <div class="al-text"><?php echo htmlspecialchars_uni($al['cuerpo']); ?></div>
            <div class="al-time"><?php echo date('d/m/Y H:i', (int)$al['dateline']); ?></div>
          </div>
          <div class="al-action">
            <?php if (!empty($al['link'])): ?>
              <a href="<?php echo htmlspecialchars_uni($al['link']); ?>" class="btn btn-ghost btn-sm">Ver</a>
            <?php endif; ?>
            <?php if ($unread): ?>
              <form method="post" style="display:inline;margin-left:4px">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <button type="submit" name="mark_one" value="<?php echo (int)$al['aid']; ?>" class="btn btn-ghost btn-sm" style="font-size:.56rem;padding:4px 7px">✓</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
```

- [ ] **Step 2: Verificar**

Navegar a `http://localhost/iforge/alertas.php` y verificar:
- Lista de alertas con iconos por tipo
- Botón "Marcar todas como leídas" funcional
- Botón "✓" para marcar individual
- Links "Ver" que redirigen al recurso

- [ ] **Step 3: Commit**

```bash
git add alertas.php
git commit -m "feat: página de alertas con marcar leídas y navegación"
```

---

### Task 7: Actualizar `zona-staff.php` — enlace a nueva página de revisión

**Files:**
- Modify: `zona-staff.php:55-58`

**Interfaces:**
- Consumes: `revisar-personaje.php` (Task 4)
- Produces: STF-01 apunta a la nueva página de revisión + contador de pendientes

- [ ] **Step 1: Cambiar enlace y añadir contador de pendientes**

Reemplazar la definición de STF-01 (líneas 55-58):

```php
// Contar pendientes
$pendientes_count = 0;
if ($db->table_exists('rol_personajes')) {
    $pc = $db->simple_select('rol_personajes', 'COUNT(*) as cnt', "estado = 'revision'");
    $pendientes_count = (int)$db->fetch_field($pc, 'cnt');
}

$zonas = array(
    1 => array(
        array(
            'title'   => 'Aprobación de expedientes',
            'code'    => 'STF-01',
            'desc'    => 'Revisa las fichas de personajes nuevos pendientes de aprobación. Lee stats, historia, virtudes y decide.',
            'link'    => 'revisar-personaje.php',
            'cta'     => 'Revisar pendientes',
            'meta'    => $pendientes_count . ' pendiente(s)',
            'tag_col' => 'var(--h6)',
            'tag_txt' => 'Narrador',
        ),
        // ... resto de zonas igual
    ),
);
```

- [ ] **Step 2: Añadir también enlace directo a la cola en la intro de zona-staff**

Añadir después de la línea del párrafo `.zs-intro`:

```php
<?php if ($pendientes_count > 0): ?>
  <div style="margin-bottom:14px;padding:12px 16px;border:2px solid var(--ember);background:var(--iron-plate);display:flex;align-items:center;justify-content:space-between;gap:12px">
    <span style="font-family:var(--mono);font-size:.68rem;color:var(--ember-hi)"><b style="color:var(--ember)"><?php echo $pendientes_count; ?></b> expediente(s) pendiente(s) de revisión</span>
    <a href="<?php echo $bburl; ?>/revisar-personaje.php" class="btn btn-hot btn-sm">Revisar ahora</a>
  </div>
<?php endif; ?>
```

- [ ] **Step 3: Verificar**

Navegar a `http://localhost/iforge/zona-staff.php` y verificar:
- STF-01 enlaza a `revisar-personaje.php`
- Muestra contador de pendientes
- Si hay pendientes, banner destacado arriba con botón directo

- [ ] **Step 4: Commit**

```bash
git add zona-staff.php
git commit -m "feat: actualizar zona-staff con enlace a revisar-personaje.php y contador de pendientes"
```

---

---

### Task 8: Botón "Editar ficha" en `personajes.php` para personajes moderados

**Files:**
- Modify: `personajes.php`

**Interfaces:**
- Consumes: `rol_mensajes` (detectar si hay mensaje de moderación), `rol_personajes.estado`
- Produces: Botón "Editar ficha" visible cuando el personaje está en revisión y tiene mensajes de moderación pendientes

- [ ] **Step 1: Añadir detección de personajes moderados**

En `personajes.php`, después de cargar `$personajes` (línea ~156), añadir:

```php
// Detectar personajes con moderación pendiente (tienen MD sin leer del staff)
$personajes_moderados = array();
if ($loggedin && $db->table_exists('rol_mensajes')) {
    foreach ($personajes as $pj) {
        if ($pj['estado'] === 'revision') {
            $pid_i = (int)$pj['pid'];
            $mc = $db->query("
                SELECT COUNT(*) as cnt FROM " . TABLE_PREFIX . "rol_mensajes
                WHERE destino_pid = {$pid_i} AND leido = 0
                AND asunto LIKE 'Moderación:%'
            ");
            if ((int)$db->fetch_field($mc, 'cnt') > 0) {
                $personajes_moderados[$pid_i] = true;
            }
        }
    }
}
```

- [ ] **Step 2: Añadir botón "Editar ficha" en la tarjeta del personaje**

En el bucle que renderiza cada `pjcard` (línea ~430), dentro del `.pjcard-foot`, cuando el personaje está en `revision` y tiene moderación:

```php
<?php if ($pj['estado'] === 'revision' && isset($personajes_moderados[(int)$pj['pid']])): ?>
  <a href="<?php echo $bburl; ?>/crear-personaje.php?editar=<?php echo (int)$pj['pid']; ?>" class="btn btn-hot btn-sm">Editar ficha</a>
  <span class="pjcard-sub" style="color:var(--h6);font-family:var(--mono);font-size:.6rem">Cambios solicitados por el staff</span>
<?php endif; ?>
```

- [ ] **Step 3: Añadir soporte `?editar=PID` en `crear-personaje.php`**

En `crear-personaje.php`, después de cargar al usuario pero antes del render del wizard, detectar el parámetro `editar`:

```php
$editando_pid = (int)($mybb->get_input('editar', MyBB::INPUT_INT));
$editando = null;
if ($editando_pid > 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $eq = $db->simple_select('rol_personajes', '*', "pid = {$editando_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($eq)) {
        $editando = $db->fetch_array($eq);
        // Marcar mensajes de moderación como leídos
        if ($db->table_exists('rol_mensajes')) {
            $db->update_query('rol_mensajes', array('leido' => 1), "destino_pid = {$editando_pid} AND asunto LIKE 'Moderación:%'");
        }
    }
}
```

Luego pasar los datos de `$editando` para pre-rellenar el formulario (usando `$old` de respaldo):

```php
if ($editando && !$mybb->request_method === 'post') {
    $datos_edit = $editando['datos'] ? json_decode($editando['datos'], true) : array();
    // Pre-rellenar $old con los datos existentes
    $old = array(
        'nombre' => $editando['nombre'],
        'raza_principal' => $datos_edit['raza_principal'] ?? '',
        'raza_secundaria' => $datos_edit['raza_secundaria'] ?? '',
        'hibrido' => !empty($datos_edit['hibrido']),
        'faccion' => $datos_edit['faccion'] ?? '',
        // ... resto de campos
    );
}
```

Y al hacer submit con `editar`:
```php
// Si estamos editando, actualizar en vez de insertar
if ($editando_pid > 0 && $ok) {
    $db->update_query('rol_personajes', array(/* mismos campos */), "pid = {$editando_pid}");
    // Volver a poner en revision
    $db->update_query('rol_personajes', array('estado' => 'revision', 'lastedit' => TIME_NOW), "pid = {$editando_pid}");
}
```

- [ ] **Step 4: Commit**

```bash
git add personajes.php crear-personaje.php
git commit -m "feat: botón editar ficha para personajes moderados, pre-relleno del wizard"
```

---

## Resumen de Archivos

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| `scripts/migrate-mensajes-alertas.php` | CREAR | Migración: tablas `rol_mensajes` + `rol_alertas` |
| `inc/plugins/iforge_rol.php` | MODIFICAR | Campana alertas + enlace mensajes en navbar |
| `personajes.php` | MODIFICAR | Quitar panel aprobación staff, mejorar flag estado, botón Editar ficha |
| `crear-personaje.php` | MODIFICAR | Soporte `?editar=PID` para pre-rellenar wizard |
| `revisar-personaje.php` | CREAR | Vista detallada de ficha + Aprobar/Moderar/Rechazar |
| `mensajes.php` | CREAR | Bandeja MD con hilos, lectura y respuesta |
| `alertas.php` | CREAR | Centro de notificaciones con marcar leídas |
| `zona-staff.php` | MODIFICAR | STF-01 → `revisar-personaje.php` + contador |

## Orden de Ejecución

1. Task 1 (BD primero — las tablas deben existir antes de usarlas)
2. Task 2 (Navbar — para que campana y menú funcionen)
3. Task 3 (personajes.php — quitar aprobación vieja)
4. Task 4 (revisar-personaje.php — nueva página de revisión)
5. Task 5 (mensajes.php — sistema de mensajería)
6. Task 6 (alertas.php — página de alertas)
7. Task 7 (zona-staff.php — actualizar enlace final)
8. Task 8 (personajes.php + crear-personaje.php — editar ficha)
