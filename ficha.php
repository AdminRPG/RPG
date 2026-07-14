<?php
/**
 * I-Forge · Ficha de personaje ("Placa forjada")
 * ----------------------------------------------
 * Muestra el expediente real de un personaje (mybb_rol_personajes), leyendo
 * los datos guardados por el wizard crear-personaje.php. Dirección visual
 * "One Piece Eternal", coherente con personajes.php.
 *
 * Acceso:
 *   ficha.php?pid=N   → ficha del personaje N
 *   ficha.php         → ficha del personaje ACTIVO del usuario autenticado
 *
 * Visibilidad:
 *   - Los expedientes APROBADOS son públicos.
 *   - El dueño ve siempre los suyos (aunque estén en revisión/rechazados).
 *   - El staff ve cualquiera.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'ficha.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

// Capacidad del inventario que se lleva "encima" (nº de slots de la mochila).
define('OPE_INV_CAP', 12);

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Staff del PERSONAJE ACTIVO (el staff es por personaje). Un colaborador+ puede
// ver expedientes no aprobados; con un personaje sin rol activo, no.
$staff_arr   = $loggedin ? ope_rol_active_staff($uid) : array('rank' => 0);
$staff_level = (int) $staff_arr['rank'];

// Iniciales para el botón de usuario (navbar).
$display_name = (string) ($mybb->user['ope_display_name'] ?? ($mybb->user['username'] ?? ''));
$display_name_e = htmlspecialchars_uni($display_name);

// ── Resolver el personaje a mostrar ──
$pid = $mybb->get_input('pid', MyBB::INPUT_INT);
if ($pid < 1 && $loggedin) {
    // Personaje activo del usuario.
    if ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $pid = (int) $db->fetch_field($cq, 'personaje_activo');
        }
    }
}

$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

// ── Control de acceso ──
$puede_ver = false;
if ($pj) {
    if ($pj['estado'] === 'eliminado') {
        $puede_ver = $loggedin && ($staff_level >= 1 || (int) $pj['uid'] === $uid);
    } elseif ($pj['estado'] === 'aprobado') {
        $puede_ver = true;
    } elseif ($loggedin && ((int) $pj['uid'] === $uid || $staff_level >= 1)) {
        $puede_ver = true;
    }
}
// Gestión permitida SOLO si el visitante tiene ESTE personaje como ACTIVO
// (el activo siempre es propio, así que esto ya implica propiedad). Gobierna
// tanto el botón/modal de Gestión como la autorización de todos los POST.
$puede_gestionar = $pj && $loggedin && (int) ($mybb->user['ope_active_pid'] ?? 0) === (int) $pj['pid'];

// ── Gestión (propietario): guardar Avatar / Icono / Firma ──
$gestion_ok = ($mybb->get_input('g') === '1');
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'perfil') {

    $valid_url = static function ($u) {
        if ($u === '') return true;
        $parsed = parse_url($u);
        if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['scheme'])) return false;
        $scheme = strtolower($parsed['scheme']);
        $host   = strtolower($parsed['host']);
        if (!in_array($scheme, array('http', 'https'), true)) return false;
        if ($host === 'localhost' || $host === '0.0.0.0') return false;
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = ip2long($host);
            if ($ip === false) return false;
            if ($ip === ip2long('127.0.0.1')) return false;
            $long = sprintf('%u', $ip);
            if ($long >= ip2long('10.0.0.0') && $long <= ip2long('10.255.255.255')) return false;
            if ($long >= ip2long('172.16.0.0') && $long <= ip2long('172.31.255.255')) return false;
            if ($long >= ip2long('192.168.0.0') && $long <= ip2long('192.168.255.255')) return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipv6 = inet_pton($host);
            if ($ipv6 === false) return false;
            if ($ipv6 === inet_pton('::1')) return false;
        }
        return true;
    };

    $n_avatar = trim((string) $mybb->get_input('avatar'));
    $n_icono  = trim((string) $mybb->get_input('icono'));
    $n_firma  = (string) $mybb->get_input('firma');
    if (!$valid_url($n_avatar)) $n_avatar = (string) $pj['avatar'];
    if (!$valid_url($n_icono))  $n_icono  = (string) ($pj['icono'] ?? '');
    $n_firma = function_exists('mb_substr') ? mb_substr($n_firma, 0, 3000) : substr($n_firma, 0, 3000);

    $db->update_query('rol_personajes', array(
        'avatar'        => $db->escape_string($n_avatar),
        'icono'         => $db->escape_string($n_icono),
        'firma'         => $db->escape_string($n_firma),
        'lastedit'      => TIME_NOW,
    ), 'pid = ' . (int) $pj['pid']);

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1');
    exit;
}

// ── Gestión (propietario): guardar descripciones de cronología ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'cronologia'
    && $db->table_exists('rol_cronologia')) {

    $pid_c   = (int) $pj['pid'];
    $descs   = $mybb->get_input('descripcion', MyBB::INPUT_ARRAY);
    if (is_array($descs)) {
        // TIDs válidos: temas donde el personaje realmente participó.
        $valid = array();
        $vq = $db->query("SELECT DISTINCT tid FROM " . TABLE_PREFIX . "posts
                          WHERE ope_pid = {$pid_c} AND visible = 1");
        while ($vr = $db->fetch_array($vq)) { $valid[(int) $vr['tid']] = true; }

        foreach ($descs as $tid_k => $txt) {
            $tid_k = (int) $tid_k;
            if ($tid_k < 1 || empty($valid[$tid_k])) continue;
            $txt = trim((string) $txt);
            $txt = function_exists('mb_substr') ? mb_substr($txt, 0, 2000) : substr($txt, 0, 2000);

            $ex = $db->simple_select('rol_cronologia', 'tid',
                "pid = {$pid_c} AND tid = {$tid_k}", array('limit' => 1));
            if ($txt === '') {
                if ($db->num_rows($ex)) {
                    $db->delete_query('rol_cronologia', "pid = {$pid_c} AND tid = {$tid_k}");
                }
                continue;
            }
            $row = array(
                'descripcion' => $db->escape_string($txt),
                'dateline'    => TIME_NOW,
            );
            if ($db->num_rows($ex)) {
                $db->update_query('rol_cronologia', $row, "pid = {$pid_c} AND tid = {$tid_k}");
            } else {
                $row['pid'] = $pid_c;
                $row['tid'] = $tid_k;
                $db->insert_query('rol_cronologia', $row);
            }
        }
    }

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1#cronologia');
    exit;
}

// ── Gestión (propietario): mapa de relaciones (añadir/editar/borrar/posición) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('rel_add', 'rel_edit', 'rel_del', 'rel_pos'), true)
    && $db->table_exists('rol_relaciones')) {

    $pid_r    = (int) $pj['pid'];
    $gaccion  = $mybb->get_input('gaccion');
    $tipos_ok = array_keys(ope_rel_tipos());

    if ($gaccion === 'rel_add') {
        $destino = $mybb->get_input('destino_pid', MyBB::INPUT_INT);
        $etq     = trim((string) $mybb->get_input('etiqueta'));
        $tipo    = (string) $mybb->get_input('tipo');
        $desc    = trim((string) $mybb->get_input('descripcion'));
        if (!in_array($tipo, $tipos_ok, true)) $tipo = 'otro';
        $etq  = function_exists('mb_substr') ? mb_substr($etq, 0, 120) : substr($etq, 0, 120);
        $desc = function_exists('mb_substr') ? mb_substr($desc, 0, 1500) : substr($desc, 0, 1500);

        // Destino: personaje aprobado, distinto de sí mismo, sin duplicar.
        $ok = false;
        if ($destino > 0 && $destino !== $pid_r) {
            $dq = $db->simple_select('rol_personajes', 'pid',
                "pid = {$destino} AND estado = 'aprobado'", array('limit' => 1));
            if ($db->num_rows($dq)) {
                $ex = $db->simple_select('rol_relaciones', 'rid',
                    "pid = {$pid_r} AND destino_pid = {$destino}", array('limit' => 1));
                if (!$db->num_rows($ex)) $ok = true;
            }
        }
        if ($ok) {
            $db->insert_query('rol_relaciones', array(
                'pid'         => $pid_r,
                'destino_pid' => $destino,
                'etiqueta'    => $db->escape_string($etq),
                'tipo'        => $db->escape_string($tipo),
                'descripcion' => $db->escape_string($desc),
                'px'          => 0,
                'py'          => 0,
                'dateline'    => TIME_NOW,
            ));
        }
    } elseif ($gaccion === 'rel_edit') {
        $rid  = $mybb->get_input('rid', MyBB::INPUT_INT);
        $etq  = trim((string) $mybb->get_input('etiqueta'));
        $tipo = (string) $mybb->get_input('tipo');
        $desc = trim((string) $mybb->get_input('descripcion'));
        if (!in_array($tipo, $tipos_ok, true)) $tipo = 'otro';
        $etq  = function_exists('mb_substr') ? mb_substr($etq, 0, 120) : substr($etq, 0, 120);
        $desc = function_exists('mb_substr') ? mb_substr($desc, 0, 1500) : substr($desc, 0, 1500);
        if ($rid > 0) {
            $db->update_query('rol_relaciones', array(
                'etiqueta'    => $db->escape_string($etq),
                'tipo'        => $db->escape_string($tipo),
                'descripcion' => $db->escape_string($desc),
            ), "rid = {$rid} AND pid = {$pid_r}");
        }
    } elseif ($gaccion === 'rel_del') {
        $rid = $mybb->get_input('rid', MyBB::INPUT_INT);
        if ($rid > 0) {
            $db->delete_query('rol_relaciones', "rid = {$rid} AND pid = {$pid_r}");
        }
    } elseif ($gaccion === 'rel_pos') {
        $pxs = $mybb->get_input('px', MyBB::INPUT_ARRAY);
        $pys = $mybb->get_input('py', MyBB::INPUT_ARRAY);
        if (is_array($pxs)) {
            foreach ($pxs as $rid_k => $vx) {
                $rid_k = (int) $rid_k;
                if ($rid_k < 1) continue;
                $vx = (int) $vx;
                $vy = isset($pys[$rid_k]) ? (int) $pys[$rid_k] : 0;
                // Clamp al lienzo (viewBox 0..1000 x 0..640).
                if ($vx < 0) $vx = 0; if ($vx > 1000) $vx = 1000;
                if ($vy < 0) $vy = 0; if ($vy > 640)  $vy = 640;
                $db->update_query('rol_relaciones',
                    array('px' => $vx, 'py' => $vy),
                    "rid = {$rid_k} AND pid = {$pid_r}");
            }
        }
    }

    // rel_pos vuelve al mapa; el resto reabre el modal en la pestaña de relaciones.
    if ($gaccion === 'rel_pos') {
        header('Location: ' . $bburl . '/ficha.php?pid=' . $pid_r . '#relaciones-map');
    } else {
        header('Location: ' . $bburl . '/ficha.php?pid=' . $pid_r . '&g=1#relaciones');
    }
    exit;
}

// ── Gestión (propietario): plantillas de post (crear/editar/borrar) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('tpl_add', 'tpl_edit', 'tpl_del'), true)
    && $db->table_exists('rol_post_templates')) {

    $pid_t   = (int) $pj['pid'];
    $gaccion = $mybb->get_input('gaccion');

    if ($gaccion === 'tpl_del') {
        $tpl_id = $mybb->get_input('tpl_id', MyBB::INPUT_INT);
        if ($tpl_id > 0) {
            $db->delete_query('rol_post_templates', "tpl_id = {$tpl_id} AND pid = {$pid_t}");
        }
    } else {
        $nombre = trim((string) $mybb->get_input('nombre'));
        $cuerpo = (string) $mybb->get_input('cuerpo');
        $nombre = function_exists('mb_substr') ? mb_substr($nombre, 0, 120) : substr($nombre, 0, 120);
        $cuerpo = function_exists('mb_substr') ? mb_substr($cuerpo, 0, 20000) : substr($cuerpo, 0, 20000);
        if ($nombre === '') $nombre = 'Plantilla';

        if ($gaccion === 'tpl_add') {
            // disporder = último + 1
            $ord = 0;
            $oq = $db->simple_select('rol_post_templates', 'MAX(disporder) AS mx', "pid = {$pid_t}");
            if ($db->num_rows($oq)) $ord = (int) $db->fetch_field($oq, 'mx') + 1;
            $db->insert_query('rol_post_templates', array(
                'pid'      => $pid_t,
                'nombre'   => $db->escape_string($nombre),
                'cuerpo'   => $db->escape_string($cuerpo),
                'disporder'=> $ord,
                'dateline' => TIME_NOW,
            ));
        } else { // tpl_edit
            $tpl_id = $mybb->get_input('tpl_id', MyBB::INPUT_INT);
            if ($tpl_id > 0) {
                $db->update_query('rol_post_templates', array(
                    'nombre' => $db->escape_string($nombre),
                    'cuerpo' => $db->escape_string($cuerpo),
                ), "tpl_id = {$tpl_id} AND pid = {$pid_t}");
            }
        }
    }

    header('Location: ' . $bburl . '/ficha.php?pid=' . $pid_t . '&g=1#templates');
    exit;
}

// ── Gestión (propietario): atributos (stats efectivas) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'attrs') {

    $datos_j = json_decode((string) $pj['datos'], true);
    if (!is_array($datos_j)) $datos_j = array();
    $stats = is_array($datos_j['stats_efectivas'] ?? null) ? $datos_j['stats_efectivas'] : array();

    $in = $mybb->get_input('attr', MyBB::INPUT_ARRAY);
    if (is_array($in)) {
        foreach (ope_rol_stat_keys() as $k) {
            if (!array_key_exists($k, $in)) continue;
            $v = (int) $in[$k];
            if ($v < 1) $v = 1;
            if ($v > 10) $v = 10;
            $stats[$k] = $v;
        }
    }
    $datos_j['stats_efectivas'] = $stats;
    $datos_j['rango_suma']      = array_sum(array_map('intval', $stats));

    $db->update_query('rol_personajes', array(
        'datos'    => $db->escape_string(json_encode($datos_j, JSON_UNESCAPED_UNICODE)),
        'lastedit' => TIME_NOW,
    ), 'pid = ' . (int) $pj['pid']);

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1#atributos');
    exit;
}

// ── Gestión (propietario): equipo (encima / almacén) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('equip_move'), true)) {

    $inv = json_decode((string) $pj['inventario'], true);
    if (!is_array($inv)) $inv = array();
    if (!isset($inv['encima']) || !is_array($inv['encima']))   $inv['encima']  = array();
    if (!isset($inv['almacen']) || !is_array($inv['almacen'])) $inv['almacen'] = array();

    $gaccion = $mybb->get_input('gaccion');
    $valid_loc = static function ($l) { return $l === 'encima' || $l === 'almacen'; };
    // Suma de slots ocupados en un contenedor (respeta el tamaño de cada objeto).
    $inv_used = static function (array $list): int {
        $u = 0;
        foreach ($list as $it) {
            $s = is_array($it) ? (int) ($it['size'] ?? 1) : 1;
            $u += ($s < 1 ? 1 : $s);
        }
        return $u;
    };

    if ($gaccion === 'equip_move') {
        $from = (string) $mybb->get_input('from');
        $idx  = $mybb->get_input('idx', MyBB::INPUT_INT);
        if ($valid_loc($from) && isset($inv[$from][$idx])) {
            $to = $from === 'encima' ? 'almacen' : 'encima';
            $item = $inv[$from][$idx];
            $isz  = is_array($item) ? (int) ($item['size'] ?? 1) : 1;
            if ($isz < 1) $isz = 1;
            // Al pasar al inventario "encima" hay que respetar la capacidad de slots.
            if ($to === 'encima' && $inv_used($inv['encima']) + $isz > OPE_INV_CAP) {
                // No cabe: no se mueve (se queda en el almacén).
            } else {
                array_splice($inv[$from], $idx, 1);
                $inv[$to][] = $item;
            }
        }
    }

    // Reindexa para evitar huecos en los arrays.
    $inv['encima']  = array_values($inv['encima']);
    $inv['almacen'] = array_values($inv['almacen']);

    $db->update_query('rol_personajes', array(
        'inventario' => $db->escape_string(json_encode($inv, JSON_UNESCAPED_UNICODE)),
        'lastedit'   => TIME_NOW,
    ), 'pid = ' . (int) $pj['pid']);

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1#equipo');
    exit;
}

// ── Datos de rol ──
$STAT_GROUPS = ope_rol_stats();
$RAZAS       = ope_rol_razas();
$FACCIONES   = ope_rol_facciones();
$ARMAS       = ope_rol_armas();
$PACKS       = ope_rol_packs_equipo();

/** Tipos de relación válidos (slug => etiqueta visible). */
function ope_rel_tipos(): array
{
    return array(
        'aliado'      => 'Aliado',
        'rival'       => 'Rival',
        'familia'     => 'Familia',
        'romance'     => 'Romance',
        'mentor'      => 'Mentor',
        'tripulacion' => 'Tripulaci&oacute;n',
        'enemigo'     => 'Enemigo',
        'otro'        => 'Otro',
    );
}

