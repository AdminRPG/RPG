<?php
/**
 * I-Forge · Forjar personaje (wizard de creación)
 * Página de front-end MyBB (dirección "Granblue Fantasy: Eternal").
 *
 * Wizard de un único envío (sin borradores intermedios) que sigue los
 * 7 pasos de one-piece-eternal-sistemas/01-creacion-de-personaje.md:
 * raza, concepto, stats, virtudes/defectos, facción, equipo, historia.
 *
 * Al enviar: valida TODO en servidor contra inc/gbe_rol_data.php
 * (nunca confía en lo que calculó el JS), inserta en mybb_rol_personajes
 * con estado=revision y abre un trámite en mybb_rol_tramites para que el
 * staff lo apruebe desde "Mi expediente".
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/gbe_rol_data.php';
require_once MYBB_ROOT . 'inc/gbe_rol_wanted.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

require_once MYBB_ROOT . 'inc/gbe_user_init.php';

$staff_level = gbe_get_staff_level($uid);

$initials   = gbe_get_initials($mybb->user['username'] ?? '');
$initials_e = htmlspecialchars_uni($initials);

$RAZAS      = gbe_rol_razas();
$VIRTUDES   = gbe_rol_virtudes();
$DEFECTOS   = gbe_rol_defectos();
$FACCIONES  = gbe_rol_facciones();
$PACKS      = gbe_rol_packs_equipo();
$STATS      = gbe_rol_stats();
$STAT_KEYS  = gbe_rol_stat_keys();
$PC_BASE    = gbe_rol_pc_iniciales();
$RUPIES_BASE = gbe_rol_rupies_iniciales();

// ─────────────────────────────────────────────────────────────
// Slots disponibles
// ─────────────────────────────────────────────────────────────
$slots = 1;
$usados = 0;
if ($loggedin && $db->table_exists('rol_cuentas')) {
    $sq = $db->simple_select('rol_cuentas', 'slots', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($sq)) {
        $slots = (int)$db->fetch_field($sq, 'slots');
    }
}
if ($loggedin && $db->table_exists('rol_personajes')) {
    $uq = $db->simple_select('rol_personajes', 'COUNT(*) AS c', "uid = {$uid} AND estado != 'rechazado'");
    $urow = $db->fetch_array($uq);
    $usados = (int)($urow['c'] ?? 0);
}
$hay_hueco = $usados < $slots;

// ── Detección de edición de ficha moderada ──
$editando_pid = (int)($mybb->get_input('editar', MyBB::INPUT_INT));
$editando = null;
if ($editando_pid > 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $eq = $db->simple_select('rol_personajes', '*', "pid = {$editando_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($eq)) {
        $editando = $db->fetch_array($eq);
        if ($db->table_exists('rol_mensajes')) {
            $db->update_query('rol_mensajes', array('leido' => 1), "destino_pid = {$editando_pid} AND asunto LIKE 'Moderación:%'");
        }
    }
}

// ─────────────────────────────────────────────────────────────
// POST: validar y crear
// ─────────────────────────────────────────────────────────────
$errores = array();
$ok = false;
$old = $_POST;

function gbe_rol_clean($s, $max = 4000)
{
    $s = trim((string)$s);
    if (function_exists('mb_substr')) {
        $s = mb_substr($s, 0, $max, 'UTF-8');
    } else {
        $s = substr($s, 0, $max);
    }
    return $s;
}

if ($loggedin && $mybb->request_method === 'post' && $hay_hueco) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $errores[] = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } else {
        // ---- Raza ----
        $raza1 = $mybb->get_input('raza_principal');
        $hibrido = $mybb->get_input('es_hibrido', MyBB::INPUT_INT) ? true : false;
        $raza2 = $hibrido ? $mybb->get_input('raza_secundaria') : '';

        if (!isset($RAZAS[$raza1])) {
            $errores[] = 'Elige una raza principal válida.';
        }
        if ($hibrido && (!isset($RAZAS[$raza2]) || $raza2 === $raza1)) {
            $errores[] = 'Elige una raza secundaria distinta para el híbrido.';
        }

        // ---- Nombre ----
        $nombre = gbe_rol_clean($mybb->get_input('nombre'), 120);
        $apodo = gbe_rol_clean($mybb->get_input('apodo'), 60);
        $edad = gbe_rol_clean($mybb->get_input('edad'), 20);
        $genero = gbe_rol_clean($mybb->get_input('genero'), 40);

        if ($nombre === '' || function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') < 3 : strlen($nombre) < 3) {
            $errores[] = 'El nombre del personaje debe tener al menos 3 caracteres.';
        }
        if ($nombre !== '' && $db->table_exists('rol_personajes')) {
            $dupe = $db->simple_select('rol_personajes', 'pid', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
            if ($db->num_rows($dupe)) {
                $errores[] = 'Ya existe un personaje con ese nombre.';
            }
        }

        // ---- Stats: recalcular en servidor, nunca confiar en el cliente ----
        // ---- Sub-opción racial (Herencia Tribal / Linaje Colosal, INI-01) ----
        // Solo aplica a razas PURAS (no híbridas) cuya raza defina 'sub_opciones':
        // sustituye la pasiva secundaria genérica por la de la opción elegida.
        $sub_opciones_disp = (!$hibrido && isset($RAZAS[$raza1]['sub_opciones'])) ? $RAZAS[$raza1]['sub_opciones'] : array();
        $sub_opcion = $mybb->get_input('sub_opcion');
        if (!empty($sub_opciones_disp)) {
            if (!isset($sub_opciones_disp[$sub_opcion])) {
                $errores[] = 'Elige una opción para la pasiva secundaria de tu raza.';
                $sub_opcion = '';
            }
        } else {
            $sub_opcion = '';
        }

        // ---- Stats v2: reparto numérico de PS (5-100+) ----
        $stats_base = gbe_rol_stats_base(); // Todos en 5
        $raza_data = isset($RAZAS[$raza1]) ? $RAZAS[$raza1] : array();
        $ps_disponibles = gbe_rol_ps_iniciales($raza1);

        // Recoger valores repartidos por el jugador (vienen como array numerico)
        $ps_asignados_raw = $mybb->get_input('ps_stats', MyBB::INPUT_ARRAY);
        $ps_asignados = array();
        $ps_total_usado = 0;
        if (is_array($ps_asignados_raw)) {
            foreach ($STAT_KEYS as $sk) {
                $v = isset($ps_asignados_raw[$sk]) ? (int)$ps_asignados_raw[$sk] : 0;
                $v = max(0, $v);
                $ps_asignados[$sk] = $v;
                $ps_total_usado += $v;
            }
        }

        if ($ps_total_usado <= 0) {
            $errores[] = 'Reparte al menos 1 Punto de Stat (PS).';
        }
        if ($ps_total_usado > $ps_disponibles) {
            $errores[] = "Has repartido {$ps_total_usado} PS, pero solo dispones de {$ps_disponibles}.";
        }

        // Aplicar PS a stats base
        $stats_sin_pasivas = $stats_base;
        foreach ($ps_asignados as $sk => $v) {
            $stats_sin_pasivas[$sk] = ($stats_sin_pasivas[$sk] ?? 5) + $v;
        }

        // Validar cap de creación: ningún stat > 20
        foreach ($stats_sin_pasivas as $sk => $sv) {
            if ($sv > 20) {
                $errores[] = "{$sk} no puede superar 20 en la creación (tiene {$sv}).";
            }
        }

        // Aplicar pasivas raciales (multiplicadores %)
        $stats_efectivas = gbe_rol_aplicar_pasivas($stats_sin_pasivas, $raza_data);
        if ($hibrido && isset($RAZAS[$raza2])) {
            // En híbrido: solo primaria de ambas razas (no secundaria)
            $stats_efectivas = gbe_rol_aplicar_pasivas($stats_efectivas, $RAZAS[$raza2]);
        } elseif (!$hibrido) {
            // Puro: aplicar multiplicadores de secundaria
            $mults_sec = isset($raza_data['multiplicadores_secundaria']) ? $raza_data['multiplicadores_secundaria'] : array();
            foreach ($mults_sec as $stat => $factor) {
                if (isset($stats_efectivas[$stat])) {
                    $stats_efectivas[$stat] = (int) round($stats_efectivas[$stat] * $factor);
                }
            }
        }

        $suma = gbe_rol_stat_sum($stats_efectivas);
        $nivel = 1;

        // ---- Virtudes y Defectos ----
        $virtudes_in = $mybb->get_input('virtudes', MyBB::INPUT_ARRAY);
        $defectos_in = $mybb->get_input('defectos', MyBB::INPUT_ARRAY);
        if (!is_array($virtudes_in)) $virtudes_in = array();
        if (!is_array($defectos_in)) $defectos_in = array();

        $pc_gastado = 0;
        $virtudes_sel = array();
        foreach ($virtudes_in as $vid) {
            $v = gbe_rol_find_virtud($vid);
            if ($v === null) continue;
            $spec = !empty($v['spec']) ? gbe_rol_clean($mybb->get_input('virtud_spec_' . $vid), 200) : '';
            if (!empty($v['spec']) && $spec === '') {
                $errores[] = 'La virtud "' . $v['nombre'] . '" requiere que especifiques un detalle.';
            }
            $pc_gastado += (int)$v['coste'];
            $virtudes_sel[$vid] = array('nombre' => $v['nombre'], 'coste' => (int)$v['coste'], 'spec' => $spec);
        }
        // Prerrequisitos Adinerado 1→2→3
        if (isset($virtudes_sel['V-RIQ-02']) && !isset($virtudes_sel['V-RIQ-01'])) {
            $errores[] = 'Adinerado 2 requiere tener Adinerado 1.';
        }
        if (isset($virtudes_sel['V-RIQ-03']) && !isset($virtudes_sel['V-RIQ-02'])) {
            $errores[] = 'Adinerado 3 requiere tener Adinerado 2.';
        }

        $pc_devuelto = 0;
        $defectos_sel = array();
        foreach ($defectos_in as $did) {
            $d = gbe_rol_find_defecto($did);
            if ($d === null) continue;
            $spec = !empty($d['spec']) ? gbe_rol_clean($mybb->get_input('defecto_spec_' . $did), 200) : '';
            if (!empty($d['spec']) && $spec === '') {
                $errores[] = 'El defecto "' . $d['nombre'] . '" requiere que especifiques un detalle.';
            }
            $pc_devuelto += (int)$d['devuelve'];
            $defectos_sel[$did] = array('nombre' => $d['nombre'], 'devuelve' => (int)$d['devuelve'], 'spec' => $spec);
        }

        $pc_balance = $PC_BASE - $pc_gastado + $pc_devuelto;
        if ($pc_balance < 0) {
            $errores[] = 'Te has pasado de Puntos de Creación (PC). Ajusta virtudes/defectos.';
        }

        // ---- Facción ----
        $faccion = $mybb->get_input('faccion');
        if (!isset($FACCIONES[$faccion])) {
            $errores[] = 'Elige una facción inicial válida.';
        }

        // ---- Equipo: Pack de Equipo Inicial (INI-01, Paso 6) ----
        $pack_equipo = $mybb->get_input('pack_equipo');
        if (!isset($PACKS[$pack_equipo])) {
            $errores[] = 'Elige un Pack de Equipo Inicial válido.';
        }
        $rupies = $RUPIES_BASE;
        if (isset($virtudes_sel['V-RIQ-01'])) $rupies += 1000000;
        if (isset($virtudes_sel['V-RIQ-02'])) $rupies += 3000000;
        if (isset($virtudes_sel['V-RIQ-03'])) $rupies += 10000000;

        // ---- Historia ----
        $historia_pasado = gbe_rol_clean($mybb->get_input('historia_pasado'), 6000);
        $historia_motivacion = gbe_rol_clean($mybb->get_input('historia_motivacion'), 3000);
        $historia_relaciones = gbe_rol_clean($mybb->get_input('historia_relaciones'), 3000);
        $min_len = function_exists('mb_strlen') ? mb_strlen($historia_pasado, 'UTF-8') : strlen($historia_pasado);
        if ($min_len < 80) {
            $errores[] = 'Cuenta el pasado de tu personaje con algo más de detalle (mínimo ~80 caracteres).';
        }

        // ---- Insertar si todo OK ----
        if (empty($errores) && $db->table_exists('rol_personajes')) {
            $slug = my_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
            $slug = trim($slug, '-');

            $stats_json_data = array();
            foreach ($STAT_KEYS as $sk) {
                $stats_json_data[$sk] = $stats_efectivas[$sk] ?? 5;
            }

            $datos = array(
                'raza_principal' => $raza1,
                'raza_secundaria' => $hibrido ? $raza2 : null,
                'hibrido' => $hibrido,
                'sub_opcion_racial' => $sub_opcion,
                'apodo' => $apodo,
                'edad' => $edad,
                'genero' => $genero,
                'stats_base' => $stats_sin_pasivas,
                'stats_efectivas' => $stats_efectivas,
                'ps_asignados' => $ps_asignados,
                'ps_disponibles' => $ps_disponibles,
                'ps_total_usado' => $ps_total_usado,
                'virtudes' => $virtudes_sel,
                'defectos' => $defectos_sel,
                'pc_gastado' => $pc_gastado,
                'pc_devuelto' => $pc_devuelto,
                'pc_balance' => $pc_balance,
                'faccion' => $faccion,
            );
            $inventario = array(
                'pack_equipo' => $pack_equipo,
            );
            $economia = array('rupies' => $rupies, 'berries' => $rupies);
            $bio = array(
                'pasado' => $historia_pasado,
                'motivacion' => $historia_motivacion,
                'relaciones' => $historia_relaciones,
            );

            $pid = $db->insert_query('rol_personajes', array(
                'uid' => $uid,
                'nombre' => $db->escape_string($nombre),
                'slug' => $db->escape_string($slug),
                'estado' => 'revision',
                'activo' => 0,
                'nivel' => 1,
                'avatar' => '',
                'stats_json' => $db->escape_string(json_encode($stats_json_data, JSON_UNESCAPED_UNICODE)),
                'ps_gastados' => $ps_total_usado,
                'stats_ganados' => 0,
                'datos' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
                'inventario' => $db->escape_string(json_encode($inventario, JSON_UNESCAPED_UNICODE)),
                'economia' => $db->escape_string(json_encode($economia, JSON_UNESCAPED_UNICODE)),
                'bio' => $db->escape_string(json_encode($bio, JSON_UNESCAPED_UNICODE)),
                'dateline' => TIME_NOW,
                'lastedit' => TIME_NOW,
            ));

            if ($pid && $db->table_exists('rol_tramites')) {
                $nuevo_pid = (int)$pid;
                $existing = $db->simple_select('rol_tramites', 'id', "pid = {$nuevo_pid} AND tipo = 'crear_personaje' AND estado = 'pendiente'", array('limit' => 1));
                if ($db->num_rows($existing) == 0) {
                    $db->insert_query('rol_tramites', array(
                        'uid' => $uid,
                        'pid' => $nuevo_pid,
                        'tipo' => 'crear_personaje',
                        'estado' => 'pendiente',
                        'datos' => $db->escape_string(json_encode(array('nombre' => $nombre, 'faccion' => $faccion), JSON_UNESCAPED_UNICODE)),
                        'dateline' => TIME_NOW,
                        'lastedit' => TIME_NOW,
                    ));
                }
            }

            $ok = true;

            // Inicializar Wanted según raza
            if (function_exists('gbe_wanted_init_raza')) {
                gbe_wanted_init_raza((int)$pid, $raza1);
            }
        }
    }
}

if ($ok) {
    header('Location: ' . $mybb->settings['bburl'] . '/personajes.php?forjado=1');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Crear personaje</title>
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-crear-personaje) -->
</head>
<body class="gbe-pg-crear-personaje">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b>Crear</b>
  </div>
</div>

<div class="wrap">

  <section>
    <div class="shead">
      <h1>Crear personaje</h1>
      <span class="code">// one piece eternal</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$loggedin): ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Acceso requerido</span><span class="c">// acceso</span></div>
    <div class="plate-b">
      <div class="pj-empty">
        <span class="anvil"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
        <div class="big">Accede para crear un personaje</div>
        <p>Necesitas una cuenta en el foro para crear una ficha.</p>
        <div class="acts">
          <a href="<?php echo $bburl; ?>/member.php?action=register" class="btn btn-hot">Reg&iacute;strate</a>
          <a href="<?php echo $bburl; ?>/member.php?action=login" class="btn btn-ghost">Acceder</a>
        </div>
      </div>
    </div>
  </div>
<?php elseif (!$hay_hueco && !$editando): ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Sin huecos disponibles</span><span class="c">// <?php echo $usados; ?>/<?php echo $slots; ?></span></div>
    <div class="plate-b">
      <div class="pj-empty">
        <span class="anvil"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
        <div class="big">Ya usas todos tus huecos de personaje</div>
        <p>Tu cuenta tiene <?php echo $slots; ?> hueco(s) de personaje y ya los ocupas todos (<?php echo $usados; ?>). Solicita un hueco adicional en trámites o gestiona tus fichas actuales.</p>
        <div class="acts">
          <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Ver mi expediente</a>
          <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost">Trámites</a>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>

<?php if (!empty($errores)): ?>
  <div class="flash warn">No se pudo crear el personaje:
    <ul><?php foreach ($errores as $e) echo '<li>' . htmlspecialchars_uni($e) . '</li>'; ?></ul>
  </div>
<?php endif; ?>

<?php if ($editando): ?>
  <div class="mb-14 p-12-16 b-2-h6 bg-plate df ai-center jc-sb gap-12">
    <span class="mono fs-68 c-dim">Est&aacute;s editando la ficha de <b class="c-paper"><?php echo htmlspecialchars_uni($editando['nombre']); ?></b>. Los cambios se enviar&aacute;n a revisi&oacute;n de nuevo.</span>
    <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-ghost btn-sm">Cancelar</a>
  </div>
<?php endif; ?>

  <p class="intro-mono">
    Sigue los <b class="c-paper">7 pasos</b> del foro: raza, concepto, estadísticas, virtudes/defectos, facción, equipo e historia. Rellena todo en una sola sesión — al enviar, tu ficha entra en <b class="c-ember">revisión</b> del staff.
  </p>

  <div class="wiz-progress" id="wizProgress"></div>

  <form method="post" action="<?php echo $bburl; ?>/crear-personaje.php" id="wizForm">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">

    <!-- PASO 1: RAZA -->
    <div class="wiz-step on" data-step="1">
      <div class="plate">
        <div class="plate-h"><span class="t">1. Raza</span><span class="c">// pura o híbrida</span></div>
        <div class="plate-b">
          <div class="race-grid" id="raceGrid">
<?php foreach ($RAZAS as $rid => $r):
    $mod = json_encode($r['mod'] ?? array(), JSON_UNESCAPED_UNICODE);
    $modsec = json_encode($r['mod_secundaria'] ?? array(), JSON_UNESCAPED_UNICODE);
    $subop = json_encode($r['sub_opciones'] ?? array(), JSON_UNESCAPED_UNICODE);
    $mults = json_encode($r['multiplicadores'] ?? array(), JSON_UNESCAPED_UNICODE);
    $multssec = json_encode($r['multiplicadores_secundaria'] ?? array(), JSON_UNESCAPED_UNICODE);
    $psbonus = (int)($r['ps_bonus'] ?? 0);
?>
            <label class="race-card">
              <input type="radio" name="raza_principal" value="<?php echo $rid; ?>" data-mod='<?php echo htmlspecialchars_uni($mod); ?>' data-modsec='<?php echo htmlspecialchars_uni($modsec); ?>' data-subop='<?php echo htmlspecialchars_uni($subop); ?>' data-mults='<?php echo htmlspecialchars_uni($mults); ?>' data-multssec='<?php echo htmlspecialchars_uni($multssec); ?>' data-ps-bonus="<?php echo $psbonus; ?>" data-extra-bump="<?php echo !empty($r['extra_stat_bump']) ? '1' : '0'; ?>" data-nombre="<?php echo htmlspecialchars_uni($r['nombre']); ?>" required>
              <div class="rc-body">
                <div class="rc-name"><?php echo htmlspecialchars_uni($r['nombre']); ?></div>
                <div class="rc-resumen"><?php echo htmlspecialchars_uni($r['resumen']); ?></div>
                <div class="rc-pas"><b>Primaria</b> — <?php echo htmlspecialchars_uni($r['primaria_nombre']); ?>: <?php echo htmlspecialchars_uni($r['primaria_desc']); ?><br><b>Secundaria</b> — <?php echo htmlspecialchars_uni($r['secundaria_nombre']); ?>: <?php echo htmlspecialchars_uni($r['secundaria_desc']); ?></div>
              </div>
            </label>
<?php endforeach; ?>
          </div>

          <div class="field mt-16">
            <label class="flabel"><input type="checkbox" id="esHibrido" name="es_hibrido" value="1"> ¿Es un híbrido de dos razas?</label>
            <p class="hint">Un híbrido obtiene SOLO las pasivas primarias de ambas razas (ninguna secundaria).</p>
          </div>
          <div class="field dn" id="razaSecundariaWrap">
            <label class="flabel">Raza secundaria</label>
            <select name="raza_secundaria" id="razaSecundaria">
              <option value="">— elige —</option>
<?php foreach ($RAZAS as $rid => $r):
    $mod2 = json_encode($r['mod'] ?? array(), JSON_UNESCAPED_UNICODE);
    $mults2 = json_encode($r['multiplicadores'] ?? array(), JSON_UNESCAPED_UNICODE);
    $psbonus2 = (int)($r['ps_bonus'] ?? 0);
?>
              <option value="<?php echo $rid; ?>" data-mod='<?php echo htmlspecialchars_uni($mod2); ?>' data-mults='<?php echo htmlspecialchars_uni($mults2); ?>' data-ps-bonus="<?php echo $psbonus2; ?>" data-extra-bump="<?php echo !empty($r['extra_stat_bump']) ? '1' : '0'; ?>"><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="field dn" id="subOpcionWrap">
            <label class="flabel" id="subOpcionLabel">Pasiva secundaria</label>
            <div id="subOpcionGrid" class="race-grid"></div>
            <p class="hint">Solo se elige si tu raza es <b>pura</b> (sin híbrido): sustituye la pasiva secundaria genérica.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- PASO 2: CONCEPTO -->
    <div class="wiz-step" data-step="2">
      <div class="plate">
        <div class="plate-h"><span class="t">2. Nombre</span><span class="c">// quién es</span></div>
        <div class="plate-b">
          <div class="grid2">
            <div class="field"><label class="flabel">Nombre del personaje *</label><input type="text" name="nombre" maxlength="120" required value="<?php echo htmlspecialchars_uni($old['nombre'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Apodo (opcional)</label><input type="text" name="apodo" maxlength="60" value="<?php echo htmlspecialchars_uni($old['apodo'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Edad</label><input type="text" name="edad" maxlength="20" value="<?php echo htmlspecialchars_uni($old['edad'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Género</label><input type="text" name="genero" maxlength="40" value="<?php echo htmlspecialchars_uni($old['genero'] ?? ''); ?>"></div>
          </div>
          <p class="hint">¿Quieres que tu personaje tenga una "D." en su nombre? Elige la virtud <b class="c-paper">Voluntad de D.</b> en el siguiente paso (Virtudes y Defectos).</p>
        </div>
      </div>
    </div>

    <!-- PASO 3: STATS -->
    <div class="wiz-step" data-step="3">
      <div class="plate">
        <div class="plate-h"><span class="t">3. Estadísticas</span><span class="c">// 5 a 20+</span></div>
        <div class="plate-b">
          <p class="stats-hint">Todas empiezan en <b class="c-paper">5</b>. Reparte tus <b class="c-ember" id="psDisponiblesLabel">30 PS</b> como quieras (máx 15 PS por stat; 20 antes de pasivas).</p>
          <div id="statsContainer"></div>
          <div class="wiz-sum-bar"><span>Suma efectiva: <b id="statSum">0</b></span><span>Nivel inicial: <b id="statLevel">1 (todos empiezan a nivel 1)</b></span></div>
        </div>
      </div>
    </div>

    <!-- PASO 4: VIRTUDES Y DEFECTOS -->
    <div class="wiz-step" data-step="4">
      <div class="plate">
        <div class="plate-h"><span class="t">4. Virtudes y defectos</span><span class="c">// 6 PC iniciales</span></div>
        <div class="plate-b">
          <div class="pc-bar" id="pcBar">PC disponibles: <span class="pc-num" id="pcNum">6</span> <span class="pc-hint">(6 base − coste virtudes + devuelto por defectos)</span></div>

          <div class="vd-grid">
            <div class="vd-col" data-vdcol="virtudes">
              <div class="vd-col-h">Virtudes</div>
              <input type="search" class="vd-search" placeholder="Buscar virtud…" autocomplete="off">
              <div class="vd-empty">Ninguna virtud coincide con la búsqueda.</div>
<?php $vcat_i = 0; foreach ($VIRTUDES as $cat => $items):
    $cat_has_checked = false;
    foreach ($items as $vid => $v) { if (!empty($old['virtudes']) && in_array($vid, (array)$old['virtudes'], true)) { $cat_has_checked = true; break; } }
    $cat_open = ($vcat_i === 0) || $cat_has_checked;
    $vcat_i++;
?>
              <div class="cat-group<?php echo $cat_open ? ' cat-open' : ''; ?>">
                <div class="cat-h" data-toggle><span><?php echo htmlspecialchars_uni($cat); ?> <span class="cat-count">(<?php echo count($items); ?>)</span></span><span class="chev">▾</span></div>
                <div class="cat-body">
<?php foreach ($items as $vid => $v): $checked = !empty($old['virtudes']) && in_array($vid, (array)$old['virtudes'], true); ?>
                  <div class="item-row">
                    <input type="checkbox" name="virtudes[]" value="<?php echo $vid; ?>" id="chk_<?php echo $vid; ?>" data-coste="<?php echo (int)$v['coste']; ?>"<?php echo !empty($v['spec']) ? ' data-spec="1"' : ''; ?><?php echo $checked ? ' checked' : ''; ?>>
                    <div class="item-txt">
                      <label for="chk_<?php echo $vid; ?>" class="item-name"><?php echo htmlspecialchars_uni($v['nombre']); ?> <span class="badge cost">-<?php echo (int)$v['coste']; ?> PC</span></label>
                      <div class="item-desc"><?php echo htmlspecialchars_uni($v['desc']); ?></div>
<?php if (!empty($v['spec'])): ?>
                      <div class="item-spec<?php echo $checked ? ' show' : ''; ?>"><input type="text" name="virtud_spec_<?php echo $vid; ?>" maxlength="200" placeholder="Especifica..." value="<?php echo htmlspecialchars_uni($old['virtud_spec_' . $vid] ?? ''); ?>"></div>
<?php endif; ?>
                    </div>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>

            <div class="vd-col" data-vdcol="defectos">
              <div class="vd-col-h">Defectos</div>
              <input type="search" class="vd-search" placeholder="Buscar defecto…" autocomplete="off">
              <div class="vd-empty">Ningún defecto coincide con la búsqueda.</div>
<?php $dcat_i = 0; foreach ($DEFECTOS as $cat => $items):
    $cat_has_checked = false;
    foreach ($items as $did => $d) { if (!empty($old['defectos']) && in_array($did, (array)$old['defectos'], true)) { $cat_has_checked = true; break; } }
    $cat_open = ($dcat_i === 0) || $cat_has_checked;
    $dcat_i++;
?>
              <div class="cat-group<?php echo $cat_open ? ' cat-open' : ''; ?>">
                <div class="cat-h" data-toggle><span><?php echo htmlspecialchars_uni($cat); ?> <span class="cat-count">(<?php echo count($items); ?>)</span></span><span class="chev">▾</span></div>
                <div class="cat-body">
<?php foreach ($items as $did => $d): $checked = !empty($old['defectos']) && in_array($did, (array)$old['defectos'], true); ?>
                  <div class="item-row">
                    <input type="checkbox" name="defectos[]" value="<?php echo $did; ?>" id="chk_<?php echo $did; ?>" data-devuelve="<?php echo (int)$d['devuelve']; ?>"<?php echo !empty($d['spec']) ? ' data-spec="1"' : ''; ?><?php echo $checked ? ' checked' : ''; ?>>
                    <div class="item-txt">
                      <label for="chk_<?php echo $did; ?>" class="item-name"><?php echo htmlspecialchars_uni($d['nombre']); ?> <span class="badge back">+<?php echo (int)$d['devuelve']; ?> PC</span></label>
                      <div class="item-desc"><?php echo htmlspecialchars_uni($d['desc']); ?></div>
<?php if (!empty($d['spec'])): ?>
                      <div class="item-spec<?php echo $checked ? ' show' : ''; ?>"><input type="text" name="defecto_spec_<?php echo $did; ?>" maxlength="200" placeholder="Especifica..." value="<?php echo htmlspecialchars_uni($old['defecto_spec_' . $did] ?? ''); ?>"></div>
<?php endif; ?>
                    </div>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PASO 5: FACCIÓN -->
    <div class="wiz-step" data-step="5">
      <div class="plate">
        <div class="plate-h"><span class="t">5. Facción inicial</span><span class="c">// punto de partida</span></div>
        <div class="plate-b">
          <p class="hint mb-12"><b>Periodo de Gracia PvP</b> — durante tus primeros 15 días (off-rol) como jugador nuevo no puedes ser objetivo de una Invasión (PvP). Pasado ese tiempo, los mares son libres: atacar a alguien mucho más débil tiene consecuencias (Wanted, persecución Marine, represalias de facción).</p>
          <div class="fac-grid">
<?php foreach ($FACCIONES as $fid => $f): ?>
            <label class="fac-card">
              <input type="radio" name="faccion" value="<?php echo $fid; ?>" required>
              <div class="fac-name"><?php echo htmlspecialchars_uni($f['nombre']); ?></div>
              <div class="fac-desc"><?php echo htmlspecialchars_uni($f['desc']); ?></div>
              <div class="fac-adv"><?php echo htmlspecialchars_uni($f['ventaja']); ?></div>
            </label>
<?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- PASO 6: EQUIPO -->
    <div class="wiz-step" data-step="6">
      <div class="plate">
        <div class="plate-h"><span class="t">6. Equipo inicial</span><span class="c">// elige tu Pack</span></div>
        <div class="plate-b">
          <p class="hint mb-12">Elige el Pack de Equipo Inicial que mejor se adapte al concepto de tu personaje. Todos incluyen vestimenta básica de viaje, raciones para 5 días y <b class="c-paper">50.000 rupias</b> iniciales.</p>
          <div class="race-grid" id="packGrid">
<?php foreach ($PACKS as $pid => $p): ?>
            <label class="race-card">
              <input type="radio" name="pack_equipo" value="<?php echo $pid; ?>" required<?php echo (($old['pack_equipo'] ?? '') === $pid) ? ' checked' : ''; ?>>
              <div class="rc-body">
                <div class="rc-name"><?php echo htmlspecialchars_uni($p['nombre']); ?></div>
                <div class="rc-resumen"><?php echo htmlspecialchars_uni($p['resumen']); ?></div>
                <div class="rc-pas"><?php echo implode('<br>', array_map('htmlspecialchars_uni', $p['contenido'])); ?></div>
              </div>
            </label>
<?php endforeach; ?>
          </div>
          <div class="wiz-sum-bar">Rupias iniciales: <b id="rupiesOut">50.000</b></div>
          <p class="hint">Sin Fruta del Diablo ni Haki al inicio (se obtienen en juego).</p>
        </div>
      </div>
    </div>

    <!-- PASO 7: HISTORIA -->
    <div class="wiz-step" data-step="7">
      <div class="plate">
        <div class="plate-h"><span class="t">7. Historia</span><span class="c">// pasado, motivación, relaciones</span></div>
        <div class="plate-b">
          <div class="field"><label class="flabel">Pasado *</label><textarea name="historia_pasado" required class="historia-textarea" placeholder="De dónde viene, qué le ha pasado antes de empezar a rolear..."><?php echo htmlspecialchars_uni($old['historia_pasado'] ?? ''); ?></textarea></div>
          <div class="field"><label class="flabel">Motivación</label><textarea name="historia_motivacion" placeholder="Qué busca, qué lo empuja a moverse por el mundo..."><?php echo htmlspecialchars_uni($old['historia_motivacion'] ?? ''); ?></textarea></div>
          <div class="field"><label class="flabel">Relaciones</label><textarea name="historia_relaciones" placeholder="Vínculos, familia, tripulación, enemigos..."><?php echo htmlspecialchars_uni($old['historia_relaciones'] ?? ''); ?></textarea></div>
        </div>
      </div>
    </div>

    <!-- PASO 8: RESUMEN -->
    <div class="wiz-step" data-step="8">
      <div class="plate">
        <div class="plate-h"><span class="t">8. Revisión final</span><span class="c">// antes de enviar</span></div>
        <div class="plate-b" id="wizSummary"></div>
      </div>
    </div>

    <div class="wiz-nav">
      <button type="button" class="btn btn-ghost" id="wizPrev">&larr; Anterior</button>
      <div class="wiz-err" id="wizErr"></div>
      <button type="button" class="btn btn-hot" id="wizNext">Siguiente &rarr;</button>
      <button type="submit" class="btn btn-hot dn" id="wizSubmit">Enviar a revisión</button>
    </div>
  </form>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<?php if ($loggedin && $hay_hueco): ?>
<script>
(function(){
  var STAT_LABELS = <?php echo json_encode($STATS, JSON_UNESCAPED_UNICODE); ?>;
  var PS_BASE = 30;
  var PS_HUMANO = 40;
  var STAT_BASE = 5;
  var STAT_CAP = 20;
  var STEP_NAMES = ['Raza','Concepto','Stats','Virtudes','Facción','Equipo','Historia','Resumen'];
  var steps = Array.prototype.slice.call(document.querySelectorAll('.wiz-step'));
  var cur = 1;
  var form = document.getElementById('wizForm');

  // ---- Progreso ----
  var progWrap = document.getElementById('wizProgress');
  STEP_NAMES.forEach(function(name, i){
    var d = document.createElement('div');
    d.className = 'wiz-step-dot';
    d.innerHTML = '<span class="n">' + (i+1) + '</span>' + name;
    progWrap.appendChild(d);
  });

  function renderProgress(){
    var dots = progWrap.children;
    for (var i = 0; i < dots.length; i++){
      dots[i].classList.toggle('on', i+1 === cur);
      dots[i].classList.toggle('done', i+1 < cur);
    }
  }

  function showStep(n){
    steps.forEach(function(s){ s.classList.toggle('on', parseInt(s.dataset.step,10) === n); });
    document.getElementById('wizPrev').style.visibility = (n === 1) ? 'hidden' : 'visible';
    document.getElementById('wizNext').style.display = (n === steps.length) ? 'none' : 'inline-block';
    document.getElementById('wizSubmit').style.display = (n === steps.length) ? 'inline-block' : 'none';
    document.getElementById('wizErr').textContent = '';
    renderProgress();
    if (n === 3) renderStats();
    if (n === steps.length) renderSummary();
    window.scrollTo({top: form.offsetTop - 70, behavior:'smooth'});
  }

  function validateStep(n){
    if (n === 1){
      var r1chk = document.querySelector('input[name=raza_principal]:checked');
      if (!r1chk) return 'Elige una raza principal.';
      if (document.getElementById('esHibrido').checked && !document.getElementById('razaSecundaria').value) return 'Elige la raza secundaria del híbrido.';
      if (!hibChk.checked){
        var subop = JSON.parse(r1chk.dataset.subop || '{}');
        if (Object.keys(subop).length && !document.querySelector('input[name=sub_opcion]:checked')) return 'Elige una opción para la pasiva secundaria de tu raza.';
      }
    }
    if (n === 2){
      var nombre = form.querySelector('[name=nombre]').value.trim();
      if (nombre.length < 3) return 'Escribe un nombre de al menos 3 caracteres.';
    }
    if (n === 3){
      var totalPs = 0;
      form.querySelectorAll('.ps-input').forEach(function(inp){ totalPs += parseInt(inp.value, 10) || 0; });
      if (totalPs <= 0) return 'Reparte al menos 1 Punto de Stat (PS).';
    }
    if (n === 4){
      var bar = document.getElementById('pcBar');
      if (bar.classList.contains('bad')) return 'Te has pasado de Puntos de Creación (PC). Ajusta tu selección.';
      var missingSpec = false;
      form.querySelectorAll('input[data-spec="1"]:checked').forEach(function(chk){
        var wrap = chk.closest('.item-row').querySelector('.item-spec input');
        if (wrap && !wrap.value.trim()) missingSpec = true;
      });
      if (missingSpec) return 'Rellena el campo de detalle de las virtudes/defectos marcadas.';
    }
    if (n === 5){
      if (!document.querySelector('input[name=faccion]:checked')) return 'Elige una facción inicial.';
    }
    if (n === 6){
      if (!document.querySelector('input[name=pack_equipo]:checked')) return 'Elige un Pack de Equipo Inicial.';
    }
    if (n === 7){
      if (form.querySelector('[name=historia_pasado]').value.trim().length < 80) return 'Cuenta el pasado de tu personaje con algo más de detalle (mínimo ~80 caracteres).';
    }
    return '';
  }

  document.getElementById('wizNext').addEventListener('click', function(){
    var err = validateStep(cur);
    if (err){ document.getElementById('wizErr').textContent = err; return; }
    cur = Math.min(cur + 1, steps.length);
    showStep(cur);
  });
  document.getElementById('wizPrev').addEventListener('click', function(){
    cur = Math.max(cur - 1, 1);
    showStep(cur);
  });

  // ---- Híbrido toggle ----
  var hibChk = document.getElementById('esHibrido');
  var razaSecWrap = document.getElementById('razaSecundariaWrap');
  hibChk.addEventListener('change', function(){
    razaSecWrap.style.display = hibChk.checked ? 'block' : 'none';
    if (!hibChk.checked) document.getElementById('razaSecundaria').value = '';
    renderSubOpciones();
  });

  // ---- Sub-opción racial (Herencia Tribal / Linaje Colosal) ----
  var subOpcionWrap = document.getElementById('subOpcionWrap');
  var subOpcionGrid = document.getElementById('subOpcionGrid');
  var subOpcionLabel = document.getElementById('subOpcionLabel');
  function renderSubOpciones(){
    var r1 = document.querySelector('input[name=raza_principal]:checked');
    var prevChecked = subOpcionGrid.querySelector('input:checked');
    var prevVal = prevChecked ? prevChecked.value : '';
    subOpcionGrid.innerHTML = '';
    if (!r1 || hibChk.checked){ subOpcionWrap.style.display = 'none'; renderStats(); return; }
    var subop = JSON.parse(r1.dataset.subop || '{}');
    var keys = Object.keys(subop);
    if (!keys.length){ subOpcionWrap.style.display = 'none'; renderStats(); return; }
    subOpcionLabel.textContent = 'Pasiva secundaria — ' + r1.dataset.nombre;
    keys.forEach(function(k){
      var opt = subop[k];
      var label = document.createElement('label');
      label.className = 'race-card';
      label.innerHTML = '<input type="radio" name="sub_opcion" value="' + k + '"' + (k === prevVal ? ' checked' : '') + ' required>' +
        '<div class="rc-body"><div class="rc-name">' + opt.nombre + '</div><div class="rc-pas">' + opt.desc + '</div></div>';
      subOpcionGrid.appendChild(label);
    });
    subOpcionWrap.style.display = 'block';
    subOpcionGrid.querySelectorAll('input[name=sub_opcion]').forEach(function(r){ r.addEventListener('change', renderStats); });
    renderStats();
  }

  // ---- Stats en vivo ----
  function getRazaData(){
    var r1 = document.querySelector('input[name=raza_principal]:checked');
    var hib = hibChk.checked;
    var r2opt = hib ? document.getElementById('razaSecundaria').selectedOptions[0] : null;
    var mults1 = r1 ? JSON.parse(r1.dataset.mults || '{}') : {};
    var multssec1 = {};
    if (r1 && !hib){
      var subChk = subOpcionGrid.querySelector('input[name=sub_opcion]:checked');
      if (subChk){
        var subop = JSON.parse(r1.dataset.subop || '{}');
        multssec1 = (subop[subChk.value] && subop[subChk.value].mod) ? {} : JSON.parse(r1.dataset.multssec || '{}');
      } else {
        multssec1 = JSON.parse(r1.dataset.multssec || '{}');
      }
    }
    var mults2 = (r2opt && r2opt.value) ? JSON.parse(r2opt.dataset.mults || '{}') : {};
    var psR1 = r1 ? parseInt(r1.dataset.psBonus, 10) || 0 : 0;
    var psR2 = (r2opt && r2opt.value) ? parseInt(r2opt.dataset.psBonus, 10) || 0 : 0;
    var psDisponibles = PS_BASE + psR1 + psR2;
    return {mults1: mults1, multssec1: multssec1, mults2: mults2, psDisponibles: psDisponibles};
  }

  function renderStats(){
    var container = document.getElementById('statsContainer');
    var rd = getRazaData();
    var psDisponibles = rd.psDisponibles;
    document.getElementById('psDisponiblesLabel').textContent = psDisponibles + ' PS';

    var prevPs = {};
    container.querySelectorAll('input.ps-input').forEach(function(inp){
      prevPs[inp.dataset.stat] = parseInt(inp.value, 10) || 0;
    });

    container.innerHTML = '';
    Object.keys(STAT_LABELS).forEach(function(pk){
      var pillar = STAT_LABELS[pk];
      var pdiv = document.createElement('div');
      pdiv.className = 'stats-pillar';
      var h = document.createElement('div');
      h.className = 'stats-pillar-h';
      h.textContent = pillar.label;
      pdiv.appendChild(h);

      Object.keys(pillar.stats).forEach(function(sig){
        var prev = prevPs[sig] || 0;
        var maxPs = STAT_CAP - STAT_BASE;
        var row = document.createElement('div');
        row.className = 'stat-row';
        row.innerHTML =
          '<span class="stat-name">' + pillar.stats[sig] + ' <span class="sig">' + sig + '</span></span>' +
          '<span class="stat-base">Base: ' + STAT_BASE + '</span>' +
          '<input type="number" name="ps_stats[' + sig + ']" class="ps-input" data-stat="' + sig + '" value="' + prev + '" min="0" max="' + maxPs + '" step="1">' +
          '<span class="stat-eff" data-eff="' + sig + '">' + STAT_BASE + '</span>';
        pdiv.appendChild(row);
      });
      container.appendChild(pdiv);
    });

    var psInputs = container.querySelectorAll('.ps-input');
    function aplicarMults(sinPasivas, sig){
      var v = sinPasivas;
      if (rd.mults1[sig]) v = Math.round(v * rd.mults1[sig]);
      if (rd.multssec1[sig]) v = Math.round(v * rd.multssec1[sig]);
      if (rd.mults2[sig]) v = Math.round(v * rd.mults2[sig]);
      return v;
    }

    function recompute(){
      var totalUsado = 0;
      var sum = 0;
      psInputs.forEach(function(inp){
        var sig = inp.dataset.stat;
        var ps = Math.max(0, parseInt(inp.value, 10) || 0);
        var maxPs = STAT_CAP - STAT_BASE;
        if (ps > maxPs){ ps = maxPs; inp.value = maxPs; }
        totalUsado += ps;
      });
      var restante = psDisponibles - totalUsado;
      psInputs.forEach(function(inp){
        var sig = inp.dataset.stat;
        var ps = parseInt(inp.value, 10) || 0;
        var maxPosible = ps + Math.max(0, restante);
        inp.max = Math.min(STAT_CAP - STAT_BASE, maxPosible);
        var sinPasivas = STAT_BASE + ps;
        var eff = aplicarMults(sinPasivas, sig);
        inp.closest('.stat-row').querySelector('.stat-eff').textContent = eff;
        sum += eff;
      });
      document.getElementById('statSum').textContent = sum;
      document.getElementById('statLevel').textContent = '1 (todos empiezan a nivel 1)';
    }

    psInputs.forEach(function(inp){ inp.addEventListener('input', recompute); });
    recompute();
  }

  document.querySelectorAll('input[name=raza_principal]').forEach(function(r){ r.addEventListener('change', renderSubOpciones); });
  document.getElementById('razaSecundaria').addEventListener('change', renderStats);

  // ---- PC bar (virtudes/defectos) ----
  var PC_BASE = <?php echo (int)$PC_BASE; ?>;
  function recomputePc(){
    var gastado = 0, devuelto = 0;
    document.querySelectorAll('input[data-coste]:checked').forEach(function(c){ gastado += parseInt(c.dataset.coste,10)||0; });
    document.querySelectorAll('input[data-devuelve]:checked').forEach(function(c){ devuelto += parseInt(c.dataset.devuelve,10)||0; });
    var balance = PC_BASE - gastado + devuelto;
    document.getElementById('pcNum').textContent = balance;
    document.getElementById('pcBar').classList.toggle('bad', balance < 0);

    // Adinerado prerequisite chain
    var r1c = document.getElementById('chk_V-RIQ-01');
    var r2c = document.getElementById('chk_V-RIQ-02');
    var r3c = document.getElementById('chk_V-RIQ-03');
    if (r2c) r2c.disabled = !(r1c && r1c.checked) && !r2c.checked;
    if (r3c) r3c.disabled = !(r2c && r2c.checked) && !r3c.checked;

    // rupies live
    var rupies = 50000;
    if (r1c && r1c.checked) rupies += 1000000;
    if (r2c && r2c.checked) rupies += 3000000;
    if (r3c && r3c.checked) rupies += 10000000;
    var out = document.getElementById('rupiesOut');
    if (out) out.textContent = rupies.toLocaleString('es-ES');
  }
  document.querySelectorAll('input[data-coste],input[data-devuelve]').forEach(function(c){
    c.addEventListener('change', function(){
      if (c.dataset.spec === '1'){
        var specBox = c.closest('.item-row').querySelector('.item-spec');
        if (specBox) specBox.classList.toggle('show', c.checked);
      }
      recomputePc();
    });
  });
  recomputePc();

  // ---- Categorías colapsables (Virtudes/Defectos) ----
  document.querySelectorAll('[data-toggle]').forEach(function(h){
    h.addEventListener('click', function(){
      h.closest('.cat-group').classList.toggle('cat-open');
    });
  });

  // ---- Búsqueda dinámica dentro de cada columna (Virtudes / Defectos) ----
  document.querySelectorAll('.vd-search').forEach(function(inp){
    var col = inp.closest('.vd-col');
    var empty = col.querySelector('.vd-empty');
    inp.addEventListener('input', function(){
      var q = inp.value.trim().toLowerCase();
      var anyVisible = false;
      col.querySelectorAll('.cat-group').forEach(function(g){
        var groupMatch = false;
        g.querySelectorAll('.item-row').forEach(function(row){
          var name = row.querySelector('.item-name').textContent.toLowerCase();
          var match = q === '' || name.indexOf(q) !== -1;
          row.style.display = match ? '' : 'none';
          if (match) groupMatch = true;
        });
        g.style.display = groupMatch ? '' : 'none';
        if (q !== '') g.classList.toggle('cat-open', groupMatch);
        if (groupMatch) anyVisible = true;
      });
      empty.style.display = anyVisible ? 'none' : 'block';
    });
  });

  // ---- Resumen final ----
  function renderSummary(){
    var out = document.getElementById('wizSummary');
    var r1 = document.querySelector('input[name=raza_principal]:checked');
    var r2opt = hibChk.checked ? document.getElementById('razaSecundaria').selectedOptions[0] : null;
    var razaTxt = r1 ? r1.dataset.nombre : '—';
    if (r2opt && r2opt.value) razaTxt += ' × ' + r2opt.textContent + ' (híbrido)';

    var nombre = form.querySelector('[name=nombre]').value || '—';
    var faccionEl = document.querySelector('input[name=faccion]:checked');
    var faccionTxt = faccionEl ? faccionEl.closest('.fac-card').querySelector('.fac-name').textContent : '—';
    var packEl = document.querySelector('input[name=pack_equipo]:checked');
    var packTxt = packEl ? packEl.closest('.race-card').querySelector('.rc-name').textContent : '—';

    var virtudesNames = [];
    document.querySelectorAll('input[data-coste]:checked').forEach(function(c){ virtudesNames.push(c.closest('.item-row').querySelector('.item-name').firstChild.textContent.trim()); });
    var defectosNames = [];
    document.querySelectorAll('input[data-devuelve]:checked').forEach(function(c){ defectosNames.push(c.closest('.item-row').querySelector('.item-name').firstChild.textContent.trim()); });

    out.innerHTML =
      '<div class="sum-block"><h4>Identidad</h4>' +
      '<div class="line"><b>Nombre</b>' + nombre + '</div>' +
      '<div class="line"><b>Raza</b>' + razaTxt + '</div>' +
      '<div class="line"><b>Facción</b>' + faccionTxt + '</div></div>' +
      '<div class="sum-block"><h4>Estadísticas</h4>' +
      '<div class="line"><b>Suma total</b>' + document.getElementById('statSum').textContent + '</div>' +
      '<div class="line"><b>Nivel inicial</b>1 (el nivel se gana jugando)</div></div>' +
      '<div class="sum-block"><h4>Virtudes (' + virtudesNames.length + ')</h4><div class="line">' + (virtudesNames.join(', ') || 'Ninguna') + '</div></div>' +
      '<div class="sum-block"><h4>Defectos (' + defectosNames.length + ')</h4><div class="line">' + (defectosNames.join(', ') || 'Ninguno') + '</div></div>' +
      '<div class="sum-block"><h4>Equipo</h4><div class="line"><b>Pack</b>' + packTxt + '</div><div class="line"><b>Rupias</b>' + document.getElementById('rupiesOut').textContent + '</div></div>';
  }

  showStep(1);
})();
</script>
<?php endif; ?>

</body>
</html>