function ope_heat_var(string $rango): string
{
    $map = array(
        'F' => '--h1', 'E' => '--h1', 'D' => '--h2', 'C' => '--h3', 'B' => '--h4',
        'A' => '--h5', 'S' => '--h6', 'SS' => '--h7', 'M' => '--h8', 'M+' => '--h9',
    );
    return $map[strtoupper(trim($rango))] ?? '--h1';
}
function ope_heat_val(int $v): string
{
    if ($v < 1) $v = 1;
    if ($v > 9) $v = 9;
    return '--h' . $v;
}
/** Formato corto para cifras grandes de berries (4.850.000 → "4.9M"). */
function ope_short_money(int $n): string
{
    $abs = abs($n);
    if ($abs >= 1000000) {
        return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    }
    if ($abs >= 1000) {
        return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    }
    return number_format($n, 0, ',', '.');
}

// Decodifica los JSON del personaje.
$datos      = $pj ? (json_decode((string) $pj['datos'], true) ?: array()) : array();
$inventario = $pj ? (json_decode((string) $pj['inventario'], true) ?: array()) : array();
$economia   = $pj ? (json_decode((string) $pj['economia'], true) ?: array()) : array();
$bio        = $pj ? (json_decode((string) $pj['bio'], true) ?: array()) : array();

// Color por facción: slug canónico para teñir el expediente (fuente: plugin ope_rol).
$fac_slug  = $pj ? ope_rol_faccion_slug($datos['faccion'] ?? '') : '';
$fac_class = $fac_slug !== '' ? ' fac-' . $fac_slug : '';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; <?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Ficha'; ?></title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-ficha) -->
</head>
<body class="ope-pg-ficha<?php echo $fac_class; ?>">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b><?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Ficha'; ?></b>
  </div>
</div>

<div class="wrap">
<?php if (!$pj || !$puede_ver):
    // ── Estado vacío / sin permiso ──
?>
  <div class="pj-empty">
    <div class="big"><?php echo $pj ? 'Expediente no disponible' : 'Expediente no encontrado'; ?></div>
    <p>
<?php if (!$pj): ?>
      No hay ning&uacute;n personaje con ese identificador. Puede que a&uacute;n no lo hayas creado o que el enlace sea incorrecto.
<?php else: ?>
      Este expediente est&aacute; en revisi&oacute;n o no es p&uacute;blico. Solo su due&ntilde;o y el staff pueden consultarlo.
<?php endif; ?>
    </p>
    <div class="acts">
      <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Mis personajes</a>
      <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
    </div>
  </div>
<?php else:
    // ── Datos derivados para el render ──
    $nombre_e   = htmlspecialchars_uni($pj['nombre']);
    $rango      = (string) $pj['rango'];
    $rango_e    = htmlspecialchars_uni($rango);
    $heat_rank  = ope_heat_var($rango);
    $nivel      = (int) $pj['nivel'];
    $avatar     = trim((string) $pj['avatar']);
    $av_initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));

    $raza1_key = $datos['raza_principal'] ?? '';
    $raza2_key = $datos['raza_secundaria'] ?? '';
    $hibrido   = !empty($datos['hibrido']);
    $raza1_lbl = isset($RAZAS[$raza1_key]) ? $RAZAS[$raza1_key]['nombre'] : ucfirst((string) $raza1_key);
    $raza2_lbl = ($raza2_key && isset($RAZAS[$raza2_key])) ? $RAZAS[$raza2_key]['nombre'] : '';
    $raza_full = $hibrido && $raza2_lbl !== '' ? ($raza1_lbl . ' / ' . $raza2_lbl) : $raza1_lbl;

    $faccion_key = $datos['faccion'] ?? '';
    $faccion_lbl = isset($FACCIONES[$faccion_key]) ? $FACCIONES[$faccion_key]['nombre'] : ucfirst((string) $faccion_key);

    // Campos añadidos en el rediseño (columnas nuevas de rol_personajes).
    $rango_faccion = trim((string) ($pj['rango_faccion'] ?? ''));
    $from_fisico   = trim((string) ($pj['from_fisico'] ?? ''));
    $desc_fisica   = trim((string) ($pj['desc_fisica'] ?? ''));
    $personalidad  = trim((string) ($pj['personalidad'] ?? ''));

    // Estado del personaje resumido a la dupla Aprobado / Pendiente para el tag.
    $es_aprobado   = ((string) $pj['estado'] === 'aprobado');
    $estado_tag    = $es_aprobado ? 'Aprobado' : 'Pendiente';

    // Rol en el foro (staff): solo se muestra si el personaje es staff.
    $staff_rol     = (string) ($pj['staff_rol'] ?? '');
    $staff_rol_lbl = function_exists('ope_rol_staff_label') ? ope_rol_staff_label($staff_rol) : '';

    $edad   = $datos['edad'] ?? '';
    $genero = $datos['genero'] ?? '';
    $apodo  = $datos['apodo'] ?? '';

    $stats_ef = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
    $suma     = (int) ($datos['rango_suma'] ?? array_sum($stats_ef));

    $virtudes = is_array($datos['virtudes'] ?? null) ? $datos['virtudes'] : array();
    $defectos = is_array($datos['defectos'] ?? null) ? $datos['defectos'] : array();
    $pc_bal   = (int) ($datos['pc_balance'] ?? 0);
    $pc_gas   = (int) ($datos['pc_gastado'] ?? 0);
    $pc_dev   = (int) ($datos['pc_devuelto'] ?? 0);

    // Pack de Equipo Inicial (INI-01, Paso 6). Fichas viejas (previas a este
    // sistema) pueden seguir teniendo 'arma'/'objeto_personal' sueltos: se
    // muestran igualmente como texto libre para no perder esa información.
    $pack_key    = $inventario['pack_equipo'] ?? '';
    $pack_def    = isset($PACKS[$pack_key]) ? $PACKS[$pack_key] : null;
    $arma_legacy_key = trim((string) ($inventario['arma'] ?? ''));
    $arma_legacy = isset($ARMAS[$arma_legacy_key]) ? $ARMAS[$arma_legacy_key]['nombre'] : $arma_legacy_key;
    $obj_legacy  = trim((string) ($inventario['objeto_personal'] ?? ''));
    $berries     = (int) ($economia['berries'] ?? 0);

    $pp_disponible = 0;
    if ($puede_gestionar && function_exists('ope_pp_saldo')) {
        $pp_row = ope_pp_saldo((int) $pj['pid']);
        $pp_disponible = (int) ($pp_row['pp_disponible'] ?? 0);
    }
    $pv_max = (int) ($pj['pv_max'] ?? 0);
    $en_max = (int) ($pj['en_max'] ?? 0);
    $pa_turno = (int) ($pj['pa_por_turno'] ?? 0);
    if ($pv_max < 1 && function_exists('ope_combat_recalc')) {
        $vit = ope_combat_recalc((int) $pj['pid']);
        if ($vit) {
            $pv_max = (int) $vit['pv_max'];
            $en_max = (int) $vit['en_max'];
            $pa_turno = (int) $vit['pa_por_turno'];
            $rango = (string) ($vit['rango'] ?? $rango);
            $rango_e = htmlspecialchars_uni($rango);
        }
    }

    // Inventario libre: objetos "encima" y en "almacén" (defaults robustos).
    $norm_items = static function ($list) {
        $out = array();
        if (is_array($list)) {
            foreach ($list as $it) {
                if (is_array($it)) {
                    $n = trim((string) ($it['n'] ?? ''));
                    $d = trim((string) ($it['d'] ?? ''));
                    $s = (int) ($it['size'] ?? 1);
                } else {
                    $n = trim((string) $it);
                    $d = '';
                    $s = 1;
                }
                if ($s < 1) $s = 1; if ($s > OPE_INV_CAP) $s = OPE_INV_CAP;
                if ($n !== '') $out[] = array('n' => $n, 'd' => $d, 'size' => $s);
            }
        }
        return $out;
    };
    $inv_encima  = $norm_items($inventario['encima'] ?? null);
    $inv_almacen = $norm_items($inventario['almacen'] ?? null);

    // Medias por pilar (Cuerpo/Mente/Espíritu) para la pestaña Crisol.
    $group_calc = array();
    foreach ($STAT_GROUPS as $gkey => $grupo) {
        $vals = array();
        foreach ($grupo['stats'] as $ab => $nm) {
            $vals[] = (int) ($stats_ef[$ab] ?? 1);
        }
        $group_calc[$gkey] = array(
            'avg' => count($vals) ? array_sum($vals) / count($vals) : 1,
        );
    }

    // Pasivas raciales: la primaria de la raza principal siempre se aplica;
    // la secundaria de esa misma raza SOLO si el personaje es puro (no
    // híbrido). Si es híbrido, se suma la primaria de la raza secundaria
    // (regla usada por el propio wizard de creación: "un híbrido obtiene
    // SOLO las pasivas primarias de ambas razas, ninguna secundaria").
    $pasivas = array();
    if ($raza1_key !== '' && isset($RAZAS[$raza1_key])) {
        $r1 = $RAZAS[$raza1_key];
        $pasivas[] = array('tag' => 'Primaria · ' . $raza1_lbl, 'nombre' => $r1['primaria_nombre'], 'desc' => $r1['primaria_desc']);
        if ($hibrido) {
            if ($raza2_key !== '' && isset($RAZAS[$raza2_key])) {
                $r2 = $RAZAS[$raza2_key];
                $pasivas[] = array('tag' => 'Primaria · ' . $raza2_lbl, 'nombre' => $r2['primaria_nombre'], 'desc' => $r2['primaria_desc']);
            }
        } else {
            // Si el jugador eligió una sub-opción racial (Herencia Tribal del
            // Humano, Linaje Colosal del Gigante...), su pasiva sustituye a la
            // secundaria genérica de la raza (ver INI-01-creacion-de-personaje).
            $sub_key = (string) ($datos['sub_opcion_racial'] ?? '');
            $sub_def = (isset($r1['sub_opciones']) && $sub_key !== '' && isset($r1['sub_opciones'][$sub_key])) ? $r1['sub_opciones'][$sub_key] : null;
            if ($sub_def !== null) {
                $pasivas[] = array('tag' => 'Secundaria · ' . $raza1_lbl, 'nombre' => $sub_def['nombre'], 'desc' => $sub_def['desc']);
            } else {
                $pasivas[] = array('tag' => 'Secundaria · ' . $raza1_lbl, 'nombre' => $r1['secundaria_nombre'], 'desc' => $r1['secundaria_desc']);
            }
        }
    }

    // Deck de cartas de técnica (INI-03) del personaje.
    $deck_tecnicas = function_exists('ope_rol_char_tecnicas') ? ope_rol_char_tecnicas((int) $pj['pid']) : array();

    // Rasgos: virtudes y defectos combinados en una sola lista (.trait).
    $rasgos = array();
    foreach ($virtudes as $vid => $v) {
        $vdef = ope_rol_find_virtud($vid);
        $rasgos[] = array(
            'tipo'  => 'v',
            'nombre' => $v['nombre'] ?? $vid,
            'spec'   => trim((string) ($v['spec'] ?? '')),
            'desc'   => $vdef ? $vdef['desc'] : '',
            'badge'  => ((int) ($v['coste'] ?? 0)) . ' PC',
        );
    }
    foreach ($defectos as $did => $d) {
        $ddef = ope_rol_find_defecto($did);
        $rasgos[] = array(
            'tipo'  => 'x',
            'nombre' => $d['nombre'] ?? $did,
            'spec'   => trim((string) ($d['spec'] ?? '')),
            'desc'   => $ddef ? $ddef['desc'] : '',
            'badge'  => '+' . ((int) ($d['devuelve'] ?? 0)),
        );
    }

    // Cronología: solo eventos reales (forjado + última edición si difiere).
    $timeline = array();
    $timeline[] = array('t' => 'Creado', 'd' => my_date('d M Y', (int) $pj['dateline']));
    $lastedit_ts = (int) ($pj['lastedit'] ?? 0);
    if ($lastedit_ts > 0 && $lastedit_ts !== (int) $pj['dateline']) {
        $timeline[] = array('t' => '&Uacute;ltima edici&oacute;n', 'd' => my_date('d M Y', $lastedit_ts));
    }

    // ── Línea de tiempo de ROL: temas donde participó el personaje ──
    // (posts.ope_pid = pid), agrupados por AÑO in-rol, excluyendo Off Topic.
    $TAG_LABELS   = function_exists('ope_rol_thread_tags') ? ope_rol_thread_tags() : array();
    $cron_years   = array();   // año => [entradas]  (para la timeline pública)
    $cron_flat    = array();   // lista plana ordenada (para el modal de gestión)
    $pid_tl       = (int) $pj['pid'];
    $has_meta     = $db->table_exists('rol_thread_meta');
    $has_cron     = $db->table_exists('rol_cronologia');
    $pref         = TABLE_PREFIX;

    $sel  = "SELECT t.tid, t.subject, t.fid, t.dateline AS tdate";
    $sel .= $has_meta ? ", m.era, m.fecha_rol, m.tag" : ", NULL AS era, NULL AS fecha_rol, '' AS tag";
    $sel .= $has_cron ? ", c.descripcion" : ", NULL AS descripcion";
    $from  = " FROM {$pref}posts p INNER JOIN {$pref}threads t ON t.tid = p.tid";
    if ($has_meta) $from .= " LEFT JOIN {$pref}rol_thread_meta m ON m.tid = t.tid";
    if ($has_cron) $from .= " LEFT JOIN {$pref}rol_cronologia c ON c.tid = t.tid AND c.pid = {$pid_tl}";
    $where = " WHERE p.ope_pid = {$pid_tl} AND p.visible = 1 AND t.visible = 1";
    $grp   = " GROUP BY t.tid, t.subject, t.fid, t.dateline";
    if ($has_meta) $grp .= ", m.era, m.fecha_rol, m.tag";
    if ($has_cron) $grp .= ", c.descripcion";

    $tq = $db->query($sel . $from . $where . $grp);
    while ($tr = $db->fetch_array($tq)) {
        $fid_t = (int) $tr['fid'];
        if (function_exists('ope_rol_is_offtopic_fid') && ope_rol_is_offtopic_fid($fid_t)) {
            continue;
        }
        $era  = ($tr['era'] === 'pasado') ? 'pasado' : (($tr['era'] === 'presente') ? 'presente' : '');
        $anio = (int) $tr['fecha_rol'];
        if ($anio <= 0) {
            // Temas sin metadata guardada (legado): calcula el año on-rol a partir
            // de la fecha real del post, en vez de mostrar el año gregoriano (2026).
            $anio = function_exists('ope_rol_onrol_calendar')
                ? (int) ope_rol_onrol_calendar((int) $tr['tdate'])['year']
                : (int) my_date('Y', (int) $tr['tdate']);
        }
        $entry = array(
            'tid'         => (int) $tr['tid'],
            'subject'     => (string) $tr['subject'],
            'era'         => $era,
            'anio'        => $anio,
            'tag'         => (string) $tr['tag'],
            'tdate'       => (int) $tr['tdate'],
            'descripcion' => (string) ($tr['descripcion'] ?? ''),
        );
        $cron_years[$anio][] = $entry;
        $cron_flat[] = $entry;
    }
    // Años de más reciente a más antiguo; dentro de cada año, por fecha real.
    krsort($cron_years);
    foreach ($cron_years as $yk => &$yarr) {
        usort($yarr, function ($a, $b) { return $a['tdate'] <=> $b['tdate']; });
    }
    unset($yarr);
    // Plano para el modal: por año desc y fecha asc.
    usort($cron_flat, function ($a, $b) {
        if ($a['anio'] !== $b['anio']) return $b['anio'] <=> $a['anio'];
        return $a['tdate'] <=> $b['tdate'];
    });

    // ── Mapa de relaciones: vínculos dirigidos desde este personaje ──
    $REL_TIPOS   = ope_rel_tipos();
    $relaciones  = array();
    $rel_pid     = (int) $pj['pid'];
    if ($db->table_exists('rol_relaciones')) {
        $rq = $db->simple_select('rol_relaciones', '*', "pid = {$rel_pid}", array('order_by' => 'dateline', 'order_dir' => 'asc'));
        while ($rr = $db->fetch_array($rq)) {
            $other = ope_rol_char((int) $rr['destino_pid']);
            if (!$other) continue; // destino borrado: se ignora
            $tipo = isset($REL_TIPOS[$rr['tipo']]) ? $rr['tipo'] : 'otro';
            // Mapa de relaciones: SIEMPRE el ICONO pequeño (fallback a inicial).
            $ico  = trim((string) ($other['icono'] ?? ''));
            $relaciones[] = array(
                'rid'      => (int) $rr['rid'],
                'dest_pid' => (int) $rr['destino_pid'],
                'nombre'   => (string) $other['nombre'],
                'icono'    => $ico,
                'inicial'  => function_exists('mb_substr') ? mb_strtoupper(mb_substr((string) $other['nombre'], 0, 1)) : strtoupper(substr((string) $other['nombre'], 0, 1)),
                'fac_slug' => (string) ($other['faccion_slug'] ?? ''),
                'etiqueta' => (string) $rr['etiqueta'],
                'tipo'     => $tipo,
                'tipo_lbl' => $REL_TIPOS[$tipo],
                'desc'     => (string) ($rr['descripcion'] ?? ''),
                'px'       => (int) $rr['px'],
                'py'       => (int) $rr['py'],
            );
        }
    }

    // Plantillas de post del personaje (para el editor del modal, solo propietario).
    $tpls_list = ($puede_gestionar && function_exists('ope_rol_char_templates'))
        ? ope_rol_char_templates((int) $pj['pid'])
        : array();

    // Personajes aprobados seleccionables como destino (excluye a sí mismo).
    $rel_choices = array();
    if ($puede_gestionar && $db->table_exists('rol_personajes')) {
        $cq = $db->simple_select('rol_personajes', 'pid, nombre',
            "estado = 'aprobado' AND pid <> {$rel_pid}", array('order_by' => 'nombre', 'order_dir' => 'asc'));
        while ($cr = $db->fetch_array($cq)) {
            $rel_choices[] = array('pid' => (int) $cr['pid'], 'nombre' => (string) $cr['nombre']);
        }
    }

    // Coordenadas de dibujo: usa px/py guardados; si están a 0, autodistribuye.
    $REL_CX = 500; $REL_CY = 320; $REL_R = 235;
    $rel_n = count($relaciones);
    foreach ($relaciones as $i => &$rl) {
        if ($rl['px'] > 0 || $rl['py'] > 0) {
            $rl['dx'] = $rl['px']; $rl['dy'] = $rl['py'];
        } else {
            $ang = ($rel_n > 0) ? (2 * M_PI * $i / $rel_n) - M_PI_2 : 0;
            $rl['dx'] = (int) round($REL_CX + $REL_R * cos($ang));
            $rl['dy'] = (int) round($REL_CY + $REL_R * sin($ang));
        }
    }
    unset($rl);

    // Crónica: solo se muestran los subtabs con contenido real.
    // 'relaciones' se muestra ahora en la pestaña Relaciones (como nota bajo el mapa).
    $bio_map = array(
        'concepto'   => 'Concepto',
        'pasado'     => 'Pasado',
        'motivacion' => 'Motivaci&oacute;n',
    );
    $bio_sections = array();
    foreach ($bio_map as $bkey => $blabel) {
        $btext = trim((string) ($bio[$bkey] ?? ''));
        if ($btext !== '') {
            $bio_sections[$bkey] = array('label' => $blabel, 'text' => $btext);
        }
    }
    // Subsecciones nuevas (columnas propias): Descripción física y Personalidad.
    // La descripción física antepone el "From:" (referencia del físico) si existe.
    if ($desc_fisica !== '' || $from_fisico !== '') {
        $bio_sections['desc_fisica'] = array(
            'label' => 'Descripci&oacute;n f&iacute;sica',
            'text'  => $desc_fisica,
            'from'  => $from_fisico,
        );
    }
    if ($personalidad !== '') {
        $bio_sections['personalidad'] = array('label' => 'Personalidad', 'text' => $personalidad);
    }

    list($est_lbl, $est_col) = (function ($e) {
        switch ($e) {
            case 'aprobado':  return array('Aprobado', 'var(--patina)');
            case 'revision':  return array('En revisi&oacute;n', 'var(--h6)');
            case 'rechazado': return array('Rechazado', 'var(--crack)');
            default:          return array('Borrador', 'var(--rivet)');
        }
    })((string) $pj['estado']);
?>

<div class="forge">
  <!-- COLUMNA RETRATO -->
  <div class="pcol">
    <div class="forge-portrait">
      <div class="fp-frame">
<?php if ($avatar !== ''): ?>
        <img class="fp-img" src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="<?php echo $nombre_e; ?>">
        <div class="fp-shade" aria-hidden="true"></div>
<?php else: ?>
        <div class="fp-glow" aria-hidden="true"></div>
        <span class="fp-initial"><?php echo htmlspecialchars_uni($av_initial); ?></span>
        <div class="fp-grid" aria-hidden="true"></div>
<?php endif; ?>
        <span class="fp-temper" aria-hidden="true"></span>
      </div>
    </div>
<?php if ($from_fisico !== ''): ?>
    <div class="fp-from"><span class="l">From:</span> <span class="v"><?php echo htmlspecialchars_uni($from_fisico); ?></span></div>
<?php endif; ?>
<?php if ($puede_gestionar): ?>
    <div class="under">
      <div class="acts">
        <a href="<?php echo $bburl; ?>/progresion.php" class="btn btn-hot">Progresi&oacute;n</a>
        <button type="button" class="btn btn-ghost" id="ope-gestion-open" aria-haspopup="dialog">Gesti&oacute;n</button>
      </div>
    </div>
<?php endif; ?>
  </div>

  <!-- COLUMNA CONTENIDO -->
  <div>
    <div class="idbanner">
      <div class="eyebrow">
        <span>Expediente N.&ordm; <?php echo str_pad((string) $pj['pid'], 5, '0', STR_PAD_LEFT); ?></span>
      </div>
      <h1><?php echo $nombre_e; ?></h1>
      <p class="desig">
        <?php echo htmlspecialchars_uni($raza_full); ?>
<?php if ($apodo !== ''): ?> &middot; &laquo;<?php echo htmlspecialchars_uni($apodo); ?>&raquo;<?php endif; ?>
<?php if ($genero !== ''): ?> &middot; <?php echo htmlspecialchars_uni(ucfirst((string) $genero)); ?><?php endif; ?>
<?php if ($edad !== ''): ?> &middot; <?php echo htmlspecialchars_uni((string) $edad); ?> a&ntilde;os<?php endif; ?>
      </p>
      <div class="idtags">
        <span class="tag estado<?php echo $es_aprobado ? ' ok' : ' pend'; ?>"><?php echo $estado_tag; ?></span>
        <span class="tag rank">Rango <?php echo $rango_e; ?></span>
<?php if ($faccion_lbl !== ''): ?>
        <span class="tag line"><?php echo htmlspecialchars_uni($faccion_lbl); ?></span>
<?php endif; ?>
        <span class="tag facrank"><?php echo $rango_faccion !== '' ? htmlspecialchars_uni($rango_faccion) : '&mdash;'; ?></span>
<?php if ($staff_rol_lbl !== ''): ?>
        <span class="tag staff"><?php echo htmlspecialchars_uni($staff_rol_lbl); ?></span>
<?php endif; ?>
<?php if ($puede_gestionar): ?>
        <span class="tag act"><?php echo (int) $pp_disponible; ?> PP</span>
<?php endif; ?>
<?php if ((int) $pj['activo'] === 1): ?>
        <span class="tag act">&#9670; Personaje activo</span>
<?php endif; ?>
      </div>
    </div>

    <div class="tabs" role="tablist" aria-label="Secciones del expediente">
      <button type="button" class="tab" role="tab" aria-selected="true" data-tab="crisol">Atributos</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="cronica">Cr&oacute;nica</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="combate">Combate</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="equipo">Equipo</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="relaciones">Relaciones</button>
    </div>

    <!-- CRISOL (ATRIBUTOS) -->
    <section class="panel on" id="tab-crisol" role="tabpanel">
      <div class="plate">
        <div class="plate-h">
          <span class="t">Atributos</span>
        </div>
        <div class="plate-b">
          <div class="ope-scalekey" aria-hidden="true">
            <span class="k">Escala</span>
            <div class="segs">
<?php foreach (array('1','2','3','4','5','6','7','8','9','10') as $sl): ?>
              <span><?php echo htmlspecialchars_uni($sl); ?></span>
<?php endforeach; ?>
            </div>
          </div>
<?php foreach ($STAT_GROUPS as $gkey => $grupo):
            $rows = $grupo['stats'];
            $gc   = $group_calc[$gkey];
?>
          <div class="pgroup">
            <div class="pgroup-h">
              <span class="n"><?php echo htmlspecialchars_uni($grupo['label']); ?></span>
              <span class="bar"></span>
            </div>
<?php foreach ($rows as $ab => $nm):
              $v = ope_rol_stat_num($stats_ef, $ab);
              if ($v > 10) $v = 10;
              $rank_lbl = ope_rol_stat_label($v);
?>
            <div class="stat">
              <span class="nm"><?php echo htmlspecialchars_uni($nm); ?></span>
              <div class="segbar" role="img" aria-label="<?php echo htmlspecialchars_uni($nm . ': ' . $rank_lbl); ?>">
<?php for ($i = 1; $i <= 10; $i++): ?>
                <span class="seg<?php echo $i <= $v ? ' on' : ''; ?>"></span>
<?php endfor; ?>
              </div>
            </div>
<?php endforeach; ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CRÓNICA -->
    <section class="panel" id="tab-cronica" role="tabpanel">
        <div>
          <!-- Descripción física (siempre visible) -->
          <div class="plate">
            <div class="plate-h"><span class="t">Descripci&oacute;n f&iacute;sica</span></div>
            <div class="plate-b prose">
<?php if ($from_fisico !== ''): ?>
              <p class="ope-from-line"><span class="l">From:</span> <?php echo htmlspecialchars_uni($from_fisico); ?></p>
<?php endif; ?>
<?php if ($desc_fisica !== ''): ?>
              <p><?php echo nl2br(htmlspecialchars_uni($desc_fisica)); ?></p>
<?php else: ?>
              <p class="mono c-dim">Sin descripci&oacute;n f&iacute;sica todav&iacute;a.<?php echo $puede_gestionar ? ' Edítala desde Gesti&oacute;n.' : ''; ?></p>
<?php endif; ?>
            </div>
          </div>

          <!-- Personalidad (siempre visible) -->
          <div class="plate">
            <div class="plate-h"><span class="t">Personalidad</span></div>
            <div class="plate-b prose">
<?php if ($personalidad !== ''): ?>
              <p><?php echo nl2br(htmlspecialchars_uni($personalidad)); ?></p>
<?php else: ?>
              <p class="mono c-dim">Sin personalidad registrada todav&iacute;a.<?php echo $puede_gestionar ? ' Edítala desde Gesti&oacute;n.' : ''; ?></p>
<?php endif; ?>
            </div>
          </div>

          <!-- Otros datos (concepto / pasado / motivación) siempre visible -->
<?php
            $otros_map = array(
                'concepto'   => 'Concepto',
                'pasado'     => 'Pasado',
                'motivacion' => 'Motivaci&oacute;n',
            );
            $otros_has = false;
            foreach ($otros_map as $ok => $ol) { if (trim((string) ($bio[$ok] ?? '')) !== '') { $otros_has = true; break; } }
?>
          <div class="plate">
            <div class="plate-h"><span class="t">Otros datos</span></div>
            <div class="plate-b prose">
<?php if (!$otros_has): ?>
              <p class="mono c-dim">Sin datos de cr&oacute;nica todav&iacute;a.</p>
<?php else: foreach ($otros_map as $ok => $ol):
                $otxt = trim((string) ($bio[$ok] ?? ''));
                if ($otxt === '') continue;
?>
              <p class="lead"><?php echo $ol; ?></p>
              <p class="mb-14"><?php echo nl2br(htmlspecialchars_uni($otxt)); ?></p>
<?php endforeach; endif; ?>
            </div>
          </div>

          <div class="plate">
            <div class="plate-h"><span class="t">L&iacute;nea de tiempo</span><span class="c">// historias por a&ntilde;o</span></div>
            <div class="plate-b">
<?php if (empty($cron_years)): ?>
              <p class="mono fs-76 c-dim">Este personaje a&uacute;n no ha participado en ninguna historia.</p>
<?php else: ?>
              <div class="tl-tabs" role="tablist">
<?php $tl_first = true; foreach ($cron_years as $y => $arr): ?>
                <button type="button" class="tl-tab" role="tab" aria-selected="<?php echo $tl_first ? 'true' : 'false'; ?>" data-year="<?php echo (int) $y; ?>">A&ntilde;o <?php echo htmlspecialchars_uni(ope_rol_year_label((int) $y)); ?></button>
<?php $tl_first = false; endforeach; ?>
              </div>
<?php $tl_first = true; foreach ($cron_years as $y => $arr): ?>
              <div class="tl-year" data-year-c="<?php echo (int) $y; ?>"<?php echo $tl_first ? '' : ' hidden'; ?>>
                <div class="tl">
<?php foreach ($arr as $e):
    $tag_lbl  = $e['tag'] !== '' ? ($TAG_LABELS[$e['tag']] ?? $e['tag']) : '';
    $tag_slug = $e['tag'] !== '' ? strtolower($e['tag']) : '';
?>
                  <div class="tl-i">
                    <a class="tl-title" href="<?php echo $bburl; ?>/showthread.php?tid=<?php echo (int) $e['tid']; ?>"><?php echo htmlspecialchars_uni($e['subject']); ?></a>
                    <span class="tl-badges">
<?php if ($e['era'] !== ''): ?><span class="tl-era tl-era-<?php echo $e['era']; ?>"><?php echo $e['era'] === 'pasado' ? 'Pasado' : 'Presente'; ?></span><?php endif; ?>
<?php if ($tag_lbl !== ''): ?><span class="tl-tag tl-tag-<?php echo $tag_slug; ?>"><?php echo htmlspecialchars_uni($tag_lbl); ?></span><?php endif; ?>
                    </span>
<?php if (trim($e['descripcion']) !== ''): ?>
                    <p class="tl-desc"><?php echo nl2br(htmlspecialchars_uni($e['descripcion'])); ?></p>
<?php endif; ?>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
<?php $tl_first = false; endforeach; ?>
<?php endif; ?>
            </div>
          </div>
        </div>
    </section>

    <!-- COMBATE -->
    <section class="panel" id="tab-combate" role="tabpanel">
      <div class="plate">
        <div class="plate-h"><span class="t">Vitales</span><span class="c">// AV-01</span></div>
        <div class="plate-b">
          <div class="ope-prog-vitals ope-ficha-vitals">
            <div class="ope-prog-vital ope-prog-vital--pv">
              <span class="ope-prog-vital-val"><?php echo (int) $pv_max; ?></span>
              <span class="ope-prog-vital-label">PV m&aacute;x</span>
            </div>
            <div class="ope-prog-vital ope-prog-vital--en">
              <span class="ope-prog-vital-val"><?php echo (int) $en_max; ?></span>
              <span class="ope-prog-vital-label">EN m&aacute;x</span>
            </div>
            <div class="ope-prog-vital ope-prog-vital--pa">
              <span class="ope-prog-vital-val"><?php echo (int) $pa_turno; ?></span>
              <span class="ope-prog-vital-label">PA / turno</span>
            </div>
            <div class="ope-prog-vital ope-prog-vital--rango">
              <span class="ope-prog-vital-val"><?php echo $rango_e; ?></span>
              <span class="ope-prog-vital-label">Rango</span>
            </div>
          </div>
        </div>
      </div>

      <div class="plate">
        <div class="plate-h"><span class="t">Pasivas</span></div>
        <div class="plate-b">
<?php if (empty($pasivas)): ?>
          <p class="mono fs-76 c-dim">Sin raza asignada.</p>
<?php else: foreach ($pasivas as $pas): ?>
          <div class="of">
            <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13 2 3 14h7l-1 8 11-14h-7z"/></svg></span>
            <div class="body">
              <div class="n"><?php echo htmlspecialchars_uni($pas['nombre']); ?><small><?php echo htmlspecialchars_uni($pas['desc']); ?></small></div>
            </div>
            <span class="lv"><?php echo htmlspecialchars_uni($pas['tag']); ?></span>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Deck de cartas de técnica (INI-03) -->
      <div class="plate">
        <div class="plate-h"><span class="t">Deck</span><span class="c">// <?php echo count($deck_tecnicas); ?> carta(s)</span></div>
        <div class="plate-b tal">
<?php if (empty($deck_tecnicas)): ?>
          <div class="ope-soon-box">
            <span class="ope-soon-tag">Deck vac&iacute;o</span>
            <p class="mono fs-76 c-dim mt-8">Este personaje a&uacute;n no ha aprendido ninguna carta de t&eacute;cnica.</p>
          </div>
<?php else: ?>
<?php echo ope_rol_tecnica_card_css(); ?>
          <div class="ope-tk-deck">
<?php foreach ($deck_tecnicas as $carta): ?>
            <?php echo ope_rol_tecnica_card_html($carta); ?>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </div>

      <!-- Virtudes y defectos (incluye el Balance de PC) -->
      <div class="plate">
        <div class="plate-h"><span class="t">Virtudes y defectos</span><span class="c">// <?php echo $pc_gas; ?> PC &middot; +<?php echo $pc_dev; ?> PC</span></div>
        <div class="plate-b">
          <div class="ope-pcbalance">
            <span class="l">Balance de PC</span>
            <span class="v"><?php echo $pc_bal; ?> <small>sin gastar</small></span>
          </div>
<?php if (empty($rasgos)): ?>
          <p class="mono fs-76 c-dim">Sin virtudes ni defectos registrados.</p>
<?php else: foreach ($rasgos as $rasgo): ?>
          <div class="trait">
            <span class="d <?php echo $rasgo['tipo']; ?>"></span>
            <div>
              <span class="b"><?php echo htmlspecialchars_uni($rasgo['nombre']); ?><?php echo $rasgo['spec'] !== '' ? ' &mdash; <em style="color:var(--paper-dim);font-style:italic">' . htmlspecialchars_uni($rasgo['spec']) . '</em>' : ''; ?></span>
<?php if ($rasgo['desc'] !== ''): ?><small><?php echo htmlspecialchars_uni($rasgo['desc']); ?></small><?php endif; ?>
            </div>
            <span class="id<?php echo $rasgo['tipo'] === 'x' ? ' x' : ''; ?>"><?php echo htmlspecialchars_uni($rasgo['badge']); ?></span>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>
    </section>

    <!-- EQUIPO -->
<?php
      // Slots ocupados por lo que se lleva encima (respeta el tamaño de cada objeto).
      $slots_usados = 0;
      foreach ($inv_encima as $it) { $slots_usados += max(1, (int) $it['size']); }
      $slots_libres = max(0, OPE_INV_CAP - $slots_usados);
      // En la VISTA de la ficha los objetos NO se mueven: al pulsarlos despliegan
      // su información. Mover entre inventario/almacén vive solo en el modal.
      $inv_data_attrs = static function (array $it): string {
          $sz = max(1, (int) $it['size']);
          return ' data-name="' . htmlspecialchars_uni($it['n']) . '"'
               . ' data-size="' . $sz . '"'
               . ' data-desc="' . htmlspecialchars_uni($it['d']) . '"';
      };
?>
    <section class="panel" id="tab-equipo" role="tabpanel">
      <!-- Mochila: berries + capacidad de slots -->
      <div class="plate">
        <div class="plate-h"><span class="t">Mochila</span><span class="c">// <?php echo $slots_usados; ?>/<?php echo OPE_INV_CAP; ?> slots</span></div>
        <div class="plate-b">
          <div class="ope-berries">
            <span class="ope-berries-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8.5 9.5h5M8.5 12h5M11 7v10"/></svg></span>
            <span class="ope-berries-l">Berries</span>
            <b class="ope-berries-v"><?php echo number_format($berries, 0, ',', '.'); ?></b>
          </div>
<?php if ($pack_def !== null): ?>
          <div class="ope-pack-inicial mt-10 pt-10 bt-dash">
            <span class="mono fs-62 fw-700 ttu ls-5 c-dim"><?php echo htmlspecialchars_uni($pack_def['nombre']); ?></span>
            <p class="mono fs-7 c-ash lh-15 mt-4"><?php echo implode(' &middot; ', array_map('htmlspecialchars_uni', $pack_def['contenido'])); ?></p>
          </div>
<?php elseif ($arma_legacy !== '' || $obj_legacy !== ''): ?>
          <div class="ope-pack-inicial mt-10 pt-10 bt-dash">
            <span class="mono fs-62 fw-700 ttu ls-5 c-dim">Equipo inicial (ficha antigua)</span>
<?php if ($arma_legacy !== ''): ?><p class="mono fs-7 c-ash mt-4"><b class="c-dim">Arma:</b> <?php echo htmlspecialchars_uni($arma_legacy); ?></p><?php endif; ?>
<?php if ($obj_legacy !== ''): ?><p class="mono fs-7 c-ash"><b class="c-dim">Objeto:</b> <?php echo htmlspecialchars_uni($obj_legacy); ?></p><?php endif; ?>
          </div>
<?php endif; ?>
        </div>
      </div>

      <div class="ope-inv">
        <!-- Inventario (lo que llevas encima): cuadrícula de slots -->
        <div class="plate ope-inv-side">
          <div class="plate-h"><span class="t">Inventario</span><span class="c">// encima &middot; <?php echo count($inv_encima); ?> obj.</span></div>
          <div class="plate-b">
            <p class="ope-inv-hint">Pulsa un objeto para ver su informaci&oacute;n. Los objetos grandes ocupan varios slots.<?php echo $puede_gestionar ? ' Para mover objetos usa Gesti&oacute;n &rsaquo; Equipo.' : ''; ?></p>
<?php if (empty($inv_encima)): ?>
            <p class="mono fs-76 c-dim">No llevas nada encima.</p>
<?php endif; ?>
            <div class="ope-slotgrid">
<?php foreach ($inv_encima as $i => $it):
                $sz = max(1, (int) $it['size']);
?>
              <button type="button" class="ope-slot filled ope-invitem" style="grid-column:span <?php echo min($sz, 4); ?>"<?php echo $inv_data_attrs($it); ?>>
                <span class="ope-slot-n"><?php echo htmlspecialchars_uni($it['n']); ?></span>
<?php if ($sz > 1): ?><span class="ope-slot-sz"><?php echo $sz; ?></span><?php endif; ?>
              </button>
<?php endforeach; ?>
<?php for ($e = 0; $e < $slots_libres; $e++): ?>
              <div class="ope-slot empty" aria-hidden="true"></div>
<?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Almacén: lista de objetos guardados -->
        <div class="plate ope-inv-side">
          <div class="plate-h"><span class="t">Almac&eacute;n</span><span class="c">// <?php echo count($inv_almacen); ?> obj.</span></div>
          <div class="plate-b">
            <p class="ope-inv-hint">Pulsa un objeto para ver su informaci&oacute;n.</p>
<?php if (empty($inv_almacen)): ?>
            <p class="mono fs-76 c-dim">Almac&eacute;n vac&iacute;o.</p>
<?php else: ?>
            <div class="ope-store">
<?php foreach ($inv_almacen as $i => $it):
                $sz = max(1, (int) $it['size']);
?>
              <button type="button" class="ope-store-item ope-invitem"<?php echo $inv_data_attrs($it); ?>>
                <span class="ope-store-n"><?php echo htmlspecialchars_uni($it['n']); ?></span>
                <span class="ope-store-sz"><?php echo $sz; ?> slot<?php echo $sz > 1 ? 's' : ''; ?></span>
              </button>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Detalle del objeto pulsado (se rellena por JS; no mueve nada) -->
      <div class="plate ope-inv-detail" id="ope-inv-detail" hidden>
        <div class="plate-h"><span class="t" id="ope-inv-detail-name">&mdash;</span><span class="c" id="ope-inv-detail-size"></span></div>
        <div class="plate-b prose"><p id="ope-inv-detail-desc" class="mono c-dim"></p></div>
      </div>
    </section>

    <!-- RELACIONES -->
    <section class="panel" id="tab-relaciones" role="tabpanel">
      <div class="plate">
        <div class="plate-h">
          <span class="t">Relaciones</span><span class="c">// mapa de v&iacute;nculos</span>
<?php if ($puede_gestionar): ?>
          <button type="button" class="ope-rel-editbtn" id="ope-rel-edit-open">Editar relaciones</button>
<?php endif; ?>
        </div>
        <div class="plate-b">
<?php if (empty($relaciones)): ?>
          <div class="ope-rel-empty" id="relaciones-map">
            <div class="ope-rel-empty-ic" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="6" r="2.4"/><circle cx="5" cy="17" r="2.4"/><circle cx="19" cy="17" r="2.4"/><path d="M12 8.4l-5.6 6.7M12 8.4l5.6 6.7M7 17h10"/></svg>
            </div>
            <p>A&uacute;n no hay v&iacute;nculos registrados en este expediente.</p>
<?php if ($puede_gestionar): ?>
            <button type="button" class="btn btn-hot" id="ope-rel-edit-open2">A&ntilde;adir la primera relaci&oacute;n</button>
<?php endif; ?>
          </div>
<?php else:
        $rel_svg_cx = $REL_CX; $rel_svg_cy = $REL_CY;
        // Nodo central: ICONO pequeño del personaje (fallback a inicial, no avatar).
        $c_ico = trim((string) ($pj['icono'] ?? ''));
?>
          <div class="ope-relmap-wrap" id="relaciones-map">
<?php if ($puede_gestionar): ?>
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" id="ope-relpos-form">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="rel_pos">
<?php endif; ?>
            <svg class="ope-relmap<?php echo $puede_gestionar ? ' is-editable' : ''; ?>" viewBox="0 0 1000 640" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Mapa de relaciones de <?php echo $nombre_e; ?>">
              <defs>
                <clipPath id="ope-rel-clip-c"><circle cx="0" cy="0" r="46"/></clipPath>
                <clipPath id="ope-rel-clip-n"><circle cx="0" cy="0" r="34"/></clipPath>
              </defs>
              <!-- Aristas centro -> nodo -->
<?php foreach ($relaciones as $rl): ?>
              <g class="ope-reledge rel-<?php echo $rl['tipo']; ?>">
                <line x1="<?php echo $rel_svg_cx; ?>" y1="<?php echo $rel_svg_cy; ?>" x2="<?php echo $rl['dx']; ?>" y2="<?php echo $rl['dy']; ?>"></line>
<?php
            $mx = (int) round(($rel_svg_cx + $rl['dx']) / 2);
            $my = (int) round(($rel_svg_cy + $rl['dy']) / 2);
            $lbl = trim($rl['etiqueta']) !== '' ? $rl['etiqueta'] : $rl['tipo_lbl'];
?>
                <g class="ope-rellabel" transform="translate(<?php echo $mx; ?>,<?php echo $my; ?>)">
                  <text text-anchor="middle" dy="4"><?php echo htmlspecialchars_uni($lbl); ?></text>
                </g>
              </g>
<?php endforeach; ?>
              <!-- Nodos de personajes relacionados -->
<?php foreach ($relaciones as $rl):
            $fc = $rl['fac_slug'] !== '' ? ' fac-' . $rl['fac_slug'] : '';
?>
              <g class="ope-relnode<?php echo $fc; ?>" data-rid="<?php echo $rl['rid']; ?>" transform="translate(<?php echo $rl['dx']; ?>,<?php echo $rl['dy']; ?>)"<?php if (!empty($rl['desc'])): ?> data-desc="<?php echo htmlspecialchars_uni($rl['desc']); ?>"<?php endif; ?>>
                <circle class="ope-relnode-bg" cx="0" cy="0" r="34"></circle>
<?php if ($rl['icono'] !== ''): ?>
                <image href="<?php echo htmlspecialchars_uni($rl['icono']); ?>" x="-34" y="-34" width="68" height="68" clip-path="url(#ope-rel-clip-n)" preserveAspectRatio="xMidYMid slice"></image>
<?php else: ?>
                <text class="ope-relnode-ini" text-anchor="middle" dy="9"><?php echo htmlspecialchars_uni($rl['inicial']); ?></text>
<?php endif; ?>
                <circle class="ope-relnode-ring" cx="0" cy="0" r="34" fill="none"></circle>
                <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo $rl['dest_pid']; ?>" class="ope-relnode-link">
                  <text class="ope-relnode-name" text-anchor="middle" y="52"><?php echo htmlspecialchars_uni($rl['nombre']); ?></text>
                </a>
<?php if ($puede_gestionar): ?>
                <input type="hidden" name="px[<?php echo $rl['rid']; ?>]" value="<?php echo $rl['dx']; ?>">
                <input type="hidden" name="py[<?php echo $rl['rid']; ?>]" value="<?php echo $rl['dy']; ?>">
<?php endif; ?>
              </g>
<?php endforeach; ?>
              <!-- Nodo central: personaje actual -->
              <g class="ope-relnode ope-relnode-center<?php echo $fac_class; ?>" transform="translate(<?php echo $rel_svg_cx; ?>,<?php echo $rel_svg_cy; ?>)">
                <circle class="ope-relnode-bg" cx="0" cy="0" r="46"></circle>
<?php if ($c_ico !== ''): ?>
                <image href="<?php echo htmlspecialchars_uni($c_ico); ?>" x="-46" y="-46" width="92" height="92" clip-path="url(#ope-rel-clip-c)" preserveAspectRatio="xMidYMid slice"></image>
<?php else: ?>
                <text class="ope-relnode-ini big" text-anchor="middle" dy="11"><?php echo htmlspecialchars_uni($inicial ?? mb_substr($pj['nombre'], 0, 1)); ?></text>
<?php endif; ?>
                <circle class="ope-relnode-ring" cx="0" cy="0" r="46" fill="none"></circle>
                <text class="ope-relnode-name center" text-anchor="middle" y="66"><?php echo $nombre_e; ?></text>
              </g>
            </svg>
<?php if ($puede_gestionar): ?>
            <div class="ope-relmap-actions">
              <span class="ope-relmap-hint">Arrastra los nodos para recolocarlos y guarda.</span>
              <button type="submit" class="btn btn-hot" id="ope-relpos-save" disabled>Guardar posiciones</button>
            </div>
          </form>
<?php endif; ?>
          </div>
          <div class="ope-rel-legend">
<?php foreach ($REL_TIPOS as $ts => $tl): ?>
            <span class="ope-rel-leg rel-<?php echo $ts; ?>"><i></i><?php echo $tl; ?></span>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>
<?php endif; ?>
</div>

<?php if ($puede_gestionar):
    $g_avatar = htmlspecialchars_uni((string) $pj['avatar']);
    $g_icono  = htmlspecialchars_uni((string) ($pj['icono'] ?? ''));
    $g_firma  = htmlspecialchars_uni((string) ($pj['firma'] ?? ''));
?>
<!-- ══ MODAL DE GESTIÓN ══ -->
<div class="ope-modal-ov" id="ope-gestion" role="dialog" aria-modal="true" aria-labelledby="ope-gestion-title" hidden>
  <div class="ope-modal">
    <div class="ope-modal-h">
      <div class="ope-modal-tt">
        <span class="ope-modal-eye">// gesti&oacute;n de personaje</span>
        <h2 id="ope-gestion-title"><?php echo $nombre_e; ?></h2>
      </div>
      <button type="button" class="ope-modal-x" id="ope-gestion-close" aria-label="Cerrar">&times;</button>
    </div>
    <div class="ope-modal-body">
      <nav class="ope-modal-rail" role="tablist" aria-label="Herramientas de gesti&oacute;n">
        <button type="button" class="ope-mtab" role="tab" aria-selected="true" data-mtab="perfil">
          <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 12 0v1"/></svg></span>
          <span>Avatar / Icono / Firma</span>
        </button>
        <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="templates">
          <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16"/><path d="M8 9h8M8 13h5"/></svg></span>
          <span>Templates de post</span>
        </button>
        <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="atributos">
          <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
          <span>Atributos</span>
        </button>
        <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="equipo">
          <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14h18V6l-3-4zM3 6h18M10 10a2 2 0 0 0 4 0"/></svg></span>
          <span>Equipo</span>
        </button>
        <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="cronologia">
          <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></span>
          <span>Cronolog&iacute;a</span>
        </button>
        <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="relaciones">
          <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="6" r="2.4"/><circle cx="5" cy="17" r="2.4"/><circle cx="19" cy="17" r="2.4"/><path d="M12 8.4l-5.6 6.7M12 8.4l5.6 6.7"/></svg></span>
          <span>Relaciones</span>
        </button>
      </nav>

      <div class="ope-modal-content">
<?php if ($gestion_ok): ?>
        <div class="ope-mflash">Cambios guardados correctamente.</div>
<?php endif; ?>

        <!-- Panel: Avatar / Icono / Firma -->
        <section class="ope-mpanel on" data-mpanel="perfil" role="tabpanel">
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="perfil">
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Avatar (retrato de la ficha)</label>
                <div class="ope-field-help">URL de imagen. Se muestra en la ficha y en tus mensajes.</div>
                <input type="url" name="avatar" value="<?php echo $g_avatar; ?>" placeholder="https://...">
              </div>
              <div class="ope-mprev">
<?php if ($g_avatar !== ''): ?>
                <img src="<?php echo $g_avatar; ?>" alt="Avatar" id="ope-prev-avatar">
<?php else: ?>
                <div class="ope-mprev-empty" id="ope-prev-avatar-empty"><?php echo htmlspecialchars_uni($inicial ?? mb_substr($pj['nombre'], 0, 1)); ?></div>
<?php endif; ?>
              </div>
            </div>
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Icono de post</label>
                <div class="ope-field-help">Imagen peque&ntilde;a opcional junto al nombre en cada mensaje.</div>
                <input type="url" name="icono" value="<?php echo $g_icono; ?>" placeholder="https://...">
              </div>
              <div class="ope-mprev">
<?php if ($g_icono !== ''): ?>
                <img src="<?php echo $g_icono; ?>" alt="Icono">
<?php else: ?>
                <div class="ope-mprev-empty small">64&times;64</div>
<?php endif; ?>
              </div>
            </div>
            <div class="ope-field">
              <label>Firma</label>
              <div class="ope-field-help">Admite BBCode: <code>[b]</code>, <code>[i]</code>, <code>[img]url[/img]</code>, <code>[color=#41A4E0]</code>&hellip; Aparecer&aacute; bajo cada mensaje de este personaje con un separador <b>One Piece Eternal</b>.</div>
              <textarea name="firma" rows="6" placeholder="[b]Dorr Kaskan[/b] &mdash; herrero de Elbaf&#10;[img]https://...[/img]"><?php echo $g_firma; ?></textarea>
            </div>

            <div class="ope-msep" aria-hidden="true"><span>One Piece Eternal</span></div>
            <div class="ope-modal-actions">
              <button type="submit" class="btn btn-hot">Guardar cambios</button>
            </div>
          </form>
        </section>

        <!-- Panel: Templates -->
        <section class="ope-mpanel" data-mpanel="templates" role="tabpanel">
          <div class="ope-field-help mb-12">Crea plantillas reutilizables (BBCode y spoilers anidados). Aparecer&aacute;n como botones sobre el editor al crear temas o responder, y se insertan en la posici&oacute;n del cursor.</div>

          <!-- Crear nueva plantilla -->
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-tpl-form" data-tpl-form>
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="tpl_add">
            <div class="ope-field">
              <label>Nombre de la plantilla</label>
              <input type="text" name="nombre" maxlength="120" placeholder="Ej.: Ficha de combate" required>
            </div>
            <div class="ope-field">
              <label>Cuerpo</label>
              <div class="ope-tpl-tools">
                <button type="button" class="ope-tpl-tool" data-ins="[spoiler=T&iacute;tulo][/spoiler]">+ Spoiler</button>
                <button type="button" class="ope-tpl-tool" data-ins="[b][/b]">B</button>
                <button type="button" class="ope-tpl-tool" data-ins="[i][/i]"><em>i</em></button>
                <button type="button" class="ope-tpl-tool" data-ins="[img][/img]">Imagen</button>
              </div>
              <textarea name="cuerpo" rows="7" placeholder="[spoiler=Estado]HP: 100/100&#10;[spoiler=Detalles]...[/spoiler][/spoiler]"></textarea>
            </div>
            <div class="ope-modal-actions"><button type="submit" class="btn btn-hot">A&ntilde;adir plantilla</button></div>
          </form>

          <div class="ope-msep" aria-hidden="true"><span>Mis plantillas</span></div>
<?php if (empty($tpls_list)): ?>
          <p class="mono fs-76 c-dim">A&uacute;n no tienes plantillas. Crea la primera arriba.</p>
<?php else: foreach ($tpls_list as $tp): ?>
          <div class="ope-tpl-item">
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-tpl-form" data-tpl-form>
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="tpl_edit">
              <input type="hidden" name="tpl_id" value="<?php echo (int) $tp['tpl_id']; ?>">
              <div class="ope-field">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="120" value="<?php echo htmlspecialchars_uni($tp['nombre']); ?>">
              </div>
              <div class="ope-field">
                <label>Cuerpo</label>
                <div class="ope-tpl-tools">
                  <button type="button" class="ope-tpl-tool" data-ins="[spoiler=T&iacute;tulo][/spoiler]">+ Spoiler</button>
                  <button type="button" class="ope-tpl-tool" data-ins="[b][/b]">B</button>
                  <button type="button" class="ope-tpl-tool" data-ins="[i][/i]"><em>i</em></button>
                  <button type="button" class="ope-tpl-tool" data-ins="[img][/img]">Imagen</button>
                </div>
                <textarea name="cuerpo" rows="6"><?php echo htmlspecialchars_uni($tp['cuerpo']); ?></textarea>
              </div>
              <div class="ope-rel-item-acts"><button type="submit" class="btn btn-ghost">Guardar</button></div>
            </form>
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" onsubmit="return confirm('&iquest;Eliminar esta plantilla?');">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="tpl_del">
              <input type="hidden" name="tpl_id" value="<?php echo (int) $tp['tpl_id']; ?>">
              <button type="submit" class="ope-rel-del">Eliminar</button>
            </form>
          </div>
<?php endforeach; endif; ?>
        </section>

        <!-- Panel: Atributos -->
        <section class="ope-mpanel" data-mpanel="atributos" role="tabpanel">
          <div class="ope-field-help mb-12">Edici&oacute;n directa de las stats efectivas (1&ndash;10). El sistema de puntos y coste de subida se detallar&aacute; m&aacute;s adelante; por ahora ajustas los valores a mano.</div>
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="attrs">
<?php foreach ($STAT_GROUPS as $gkey => $grupo): ?>
            <div class="ope-attr-group">
              <div class="ope-attr-gh"><?php echo htmlspecialchars_uni($grupo['label']); ?></div>
              <div class="ope-attr-grid">
<?php foreach ($grupo['stats'] as $ab => $nm):
                  $cur = (int) ($stats_ef[$ab] ?? 1);
                  if ($cur < 1) $cur = 1; if ($cur > 10) $cur = 10;
?>
                <label class="ope-attr-cell">
                  <span class="ope-attr-ab"><?php echo htmlspecialchars_uni($ab); ?></span>
                  <span class="ope-attr-nm"><?php echo htmlspecialchars_uni($nm); ?></span>
                  <input type="number" name="attr[<?php echo $ab; ?>]" min="1" max="10" step="1" value="<?php echo $cur; ?>">
                </label>
<?php endforeach; ?>
              </div>
            </div>
<?php endforeach; ?>
            <div class="ope-modal-actions mt-14"><button type="submit" class="btn btn-hot">Guardar atributos</button></div>
          </form>
        </section>

        <!-- Panel: Equipo -->
        <section class="ope-mpanel" data-mpanel="equipo" role="tabpanel">
          <div class="ope-field-help mb-12">Gestiona qu&eacute; objetos llevas <b>encima</b> y cu&aacute;les dejas en el <b>almac&eacute;n</b>. El l&iacute;mite de carga se definir&aacute; m&aacute;s adelante; por ahora no hay tope.</div>

<?php
          $equip_cols = array(
              'encima'  => array('Lleva encima', $inv_encima,  'Al almac&eacute;n'),
              'almacen' => array('Almac&eacute;n', $inv_almacen, 'Sacar (llevar)'),
          );
          foreach ($equip_cols as $loc => $col):
              list($col_lbl, $col_items, $move_lbl) = $col;
?>
          <div class="ope-msep" aria-hidden="true"><span><?php echo $col_lbl; ?></span></div>
<?php if (empty($col_items)): ?>
          <p class="mono fs-76 c-dim">Vac&iacute;o.</p>
<?php else: foreach ($col_items as $i => $it): ?>
          <div class="ope-equip-item">
            <div class="ope-equip-item-b">
              <span class="ope-equip-n"><?php echo htmlspecialchars_uni($it['n']); ?></span>
<?php if ($it['d'] !== ''): ?><span class="ope-equip-d"><?php echo htmlspecialchars_uni($it['d']); ?></span><?php endif; ?>
            </div>
            <div class="ope-equip-acts">
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="equip_move">
                <input type="hidden" name="from" value="<?php echo $loc; ?>">
                <input type="hidden" name="idx" value="<?php echo (int) $i; ?>">
                <button type="submit" class="btn btn-ghost"><?php echo $move_lbl; ?></button>
              </form>
            </div>
          </div>
<?php endforeach; endif; ?>
<?php endforeach; ?>
        </section>

        <!-- Panel: Cronología -->
        <section class="ope-mpanel" data-mpanel="cronologia" role="tabpanel">
<?php if (empty($cron_flat)): ?>
          <div class="ope-msoon">
            <h3>Gestionar cronolog&iacute;a</h3>
            <p>Todav&iacute;a no has participado en ninguna historia. Cuando publiques o respondas temas (fuera de Off Topic), aparecer&aacute;n aqu&iacute; para que les pongas una descripci&oacute;n.</p>
          </div>
<?php else: ?>
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="cronologia">
            <div class="ope-field-help mb-12">A&ntilde;ade una nota personal a cada historia de tu l&iacute;nea de tiempo. Se mostrar&aacute; bajo el t&iacute;tulo del tema en la ficha.</div>
            <div class="ope-cron-list">
<?php foreach ($cron_flat as $e):
    $tag_lbl2  = $e['tag'] !== '' ? ($TAG_LABELS[$e['tag']] ?? $e['tag']) : '';
    $tag_slug2 = $e['tag'] !== '' ? strtolower($e['tag']) : '';
?>
              <div class="ope-cron-item">
                <div class="ope-cron-head">
                  <span class="ope-cron-t"><?php echo htmlspecialchars_uni($e['subject']); ?></span>
                  <span class="ope-cron-y">A&ntilde;o <?php echo htmlspecialchars_uni(ope_rol_year_label((int) $e['anio'])); ?></span>
<?php if ($e['era'] !== ''): ?><span class="tl-era tl-era-<?php echo $e['era']; ?>"><?php echo $e['era'] === 'pasado' ? 'Pasado' : 'Presente'; ?></span><?php endif; ?>
<?php if ($tag_lbl2 !== ''): ?><span class="tl-tag tl-tag-<?php echo $tag_slug2; ?>"><?php echo htmlspecialchars_uni($tag_lbl2); ?></span><?php endif; ?>
                </div>
                <textarea name="descripcion[<?php echo (int) $e['tid']; ?>]" rows="3" placeholder="Describe qu&eacute; ocurri&oacute; en esta historia..."><?php echo htmlspecialchars_uni($e['descripcion']); ?></textarea>
              </div>
<?php endforeach; ?>
            </div>
            <div class="ope-modal-actions mt-14">
              <button type="submit" class="btn btn-hot">Guardar cronolog&iacute;a</button>
            </div>
          </form>
<?php endif; ?>
        </section>

        <!-- Panel: Relaciones -->
        <section class="ope-mpanel" data-mpanel="relaciones" role="tabpanel">
          <div class="ope-field-help mb-12">Vincula a este personaje con otros. El nodo se colorea con la facci&oacute;n del otro personaje y la l&iacute;nea con el tipo de v&iacute;nculo.</div>

<?php if (empty($rel_choices)): ?>
          <div class="ope-msoon"><p>No hay otros personajes aprobados con los que crear v&iacute;nculos todav&iacute;a.</p></div>
<?php else: ?>
          <!-- Añadir relación -->
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-rel-addform">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="rel_add">
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Personaje</label>
                <select name="destino_pid" required>
                  <option value="">&mdash; elige un personaje &mdash;</option>
<?php foreach ($rel_choices as $rc): ?>
                  <option value="<?php echo $rc['pid']; ?>"><?php echo htmlspecialchars_uni($rc['nombre']); ?></option>
<?php endforeach; ?>
                </select>
              </div>
              <div class="ope-field">
                <label>Tipo de v&iacute;nculo</label>
                <select name="tipo">
<?php foreach ($REL_TIPOS as $ts => $tl): ?>
                  <option value="<?php echo $ts; ?>"<?php echo $ts === 'aliado' ? ' selected' : ''; ?>><?php echo $tl; ?></option>
<?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="ope-field">
              <label>Nombre de la relaci&oacute;n</label>
              <div class="ope-field-help">Ej.: &laquo;Capit&aacute;n&raquo;, &laquo;Hermano de sangre&raquo;, &laquo;Rival eterno&raquo;.</div>
              <input type="text" name="etiqueta" maxlength="120" placeholder="Capit&aacute;n">
            </div>
            <div class="ope-field">
              <label>Descripci&oacute;n</label>
              <textarea name="descripcion" rows="3" placeholder="Historia o matiz de esta relaci&oacute;n..."></textarea>
            </div>
            <div class="ope-modal-actions"><button type="submit" class="btn btn-hot">A&ntilde;adir relaci&oacute;n</button></div>
          </form>
<?php endif; ?>

          <!-- Relaciones existentes -->
          <div class="ope-msep" aria-hidden="true"><span>V&iacute;nculos actuales</span></div>
<?php if (empty($relaciones)): ?>
          <p class="mono fs-76 c-dim">Sin v&iacute;nculos registrados todav&iacute;a.</p>
<?php else: foreach ($relaciones as $rl): ?>
          <div class="ope-rel-item rel-<?php echo $rl['tipo']; ?>">
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="rel_edit">
              <input type="hidden" name="rid" value="<?php echo $rl['rid']; ?>">
              <div class="ope-rel-item-h">
                <span class="ope-rel-item-node<?php echo $rl['fac_slug'] !== '' ? ' fac-' . $rl['fac_slug'] : ''; ?>"><?php echo htmlspecialchars_uni($rl['inicial']); ?></span>
                <a class="ope-rel-item-name" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo $rl['dest_pid']; ?>"><?php echo htmlspecialchars_uni($rl['nombre']); ?></a>
              </div>
              <div class="ope-mgrid">
                <div class="ope-field">
                  <label>Nombre de la relaci&oacute;n</label>
                  <input type="text" name="etiqueta" maxlength="120" value="<?php echo htmlspecialchars_uni($rl['etiqueta']); ?>">
                </div>
                <div class="ope-field">
                  <label>Tipo</label>
                  <select name="tipo">
<?php foreach ($REL_TIPOS as $ts => $tl): ?>
                    <option value="<?php echo $ts; ?>"<?php echo $ts === $rl['tipo'] ? ' selected' : ''; ?>><?php echo $tl; ?></option>
<?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="ope-field">
                <label>Descripci&oacute;n</label>
                <textarea name="descripcion" rows="2"><?php echo htmlspecialchars_uni($rl['desc']); ?></textarea>
              </div>
              <div class="ope-rel-item-acts">
                <button type="submit" class="btn btn-ghost">Guardar</button>
              </div>
            </form>
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" onsubmit="return confirm('&iquest;Eliminar este v&iacute;nculo?');">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="rel_del">
              <input type="hidden" name="rid" value="<?php echo $rl['rid']; ?>">
              <button type="submit" class="ope-rel-del">Eliminar</button>
            </form>
          </div>
<?php endforeach; endif; ?>
        </section>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
document.querySelectorAll('.tab').forEach(function (t) {
  t.addEventListener('click', function () {
    document.querySelectorAll('.tab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('on'); });
    t.setAttribute('aria-selected', 'true');
    var panel = document.getElementById('tab-' + t.dataset.tab);
    if (panel) panel.classList.add('on');
  });
});
document.querySelectorAll('.subtab').forEach(function (s) {
  s.addEventListener('click', function () {
    document.querySelectorAll('.subtab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    s.setAttribute('aria-selected', 'true');
    document.querySelectorAll('[data-bio-c]').forEach(function (c) { c.hidden = (c.dataset.bioC !== s.dataset.bio); });
  });
});
// Pestañas por año de la línea de tiempo.
document.querySelectorAll('.tl-tab').forEach(function (s) {
  s.addEventListener('click', function () {
    document.querySelectorAll('.tl-tab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    s.setAttribute('aria-selected', 'true');
    document.querySelectorAll('[data-year-c]').forEach(function (c) { c.hidden = (c.dataset.yearC !== s.dataset.year); });
  });
});

// ── Equipo (vista de ficha): pulsar un objeto despliega su info (no lo mueve) ──
(function () {
  var box = document.getElementById('ope-inv-detail');
  if (!box) return;
  var elName = document.getElementById('ope-inv-detail-name');
  var elSize = document.getElementById('ope-inv-detail-size');
  var elDesc = document.getElementById('ope-inv-detail-desc');
  var current = null;
  document.querySelectorAll('.ope-invitem').forEach(function (it) {
    it.addEventListener('click', function () {
      document.querySelectorAll('.ope-invitem.is-open').forEach(function (x) { if (x !== it) x.classList.remove('is-open'); });
      if (current === it) { it.classList.remove('is-open'); box.hidden = true; current = null; return; }
      current = it;
      it.classList.add('is-open');
      var n = it.getAttribute('data-name') || '';
      var s = parseInt(it.getAttribute('data-size') || '1', 10) || 1;
      var d = it.getAttribute('data-desc') || '';
      elName.textContent = n;
      elSize.textContent = s + ' slot' + (s > 1 ? 's' : '');
      if (d) { elDesc.textContent = d; elDesc.style.color = 'var(--paper)'; }
      else { elDesc.textContent = 'Sin descripción.'; elDesc.style.color = 'var(--paper-dim)'; }
      box.hidden = false;
    });
  });
})();

// ── Modal de gestión ──
(function () {
  var ov = document.getElementById('ope-gestion');
  if (!ov) return;
  var openBtn = document.getElementById('ope-gestion-open');
  var closeBtn = document.getElementById('ope-gestion-close');
  function open() { ov.hidden = false; document.body.style.overflow = 'hidden'; }
  function close() { ov.hidden = true; document.body.style.overflow = ''; }
  if (openBtn) openBtn.addEventListener('click', open);
  if (closeBtn) closeBtn.addEventListener('click', close);
  ov.addEventListener('click', function (e) { if (e.target === ov) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !ov.hidden) close(); });

  // Pestañas del modal.
  ov.querySelectorAll('.ope-mtab').forEach(function (t) {
    t.addEventListener('click', function () {
      ov.querySelectorAll('.ope-mtab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
      t.setAttribute('aria-selected', 'true');
      ov.querySelectorAll('.ope-mpanel').forEach(function (p) {
        p.classList.toggle('on', p.dataset.mpanel === t.dataset.mtab);
      });
    });
  });

  // Previsualización en vivo del avatar.
  var avIn = ov.querySelector('input[name="avatar"]');
  var avImg = document.getElementById('ope-prev-avatar');
  var avEmpty = document.getElementById('ope-prev-avatar-empty');
  if (avIn) avIn.addEventListener('input', function () {
    var v = avIn.value.trim();
    if (!/^https?:\/\//i.test(v)) return;
    if (!avImg && avEmpty) { avImg = document.createElement('img'); avImg.id = 'ope-prev-avatar'; avImg.alt = 'Avatar'; avEmpty.replaceWith(avImg); avEmpty = null; }
    if (avImg) avImg.src = v;
  });

  // Abre una pestaña concreta del modal.
  function openTab(name) {
    var tab = ov.querySelector('.ope-mtab[data-mtab="' + name + '"]');
    if (tab) tab.click();
  }

  // Botones "Editar relaciones" (cabecera del mapa y estado vacío).
  ['ope-rel-edit-open', 'ope-rel-edit-open2'].forEach(function (id) {
    var b = document.getElementById(id);
    if (b) b.addEventListener('click', function () { open(); openTab('relaciones'); });
  });

  // Herramientas de inserción BBCode/spoiler en los editores de plantillas.
  ov.addEventListener('click', function (e) {
    var t = e.target.closest('.ope-tpl-tool');
    if (!t) return;
    var form = t.closest('form');
    var ta = form && form.querySelector('textarea[name="cuerpo"]');
    if (!ta) return;
    var ins = t.getAttribute('data-ins') || '';
    var s = ta.selectionStart || 0, en = ta.selectionEnd || 0, v = ta.value;
    var m = /^(\[[^\]]*\])(\[\/[^\]]*\])$/.exec(ins);
    var caret;
    if (m) {
      // Envuelve la selección o coloca el cursor entre las etiquetas.
      var sel = v.slice(s, en);
      ta.value = v.slice(0, s) + m[1] + sel + m[2] + v.slice(en);
      caret = s + m[1].length + sel.length;
    } else {
      ta.value = v.slice(0, s) + ins + v.slice(en);
      caret = s + ins.length;
    }
    ta.focus();
    try { ta.setSelectionRange(caret, caret); } catch (x) {}
  });

  // Si venimos de guardar (?g=1), abre el modal directamente.
  if (/[?&]g=1(&|$)/.test(location.search)) {
    open();
    if (location.hash === '#cronologia') openTab('cronologia');
    if (location.hash === '#relaciones') openTab('relaciones');
    if (location.hash === '#templates') openTab('templates');
    if (location.hash === '#atributos') openTab('atributos');
    if (location.hash === '#equipo') openTab('equipo');
  }
})();

// ── Mapa de relaciones: activar pestaña + arrastre de nodos ──
(function () {
  var svg = document.querySelector('.ope-relmap');
  if (!svg) return;

  // Si venimos de guardar posiciones, muestra la pestaña Relaciones.
  if (location.hash === '#relaciones-map') {
    var relTab = document.querySelector('.tab[data-tab="relaciones"]');
    if (relTab) relTab.click();
  }

  if (!svg.classList.contains('is-editable')) return;
  var saveBtn = document.getElementById('ope-relpos-save');
  var pt = svg.createSVGPoint();
  var dragging = null;

  function toSvg(evt) {
    pt.x = evt.clientX; pt.y = evt.clientY;
    var m = svg.getScreenCTM();
    if (!m) return { x: 0, y: 0 };
    var p = pt.matrixTransform(m.inverse());
    return { x: p.x, y: p.y };
  }
  function clamp(v, min, max) { return v < min ? min : (v > max ? max : v); }

  svg.querySelectorAll('.ope-relnode[data-rid]').forEach(function (node) {
    node.addEventListener('pointerdown', function (e) {
      // No arrastres al pulsar el enlace del nombre.
      if (e.target.closest('.ope-relnode-link')) return;
      e.preventDefault();
      dragging = node;
      node.classList.add('is-dragging');
      node.setPointerCapture(e.pointerId);
    });
  });

  svg.addEventListener('pointermove', function (e) {
    if (!dragging) return;
    var p = toSvg(e);
    var x = Math.round(clamp(p.x, 40, 960));
    var y = Math.round(clamp(p.y, 40, 600));
    dragging.setAttribute('transform', 'translate(' + x + ',' + y + ')');
    var rid = dragging.getAttribute('data-rid');
    var hx = dragging.querySelector('input[name="px[' + rid + ']"]');
    var hy = dragging.querySelector('input[name="py[' + rid + ']"]');
    if (hx) hx.value = x;
    if (hy) hy.value = y;
    updateEdges();
    if (saveBtn) saveBtn.disabled = false;
  });
  function endDrag(e) {
    if (!dragging) return;
    dragging.classList.remove('is-dragging');
    dragging = null;
  }
  svg.addEventListener('pointerup', endDrag);
  svg.addEventListener('pointercancel', endDrag);

  // Recalcula posición de aristas/etiquetas a partir de los nodos.
  function updateEdges() {
    var cx = 500, cy = 320;
    var nodes = svg.querySelectorAll('.ope-relnode[data-rid]');
    var edges = svg.querySelectorAll('.ope-reledge');
    nodes.forEach(function (node, i) {
      var t = node.getAttribute('transform') || '';
      var m = /translate\(\s*(-?\d+\.?\d*)[ ,]+(-?\d+\.?\d*)\)/.exec(t);
      if (!m) return;
      var x = parseFloat(m[1]), y = parseFloat(m[2]);
      var edge = edges[i];
      if (!edge) return;
      var ln = edge.querySelector('line');
      if (ln) { ln.setAttribute('x2', x); ln.setAttribute('y2', y); }
      var lbl = edge.querySelector('.ope-rellabel');
      if (lbl) lbl.setAttribute('transform', 'translate(' + Math.round((cx + x) / 2) + ',' + Math.round((cy + y) / 2) + ')');
    });
  }
})();
</script>

</body>
</html>
