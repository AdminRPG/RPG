<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

require_once MYBB_ROOT . 'inc/ope_user_init.php';

$staff_level = ope_get_staff_level($uid);
$initials    = ope_get_initials($mybb->user['username'] ?? '');
$initials_e  = htmlspecialchars_uni($initials);

$RAZAS       = ope_rol_razas();
$IDENTIDADES = ope_rol_identidades();
$FAMILIAS    = ope_rol_familias_arma();
$ARMAS       = ope_rol_armas();
$FACCIONES   = ope_rol_facciones();
$RASGOS_GENERALES = ope_rol_rasgos_generales();
$RASGOS_RACIALES  = ope_rol_rasgos_raciales();
$RASGO_PURO       = ope_rol_rasgo_puro();
$DOTES_INNATAS    = ope_rol_dotes_innatas();
$FL_DEFECTOS      = ope_rol_fl_defectos();
$DEF_HIBRIDACION  = ope_rol_defectos_hibridacion();
$PL_PRESUP        = ope_rol_pl_presupuesto();
$PACKS       = ope_rol_packs_equipo();
$STATS       = ope_rol_stats();
$STAT_KEYS   = ope_rol_stat_keys();
$PS_TOTAL    = 20;
$STAT_BASE   = 1;
$STAT_CAP    = 5; // antes del perfil de linaje (positivo o negativo)
$BERRIES_BASE = ope_rol_berries_iniciales();
$RUPIES_BASE  = $BERRIES_BASE; // alias legacy
$MECH_HELP    = ope_rol_mechanics_help();

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

$errores = array();
$ok = false;
$old = $_POST;

function ope_rol_clean($s, $max = 4000)
{
    $s = trim((string)$s);
    if (function_exists('mb_substr')) {
        $s = mb_substr($s, 0, $max, 'UTF-8');
    } else {
        $s = substr($s, 0, $max);
    }
    return $s;
}

/**
 * Renderiza una fila comprable del Factor Linaje (rasgo o defecto) con PL.
 * $name  = name del input (p.ej. 'rasgos_generales[]' o 'rasgo_puro').
 * $pl    = valor en PL (positivo rasgo, negativo defecto).
 * $opts  = spec(bool), cyborg(bool), linaje(string), req(string), placeholder(string).
 */
function ope_wiz_fl_row($name, $id, $pl, $nombre, $efecto, $opts = array())
{
    $pl = (int)$pl;
    $chkId = 'chk_' . preg_replace('/[^a-z0-9_-]/i', '', $id);
    $badgeClass = $pl >= 0 ? 'cost' : 'back';
    $badgeTxt = ($pl > 0 ? '+' : '') . $pl;
    $spec = !empty($opts['spec']);
    $attrs = ' data-pl="' . $pl . '"';
    if ($spec) { $attrs .= ' data-spec="1"'; }
    if (!empty($opts['cyborg'])) { $attrs .= ' data-cyborg="1"'; }
    if (!empty($opts['linaje'])) { $attrs .= ' data-linaje="' . htmlspecialchars_uni($opts['linaje']) . '"'; }
    if (!empty($opts['req'])) { $attrs .= ' data-req="' . htmlspecialchars_uni($opts['req']) . '"'; }
    $rowClass = 'item-row';
    if (!empty($opts['cyborg'])) { $rowClass .= ' item-row--cyborg'; }
    if (!empty($opts['linaje'])) { $rowClass .= ' fl-linaje-row'; }
    $ph = isset($opts['placeholder']) ? $opts['placeholder'] : 'Especifica...';
    $ide = htmlspecialchars_uni($id);
    $out  = '<div class="' . $rowClass . '"' . (!empty($opts['linaje']) ? ' data-linaje-row="' . htmlspecialchars_uni($opts['linaje']) . '"' : '') . '>';
    $out .= '<input type="checkbox" name="' . $name . '" value="' . $ide . '" id="' . $chkId . '"' . $attrs . '>';
    $out .= '<div class="item-txt">';
    $out .= '<label for="' . $chkId . '" class="item-name">' . htmlspecialchars_uni($nombre) . ' <span class="badge ' . $badgeClass . '">' . $badgeTxt . '</span>';
    if (!empty($opts['cyborg'])) { $out .= ' <button type="button" class="ope-help-btn" data-ope-help="cyborg" title="Reglas Cyborg">?</button>'; }
    $out .= '</label>';
    $out .= '<div class="item-desc">' . htmlspecialchars_uni($efecto) . '</div>';
    if ($spec) {
        $out .= '<div class="item-spec"><input type="text" name="spec_' . $ide . '" maxlength="200" placeholder="' . htmlspecialchars_uni($ph) . '"></div>';
    }
    $out .= '</div></div>';
    return $out;
}

if ($loggedin && $mybb->request_method === 'post' && $hay_hueco) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $errores[] = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } else {
        $raza = $mybb->get_input('raza');
        $identidad = $mybb->get_input('identidad');
        $familia_arma = $mybb->get_input('familia_arma');
        $arma = $mybb->get_input('arma');

        if (!isset($RAZAS[$raza])) {
            $errores[] = 'Elige un linaje válido.';
        }
        if (!isset($IDENTIDADES[$identidad])) {
            $errores[] = 'Elige una Identidad Eternal válida.';
        }
        if (!isset($FAMILIAS[$familia_arma])) {
            $errores[] = 'Elige una Familia de Arma válida.';
        }
        if (!isset($ARMAS[$arma])) {
            $errores[] = 'Elige un arma válida.';
        } elseif (isset($FAMILIAS[$familia_arma]) && ($ARMAS[$arma]['familia'] ?? '') !== $familia_arma) {
            $errores[] = 'El arma debe pertenecer a la familia Eternal elegida.';
        }

        $nombre = ope_rol_clean($mybb->get_input('nombre'), 120);
        $apodo = ope_rol_clean($mybb->get_input('apodo'), 60);
        $edad = ope_rol_clean($mybb->get_input('edad'), 20);
        $genero = ope_rol_clean($mybb->get_input('genero'), 40);
        $pb = ope_rol_clean($mybb->get_input('pb'), 120);
        $desc_fisica = ope_rol_clean($mybb->get_input('desc_fisica'), 3000);
                $desc_psicologica = ope_rol_clean($mybb->get_input('desc_psicologica'), 3000);
        $notas = ope_rol_clean($mybb->get_input('notas'), 3000);

        if ($nombre === '' || (function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre)) < 3) {
            $errores[] = 'El nombre del personaje debe tener al menos 3 caracteres.';
        }
        if ($nombre !== '' && $db->table_exists('rol_personajes')) {
            $dupe = $db->simple_select('rol_personajes', 'pid', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
            if ($db->num_rows($dupe)) {
                $errores[] = 'Ya existe un personaje con ese nombre.';
            }
        }

        $stats_base = ope_rol_stats_base();
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
            $errores[] = 'Reparte al menos 1 Punto de Stat.';
        }
        if ($ps_total_usado > $PS_TOTAL) {
            $errores[] = "Has repartido {$ps_total_usado} puntos, pero solo dispones de {$PS_TOTAL}.";
        }

        $stats_con_puntos = $stats_base;
        foreach ($ps_asignados as $sk => $v) {
            $stats_con_puntos[$sk] = ($stats_con_puntos[$sk] ?? 1) + $v;
        }
        $stats_con_racial = $stats_con_puntos;
        $racial_mods = isset($RAZAS[$raza]['bonus']['mods']) && is_array($RAZAS[$raza]['bonus']['mods']) ? $RAZAS[$raza]['bonus']['mods'] : array();
        foreach ($racial_mods as $rk => $rv) {
            if (isset($stats_con_racial[$rk])) {
                $stats_con_racial[$rk] = max(1, $stats_con_racial[$rk] + (int)$rv);
            }
        }
        foreach ($stats_con_puntos as $sk => $sv) {
            if ($sv > $STAT_CAP) {
                $errores[] = "{$sk} no puede superar {$STAT_CAP} con puntos de creación (antes del racial).";
            }
        }

        $faccion = $mybb->get_input('faccion');
        if (!isset($FACCIONES[$faccion])) {
            $errores[] = 'Elige una facción inicial válida.';
        }

        // --- Factor Linaje: TODO se compra con PL (suma cero) ---
        $pureza  = $mybb->get_input('pureza') === 'hibrida' ? 'hibrida' : 'pura';
        $linaje2 = $mybb->get_input('linaje2');

        // Recolectar los detalles libres (spec_<id>) enviados.
        $fl_specs = array();
        foreach ($_POST as $pk => $pv) {
            if (is_string($pv) && strncmp($pk, 'spec_', 5) === 0) {
                $fl_specs[substr($pk, 5)] = ope_rol_clean($pv, 200);
            }
        }

        $fl_in = array(
            'pureza'               => $pureza,
            'linaje'               => $raza,
            'linaje2'              => $linaje2,
            'rasgos_generales'     => $mybb->get_input('rasgos_generales', MyBB::INPUT_ARRAY),
            'rasgos_raciales'      => $mybb->get_input('rasgos_raciales', MyBB::INPUT_ARRAY),
            'rasgo_puro'           => $mybb->get_input('rasgo_puro'),
            'dotes_innatas'        => $mybb->get_input('dotes_innatas', MyBB::INPUT_ARRAY),
            'defectos'             => $mybb->get_input('defectos', MyBB::INPUT_ARRAY),
            'defectos_hibridacion' => $mybb->get_input('defectos_hibridacion', MyBB::INPUT_ARRAY),
            'specs'                => $fl_specs,
        );
        $fl = ope_pj_validar_factor_linaje($fl_in);
        foreach ($fl['errores'] as $fl_err) {
            $errores[] = $fl_err;
        }
        $dotes_sel  = $fl['seleccion'];
        $suma_dotes = $fl['pl_total'];

        if (isset($dotes_sel['cyborg'])) {
            $cy_slot = ope_rol_clean($mybb->get_input('cyborg_slot'), 20);
            $slots_ok = array('brazo', 'pierna', 'ojo', 'torso');
            if (!in_array($cy_slot, $slots_ok, true)) {
                $errores[] = 'Cyborg: elige el slot del miembro mecánico (Brazo, Pierna, Ojo o Torso).';
            }
        }

        $pack_equipo = $mybb->get_input('pack_equipo');
        if (!isset($PACKS[$pack_equipo])) {
            $errores[] = 'Elige un Pack de Equipo Inicial válido.';
        }

        $historia_pasado = ope_rol_clean($mybb->get_input('historia_pasado'), 6000);
        $min_len = function_exists('mb_strlen') ? mb_strlen($historia_pasado, 'UTF-8') : strlen($historia_pasado);
        if ($min_len < 80) {
            $errores[] = 'Cuenta el pasado de tu personaje con algo más de detalle (mínimo ~80 caracteres).';
        }

        if (empty($errores) && $db->table_exists('rol_personajes')) {
            $slug = my_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
            $slug = trim($slug, '-');

            $stats_json_data = array();
            foreach ($STAT_KEYS as $sk) {
                $stats_json_data[$sk] = $stats_con_racial[$sk] ?? 1;
            }

            $datos = array(
                'raza' => $raza,
                'raza_mods' => $racial_mods,
                'pureza' => $pureza,
                'linaje2' => ($pureza === 'hibrida') ? $linaje2 : '',
                'linajes' => $fl['linajes'],
                'identidad' => $identidad,
                'familia_arma' => $familia_arma,
                'arma' => $arma,
                'arbol_identidad' => $IDENTIDADES[$identidad]['arbol'] ?? '',
                'arbol_arma' => $FAMILIAS[$familia_arma]['arbol'] ?? '',
                'faccion' => $faccion,
                'factor_linaje' => $dotes_sel,
                'virtudes_defectos' => $dotes_sel, // alias compat (ficha/legacy)
                'dotes' => $dotes_sel, // alias
                'suma_dotes' => $suma_dotes,
                'pl_total' => $suma_dotes,
                'pack_equipo' => $pack_equipo,
                'ps_asignados' => $ps_asignados,
                'ps_total_usado' => $ps_total_usado,
                'cyborg' => isset($dotes_sel['cyborg']),
                'cyborg_slot' => isset($dotes_sel['cyborg'])
                    ? ope_rol_clean($mybb->get_input('cyborg_slot'), 20)
                    : '',
            );
            $inventario = array('pack_equipo' => $pack_equipo);
            $economia = array('berries' => $BERRIES_BASE, 'rupies' => $BERRIES_BASE);
                        $bio = array(
                'historia' => $historia_pasado,
                'apodo' => $apodo,
                'edad' => $edad,
                'genero' => $genero,
                'pb' => $pb,
                'desc_fisica' => $desc_fisica,
                'desc_psicologica' => $desc_psicologica,
                'notas' => $notas,
            );

            $ins = array(
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
            );
            if ($db->field_exists('pt_disponibles', 'rol_personajes')) {
                $ins['pt_disponibles'] = 0;
            }
            if ($db->field_exists('pt_gastados', 'rol_personajes')) {
                $ins['pt_gastados'] = 0;
            }
            $pid = $db->insert_query('rol_personajes', $ins);

            // Fruta opcional: tirada obligatoria si eligió «Tirar».
            $fruta_opcion = $mybb->get_input('fruta_opcion');
            $fruta_roll_msg = '';
            if ($pid && $fruta_opcion === 'tirar' && function_exists('ope_fruta_roll_aleatoria')) {
                $fr = ope_fruta_roll_aleatoria((int) $pid);
                if (empty($fr['ok'])) {
                    // No bloqueamos el PJ; el staff puede asignar luego.
                    $fruta_roll_msg = (string) ($fr['msg'] ?? 'No se pudo asignar fruta.');
                } else {
                    $fruta_roll_msg = (string) ($fr['msg'] ?? '');
                }
            }

            if ($pid && $db->table_exists('rol_tramites')) {
                $nuevo_pid = (int)$pid;
                $existing = $db->simple_select('rol_tramites', 'tid', "pid = {$nuevo_pid} AND tipo = 'crear_personaje' AND estado = 'pendiente'", array('limit' => 1));
                if ($db->num_rows($existing) == 0) {
                    $db->insert_query('rol_tramites', array(
                        'uid' => $uid,
                        'pid' => $nuevo_pid,
                        'tipo' => 'crear_personaje',
                        'estado' => 'pendiente',
                        'datos' => $db->escape_string(json_encode(array(
                            'nombre' => $nombre,
                            'raza' => $raza,
                            'pureza' => $pureza,
                            'linaje2' => ($pureza === 'hibrida') ? $linaje2 : '',
                            'identidad' => $identidad,
                            'familia_arma' => $familia_arma,
                            'arma' => $arma,
                            'faccion' => $faccion,
                        ), JSON_UNESCAPED_UNICODE)),
                        'dateline' => TIME_NOW,
                        'lastedit' => TIME_NOW,
                    ));
                }
            }
            $ok = true;
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
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-crear-personaje">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personajes</a>
    <span class="sep">&#8250;</span>
    <b>Crear</b>
  </div>
</div>

<div class="wrap">

  <div class="shead">
    <h1>Crear personaje</h1>
    <span class="code">// one piece eternal</span>
    <span class="rule"></span>
  </div>

<?php if (!$loggedin): ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Acceso requerido</span><span class="c">// acceso</span></div>
    <div class="plate-b">
      <div class="pj-empty">
        <span class="anvil"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
        <div class="big">Accede para crear un personaje</div>
        <p>Necesitas una cuenta en el foro para crear un personaje.</p>
        <div class="acts">
          <a href="<?php echo $bburl; ?>/member.php?action=register" class="btn btn-hot">Regístrate</a>
          <a href="<?php echo $bburl; ?>/member.php?action=login" class="btn btn-ghost">Acceder</a>
        </div>
      </div>
    </div>
  </div>
<?php elseif (!$hay_hueco): ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Sin huecos disponibles</span><span class="c">// <?php echo $usados; ?>/<?php echo $slots; ?></span></div>
    <div class="plate-b">
      <div class="pj-empty">
        <span class="anvil"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
        <div class="big">Ya usas todos tus huecos de personaje</div>
        <p>Tu cuenta tiene <?php echo $slots; ?> hueco(s) y ya los ocupas todos (<?php echo $usados; ?>). Solicita un hueco adicional en trámites.</p>
        <div class="acts">
          <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Ver mis personajes</a>
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

  <p class="wiz-lead">
    Forja tu personaje en <b>10 pasos</b>: identidad personal, linaje (Factor Linaje), Identidad Eternal, familia de arma, atributos, facción, virtudes/defectos, Akuma no Mi (opcional), equipo y revisión.
    Al enviar, tu ficha entra en <b>revisión</b> del staff.
  </p>

  <div class="wiz-forge">
    <aside class="wiz-preview" aria-label="Vista previa del personaje">
      <div class="wiz-card" id="wizCard" data-element="">
        <div class="wiz-card-art">
          <div class="wiz-card-ph" id="wizInitial">?</div>
          <div class="wiz-card-veil"></div>
          <span class="wiz-card-el" title="Identidad" aria-hidden="true"><i></i></span>
          <div class="wiz-card-lv"><span>Lv</span><b>1</b></div>
        </div>
        <div class="wiz-card-meta">
          <h2 class="wiz-card-name" id="wizNamePrev">Sin nombre</h2>
          <div class="wiz-card-chips" id="wizChips">
            <span class="wiz-chip">Borrador</span>
          </div>
          <div class="wiz-card-stats">
            <div class="wiz-st"><span>FUE</span><b id="wizStatFue">—</b></div>
            <div class="wiz-st"><span>RES</span><b id="wizStatRes">—</b></div>
            <div class="wiz-st"><span>AGI</span><b id="wizStatAgi">—</b></div>
            <div class="wiz-st"><span>VOL</span><b id="wizStatVol">—</b></div>
          </div>
        </div>
        <div class="wiz-card-foot">
          <div class="wiz-card-line"><b>Linaje</b><span id="wizLineRaza">—</span></div>
          <div class="wiz-card-line"><b>Identidad</b><span id="wizLineId">—</span></div>
          <div class="wiz-card-line"><b>Arma</b><span id="wizLineArma">—</span></div>
          <div class="wiz-card-line"><b>Familia</b><span id="wizLineFam">—</span></div>
          <div class="wiz-card-line"><b>Facción</b><span id="wizLineFac">—</span></div>
        </div>
      </div>
    </aside>

    <div class="wiz-main">
      <div class="wiz-progress" id="wizProgress"></div>

      <form method="post" action="<?php echo $bburl; ?>/crear-personaje.php" id="wizForm">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">

        <!-- PASO 1: IDENTIDAD -->
        <div class="wiz-step on" data-step="1">
          <div class="plate">
            <div class="plate-h"><span class="t">1. Identidad</span><span class="c">// quién eres</span></div>
            <div class="plate-b">
              <div class="grid2">
                <div class="field"><label class="flabel">Nombre del personaje *</label><input type="text" name="nombre" id="wizNombreInput" maxlength="120" required value="<?php echo htmlspecialchars_uni($old['nombre'] ?? ''); ?>"></div>
                <div class="field"><label class="flabel">Apodo (opcional)</label><input type="text" name="apodo" maxlength="60" value="<?php echo htmlspecialchars_uni($old['apodo'] ?? ''); ?>"></div>
                <div class="field"><label class="flabel">Edad</label><input type="text" name="edad" maxlength="20" value="<?php echo htmlspecialchars_uni($old['edad'] ?? ''); ?>"></div>
                <div class="field"><label class="flabel">Género</label><input type="text" name="genero" maxlength="40" value="<?php echo htmlspecialchars_uni($old['genero'] ?? ''); ?>"></div>
                <div class="field"><label class="flabel">Player By (Origen del físico)</label><input type="text" name="pb" maxlength="120" value="<?php echo htmlspecialchars_uni($old['pb'] ?? ''); ?>"></div>
              </div>
              
              <div class="field"><label class="flabel">Descripción Física</label><textarea name="desc_fisica" placeholder="Rasgos, altura, complexión, estilo de vestir..."><?php echo htmlspecialchars_uni($old['desc_fisica'] ?? ''); ?></textarea></div>
              <div class="field"><label class="flabel">Descripción Psicológica</label><textarea name="desc_psicologica" placeholder="Personalidad, miedos, virtudes..."><?php echo htmlspecialchars_uni($old['desc_psicologica'] ?? ''); ?></textarea></div>

              <div class="field"><label class="flabel">Historia *</label><textarea name="historia_pasado" required class="historia-textarea" placeholder="Tu origen, pasado y lo que te empuja a surcar los cielos..."><?php echo htmlspecialchars_uni($old['historia_pasado'] ?? ''); ?></textarea></div>
              <div class="field"><label class="flabel">Notas (opcional)</label><textarea name="notas" placeholder="Cualquier detalle extra que quieras apuntar (manías, estilo de combate interpretativo, secretos...)" ><?php echo htmlspecialchars_uni($old['notas'] ?? ''); ?></textarea></div>
              
              
            </div>
          </div>
        </div>

<!-- PASO 1: RAZA -->
        <div class="wiz-step" data-step="2">
          <div class="plate">
            <div class="plate-h"><span class="t">2. Linaje</span><span class="c">// tu Factor Linaje</span></div>
            <div class="plate-b">
              <p class="hint mb-12">Tu linaje da un <b class="c-paper">perfil de stats fijo</b> (interino) y <b class="c-paper">acceso</b> a Rasgos Raciales, un Rasgo Puro y una dote innata que <b class="c-paper">compras con Puntos de Linaje (PL)</b> en el paso 7. <button type="button" class="ope-help-btn" data-ope-help="raza" title="Ayuda">?</button></p>

              <div class="fl-pureza" id="flPureza">
                <label class="fl-pureza-opt">
                  <input type="radio" name="pureza" value="pura" checked>
                  <span class="fl-po-t">Sangre Pura</span>
                  <span class="fl-po-d">Un solo linaje. Acceso al <b>Rasgo Puro</b> de tu sangre.</span>
                </label>
                <label class="fl-pureza-opt">
                  <input type="radio" name="pureza" value="hibrida">
                  <span class="fl-po-t">Sangre Híbrida</span>
                  <span class="fl-po-d">Dos linajes. Accedes a los rasgos de ambos, pero cargas <b>≥ −2 en Defectos de Hibridación</b> y no puedes tomar Rasgo Puro.</span>
                </label>
              </div>

              <div class="race-grid" id="raceGrid">
<?php foreach ($RAZAS as $rid => $r): ?>
                <label class="race-card">
                  <input type="radio" name="raza" value="<?php echo $rid; ?>" data-mods='<?php echo htmlspecialchars_uni(json_encode($r['bonus']['mods'], JSON_UNESCAPED_UNICODE)); ?>' data-bonuslabel="<?php echo htmlspecialchars_uni($r['bonus']['label']); ?>" data-nombre="<?php echo htmlspecialchars_uni($r['nombre']); ?>" required>
                  <div class="rc-body">
                    <div class="rc-name"><?php echo htmlspecialchars_uni($r['nombre']); ?></div>
                    <div class="rc-resumen"><?php echo htmlspecialchars_uni($r['resumen']); ?></div>
                    <div class="rc-pas"><b>Perfil:</b> <?php echo htmlspecialchars_uni($r['bonus']['label']); ?></div>
                    <div class="rc-pas c-dim">Los Rasgos Raciales y dotes se compran con PL en el paso 7.</div>
                  </div>
                </label>
<?php endforeach; ?>
              </div>

              <div class="fl-linaje2" id="linaje2Wrap" hidden>
                <p class="hint mb-12"><b class="c-paper">Segundo linaje</b> (Hibridación). Tu <b>perfil de stats</b> lo marca el linaje primario de arriba.</p>
                <label class="fl-l2-lbl">Combinar con
                  <select name="linaje2" id="linaje2Sel">
                    <option value="">— elige tu segundo linaje —</option>
<?php foreach ($RAZAS as $rid => $r): ?>
                    <option value="<?php echo $rid; ?>"><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
<?php endforeach; ?>
                  </select>
                </label>
                <div class="fl-hib-aviso" id="hibAviso"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- PASO 3: IDENTIDAD ETERNAL -->
        <div class="wiz-step" data-step="3">
          <div class="plate">
            <div class="plate-h"><span class="t">3. Identidad</span><span class="c">// filosofía de combate</span></div>
            <div class="plate-b">
              <p class="hint mb-12">Tu Identidad es el <b class="c-paper">porqué</b> combates. Explora el árbol completo (solo lectura) antes de decidir; puedes volver atrás sin perder datos. <button type="button" class="ope-help-btn" data-ope-help="identidad" title="Ayuda">?</button></p>
              <div class="race-grid" id="idGrid">
<?php foreach ($IDENTIDADES as $iid => $idat): ?>
                <label class="race-card">
                  <input type="radio" name="identidad" value="<?php echo htmlspecialchars_uni($iid); ?>"
                    data-nombre="<?php echo htmlspecialchars_uni($idat['nombre']); ?>"
                    data-arbol="<?php echo htmlspecialchars_uni($idat['arbol']); ?>"
                    data-rol="<?php echo htmlspecialchars_uni($idat['rol']); ?>"
                    required>
                  <div class="rc-body">
                    <div class="rc-name"><?php echo htmlspecialchars_uni($idat['nombre']); ?></div>
                    <div class="rc-pas"><b>Rol:</b> <?php echo htmlspecialchars_uni($idat['rol']); ?> · <b>Recurso:</b> <?php echo htmlspecialchars_uni($idat['recurso']); ?></div>
                    <div class="rc-resumen"><?php echo htmlspecialchars_uni($idat['resumen']); ?></div>
                  </div>
                </label>
<?php endforeach; ?>
              </div>
              <div id="idTreePreview" class="eternal-preview-wrap" hidden>
<?php foreach ($IDENTIDADES as $iid => $idat):
    $tree = ope_eternal_load($idat['arbol']);
    if (!$tree) {
        continue;
    }
?>
                <div class="eternal-preview-pane" data-arbol="<?php echo htmlspecialchars_uni($idat['arbol']); ?>" hidden>
                  <?php echo ope_eternal_render_tree($tree, 'preview'); ?>
                </div>
<?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- PASO 4: FAMILIA DE ARMA + ARMA T1 -->
        <div class="wiz-step" data-step="4">
          <div class="plate">
            <div class="plate-h"><span class="t">4. Familia de Arma</span><span class="c">// árbol Eternal</span></div>
            <div class="plate-b">
              <p class="hint mb-12">La familia fija tu <b class="c-paper">árbol de Arma</b>. Después eliges el arma física Tier 1 de esa familia. <button type="button" class="ope-help-btn" data-ope-help="familia-arma" title="Ayuda">?</button></p>
              <div class="race-grid" id="famGrid">
<?php foreach ($FAMILIAS as $fid => $f): ?>
                <label class="race-card">
                  <input type="radio" name="familia_arma" value="<?php echo htmlspecialchars_uni($fid); ?>"
                    data-nombre="<?php echo htmlspecialchars_uni($f['nombre']); ?>"
                    data-arbol="<?php echo htmlspecialchars_uni($f['arbol']); ?>"
                    data-armas='<?php echo htmlspecialchars_uni(json_encode($f['armas'], JSON_UNESCAPED_UNICODE)); ?>'
                    required>
                  <div class="rc-body">
                    <div class="rc-name"><?php echo htmlspecialchars_uni($f['nombre']); ?></div>
                    <div class="rc-pas"><b>Efecto inherente:</b> <?php echo htmlspecialchars_uni($f['efecto']); ?></div>
                    <div class="rc-resumen"><?php echo htmlspecialchars_uni($f['resumen']); ?></div>
                  </div>
                </label>
<?php endforeach; ?>
              </div>
              <div id="famTreePreview" class="eternal-preview-wrap" hidden>
<?php foreach ($FAMILIAS as $fid => $f):
    $tree = ope_eternal_load($f['arbol']);
    if (!$tree) {
        continue;
    }
?>
                <div class="eternal-preview-pane" data-arbol="<?php echo htmlspecialchars_uni($f['arbol']); ?>" hidden>
                  <?php echo ope_eternal_render_tree($tree, 'preview'); ?>
                </div>
<?php endforeach; ?>
              </div>

              <div class="field mt-18" id="armaWrap" hidden>
                <label class="flabel">Arma física (Tier 1)</label>
                <div class="arm-grid" id="armGrid">
<?php foreach ($ARMAS as $aid => $a): ?>
                  <label class="race-card arma-opt" data-familia="<?php echo htmlspecialchars_uni($a['familia'] ?? ''); ?>" hidden>
                    <input type="radio" name="arma" value="<?php echo htmlspecialchars_uni($aid); ?>"
                      data-nombre="<?php echo htmlspecialchars_uni($a['nombre']); ?>"
                      data-familia="<?php echo htmlspecialchars_uni($a['familia'] ?? ''); ?>">
                    <div class="rc-body">
                      <div class="rc-name"><?php echo htmlspecialchars_uni($a['nombre']); ?></div>
                      <div class="rc-pas">
                        <b>Escala:</b> <?php echo htmlspecialchars_uni(implode(' / ', (array)$a['escala'])); ?><br>
                        <b>Técnica:</b> <?php echo htmlspecialchars_uni($a['tecnica']); ?><br>
                        <?php echo htmlspecialchars_uni($a['efecto']); ?>
                      </div>
                    </div>
                  </label>
<?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PASO 5: STATS -->
        <div class="wiz-step" data-step="5">
          <div class="plate">
            <div class="plate-h"><span class="t">5. Atributos</span><span class="c">// 1 a 6 + racial</span></div>
            <div class="plate-b">
              <p class="stats-hint">Todos empiezan en <b class="c-paper">1</b>. Reparte <b class="c-ember" id="psDisponiblesLabel">20 puntos</b>. Máximo <b class="c-paper">5</b> por stat antes del perfil de linaje (puede sumar o restar según tu linaje; suelo 1). <button type="button" class="ope-help-btn" data-ope-help="stats" title="Ayuda">?</button></p>
              <div id="statsContainer"></div>
              <div class="wiz-sum-bar"><span>Suma base: <b id="statSum">0</b></span><span>Puntos restantes: <b id="psRestantes">20</b></span></div>
            </div>
          </div>
        </div>




        <!-- PASO 6: FACCIÓN -->
        <div class="wiz-step" data-step="6">
          <div class="plate">
            <div class="plate-h"><span class="t">6. Facción</span><span class="c">// punto de partida</span></div>
            <div class="plate-b">
              <p class="hint mb-12">Tu facción no define tu moralidad. Puedes cambiarla con una trama significativa, pero perderás tu rango interno.</p>
              <div class="fac-grid">
<?php foreach ($FACCIONES as $fid => $f): ?>
                <label class="fac-card">
                  <input type="radio" name="faccion" value="<?php echo $fid; ?>" data-nombre="<?php echo htmlspecialchars_uni($f['nombre']); ?>" required>
                  <div class="fac-name"><?php echo htmlspecialchars_uni($f['nombre']); ?></div>
                  <div class="fac-desc"><?php echo htmlspecialchars_uni($f['desc']); ?></div>
                  <div class="fac-adv"><?php echo htmlspecialchars_uni($f['ventaja']); ?></div>
                </label>
<?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- PASO 7: VIRTUDES / DEFECTOS -->
        <div class="wiz-step" data-step="7">
          <div class="plate">
            <div class="plate-h"><span class="t">7. Factor Linaje</span><span class="c">// todo se compra con PL · suma 0</span></div>
            <div class="plate-b">
              <p class="hint mb-12"><b class="c-paper">Columna +</b>: Raciales (tu linaje / híbrido) y Generales. <b class="c-paper">Columna −</b>: Defectos que financian. Balance en <b class="c-paper">0</b>.</p>
              <div class="dotes-bar" id="dotesBar">Balance PL: <span class="dotes-num" id="dotesNum">0</span> <span class="dotes-hint">(debe cerrar en 0 · típico ±<?php echo (int)$PL_PRESUP['recomendado']; ?>)</span> <button type="button" class="ope-help-btn" data-ope-help="virtudes" title="Ayuda">?</button></div>
              <div id="cyborgBanner" class="cyborg-banner" hidden>
                <div class="cyborg-banner__h">Mecánica Cyborg activa</div>
                <p>Este personaje queda marcado como <b>Cyborg</b>: sub-sistema de modificaciones corporales (slots + PP). No sustituye Haki, Fruta ni Eternal.</p>
                <label class="cyborg-slot-lbl">Slot Tier I inicial
                  <select name="cyborg_slot" id="cyborgSlot">
                    <option value="">— elige —</option>
                    <option value="brazo">Brazo</option>
                    <option value="pierna">Pierna</option>
                    <option value="ojo">Ojo</option>
                    <option value="torso">Torso</option>
                  </select>
                </label>
                <button type="button" class="btn btn-ghost btn-sm" data-ope-help="cyborg">Leer reglas Cyborg</button>
              </div>

              <div class="fl-cols">
                <!-- COLUMNA +PL -->
                <div class="fl-col fl-col--pos">
                  <div class="fl-col-h">Rasgos <span class="badge cost">+PL</span></div>

                  <div class="fl-sec" id="flRacialesSec">
                    <div class="fl-sec-h">Raciales <span class="c-dim">// linaje · puro · dotes</span></div>
                    <p class="fl-empty" id="flRacialesEmpty">Elige tu linaje en el paso 2.</p>
<?php foreach ($RASGOS_RACIALES as $lin => $items): foreach ($items as $rid => $r): ?>
                    <?php echo ope_wiz_fl_row('rasgos_raciales[]', $rid, $r['pl'], $r['nombre'], $r['efecto'], array('linaje' => $lin, 'req' => $r['req'] ?? '')); ?>
<?php endforeach; endforeach; ?>
                    <div id="flPuroWrap">
                      <p class="fl-empty" id="flPuroEmpty" hidden>Rasgo Puro: solo Sangre Pura.</p>
<?php foreach ($RASGO_PURO as $lin => $p): ?>
                      <?php echo ope_wiz_fl_row('rasgo_puro', $p['id'], $p['pl'], 'Puro · ' . $p['nombre'], $p['efecto'], array('linaje' => $lin)); ?>
<?php endforeach; ?>
                    </div>
                    <div id="flDotesWrap">
                      <p class="fl-empty" id="flDotesEmpty" hidden>Sin dote innata en tu linaje.</p>
<?php foreach ($DOTES_INNATAS as $lin => $d): ?>
                      <?php echo ope_wiz_fl_row('dotes_innatas[]', $d['id'], $d['pl'], 'Dote · ' . $d['nombre'], 'Innata de linaje: Nv.1 sin PD; ocupa 1 de 4 slots de dote.', array('linaje' => $lin)); ?>
<?php endforeach; ?>
                    </div>
                  </div>

                  <div class="fl-sec">
                    <div class="fl-sec-h">Generales <span class="c-dim">// cualquiera</span></div>
<?php foreach ($RASGOS_GENERALES as $cat => $items): foreach ($items as $gid => $g):
    $opts = array('spec' => !empty($g['spec']));
    if (($g['sub'] ?? '') === 'cyborg') { $opts['cyborg'] = true; }
?>
                    <?php echo ope_wiz_fl_row('rasgos_generales[]', $gid, $g['pl'], $g['nombre'], $g['efecto'], $opts); ?>
<?php endforeach; endforeach; ?>
                  </div>
                </div>

                <!-- COLUMNA −PL -->
                <div class="fl-col fl-col--neg">
                  <div class="fl-col-h">Defectos <span class="badge back">−PL</span></div>

                  <div class="fl-sec" id="flHibSec" hidden>
                    <div class="fl-sec-h">Hibridación <span class="c-dim">// obligatorio ≥ −2</span></div>
<?php foreach ($DEF_HIBRIDACION as $hid => $h): ?>
                    <?php echo ope_wiz_fl_row('defectos_hibridacion[]', $hid, $h['pl'], $h['nombre'], $h['efecto'], array('spec' => !empty($h['spec']))); ?>
<?php endforeach; ?>
                  </div>

                  <div class="fl-sec">
                    <div class="fl-sec-h">Generales <span class="c-dim">// financian</span></div>
<?php foreach ($FL_DEFECTOS as $did => $d): ?>
                    <?php echo ope_wiz_fl_row('defectos[]', $did, $d['pl'], $d['nombre'], $d['efecto'], array('spec' => !empty($d['spec']))); ?>
<?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PASO 8: AKUMA NO MI (opcional) -->
        <div class="wiz-step" data-step="8">
          <div class="plate">
            <div class="plate-h"><span class="t">8. Akuma no Mi</span><span class="c">// opcional · riesgo</span></div>
            <div class="plate-b">
              <p class="hint mb-12">Puedes <b>tirar</b> una fruta aleatoria entre las libres del catálogo. Si tiras, <b>debes comerla</b> (Nv.0). Comprar con PD se hace después en Trámites.</p>
              <div class="pack-grid" id="frutaGrid">
                <label class="pack-card">
                  <input type="radio" name="fruta_opcion" value="ninguna" checked>
                  <div class="rc-body">
                    <div class="rc-name">Sin fruta</div>
                    <div class="rc-resumen">Podrás obtenerla más adelante (PD, trama o botín).</div>
                  </div>
                </label>
                <label class="pack-card">
                  <input type="radio" name="fruta_opcion" value="tirar">
                  <div class="rc-body">
                    <div class="rc-name">Tirar (aleatoria)</div>
                    <div class="rc-resumen">El sistema elige entre todas las frutas libres. No puedes rechazar el resultado.</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- PASO 9: EQUIPO -->
        <div class="wiz-step" data-step="9">
          <div class="plate">
            <div class="plate-h"><span class="t">9. Equipo Inicial</span><span class="c">// prepárate</span></div>
            <div class="plate-b">
              <p class="hint mb-12">Elige tu <b class="c-paper">Pack de Equipo Inicial</b>. Empiezas con <b class="c-ember"><?php echo number_format((int)$BERRIES_BASE, 0, ',', '.'); ?> Berries</b>.</p>
              <div class="pack-grid" id="packGrid">
<?php foreach ($PACKS as $pack_id => $p): ?>
                <label class="pack-card">
                  <input type="radio" name="pack_equipo" value="<?php echo htmlspecialchars_uni($pack_id); ?>" data-nombre="<?php echo htmlspecialchars_uni($p['nombre']); ?>" required<?php echo (($old['pack_equipo'] ?? '') === $pack_id) ? ' checked' : ''; ?>>
                  <div class="rc-body">
                    <div class="rc-name"><?php echo htmlspecialchars_uni($p['nombre']); ?></div>
                    <div class="rc-resumen"><?php echo htmlspecialchars_uni($p['resumen']); ?></div>
                    <div class="rc-pas"><?php echo implode('<br>', array_map('htmlspecialchars_uni', $p['contenido'])); ?></div>
                  </div>
                </label>
<?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

<!-- PASO 10: RESUMEN -->
        <div class="wiz-step" data-step="10">
          <div class="plate">
            <div class="plate-h"><span class="t">10. Revisión final</span><span class="c">// antes de enviar</span></div>
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
    </div>
  </div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<?php if ($loggedin && $hay_hueco): ?>
<script>
(function(){
  var STAT_LABELS = <?php echo json_encode($STATS, JSON_UNESCAPED_UNICODE); ?>;
  var PS_TOTAL = <?php echo (int)$PS_TOTAL; ?>;
  var STAT_BASE = <?php echo (int)$STAT_BASE; ?>;
  var STAT_CAP = <?php echo (int)$STAT_CAP; ?>;
  var BERRIES = <?php echo (int)$BERRIES_BASE; ?>;
  var STEP_NAMES = ['Qui\u00e9n eres','Linaje','Identidad','Arma','Atributos','Facci\u00f3n','Factor Linaje','Fruta','Equipo','Resumen'];
  var steps = Array.prototype.slice.call(document.querySelectorAll('.wiz-step'));
  var cur = 1;
  var form = document.getElementById('wizForm');
  var dash = '\u2014';

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

  function showTreePreview(wrapId, arbol){
    var wrap = document.getElementById(wrapId);
    if (!wrap) return;
    wrap.hidden = !arbol;
    wrap.querySelectorAll('.eternal-preview-pane').forEach(function(p){
      p.hidden = p.getAttribute('data-arbol') !== arbol;
    });
  }

  function showStep(n){
    steps.forEach(function(s){ s.classList.toggle('on', parseInt(s.dataset.step,10) === n); });
    document.getElementById('wizPrev').style.visibility = (n === 1) ? 'hidden' : 'visible';
    document.getElementById('wizNext').classList.toggle('dn', n === steps.length);
    document.getElementById('wizSubmit').classList.toggle('dn', n !== steps.length);
    document.getElementById('wizErr').textContent = '';
    renderProgress();
    if (n === 5) renderStats();
    if (n === steps.length) renderSummary();
    updatePreview();
    window.scrollTo({top: form.offsetTop - 70, behavior:'smooth'});
  }

  function validateStep(n){
    if (n === 1){
      if (form.querySelector('[name=nombre]').value.trim().length < 3) return 'Escribe un nombre de al menos 3 caracteres.';
      if (form.querySelector('[name=historia_pasado]').value.trim().length < 80) return 'Cuenta la historia de tu personaje con más detalle (mínimo ~80 caracteres).';
    }
    if (n === 2){
      if (!document.querySelector('input[name=raza]:checked')) return 'Elige un linaje.';
      if (flPureza() === 'hibrida'){
        var b = flLinaje2();
        if (!b) return 'Elige tu segundo linaje (Hibridación).';
        var h = flHib(flLinaje1(), b);
        if (!h.ok) return h.motivo;
      }
    }
    if (n === 3){
      if (!document.querySelector('input[name=identidad]:checked')) return 'Elige una Identidad Eternal.';
    }
    if (n === 4){
      if (!document.querySelector('input[name=familia_arma]:checked')) return 'Elige una Familia de Arma.';
      if (!document.querySelector('input[name=arma]:checked')) return 'Elige un arma física de esa familia.';
    }
    if (n === 5){
      var totalPs = 0;
      form.querySelectorAll('.ps-input').forEach(function(inp){ totalPs += parseInt(inp.value, 10) || 0; });
      if (totalPs <= 0) return 'Reparte al menos 1 punto.';
      if (totalPs > PS_TOTAL) return 'Te has pasado del límite de puntos.';
    }
    if (n === 6){
      if (!document.querySelector('input[name=faccion]:checked')) return 'Elige una facción.';
    }
    if (n === 7){
      var bar = document.getElementById('dotesBar');
      if (bar.classList.contains('bad')) return 'El balance de Puntos de Linaje debe cerrar en 0.';
      var missingSpec = false;
      form.querySelectorAll('input[data-spec="1"]:checked').forEach(function(chk){
        if (chk.offsetParent === null && chk.closest('.fl-linaje-row')) return; // oculto por linaje
        var wrap = chk.closest('.item-row').querySelector('.item-spec input');
        if (wrap && !wrap.value.trim()) missingSpec = true;
      });
      if (missingSpec) return 'Rellena el campo de detalle de los rasgos/defectos marcados.';
      var cy = document.querySelector('input[data-cyborg="1"]');
      if (cy && cy.checked) {
        var slot = document.getElementById('cyborgSlot');
        if (!slot || !slot.value) return 'Cyborg: elige el slot del miembro mecánico (Brazo / Pierna / Ojo / Torso).';
      }
      if (flPureza() === 'hibrida'){
        var sumHib = 0;
        document.querySelectorAll('input[name="defectos_hibridacion[]"]:checked').forEach(function(c){
          sumHib += parseInt(c.dataset.pl, 10) || 0;
        });
        if (sumHib > -2) return 'Un híbrido debe tomar al menos −2 en Defectos de Hibridación.';
        var h = flHib(flLinaje1(), flLinaje2());
        if (h.ok && h.exp && !document.querySelector('input[value="experimento"]:checked')) {
          return 'Esta mezcla requiere el rasgo "Experimento / Anomalía".';
        }
      }
    }
    if (n === 8){
      // Fruta opcional: ninguna o tirar (radio siempre checked por default).
    }
    if (n === 9){
      if (!document.querySelector('input[name=pack_equipo]:checked')) return 'Elige un Pack de Equipo Inicial.';
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

  // ---- Identidad Eternal (preview árbol) ----
  document.querySelectorAll('input[name=identidad]').forEach(function(r){
    r.addEventListener('change', function(){
      showTreePreview('idTreePreview', r.dataset.arbol || '');
      updatePreview();
    });
  });

  // ---- Familia de arma + filtrar armas T1 ----
  document.querySelectorAll('input[name=familia_arma]').forEach(function(r){
    r.addEventListener('change', function(){
      var fam = r.value;
      showTreePreview('famTreePreview', r.dataset.arbol || '');
      var wrap = document.getElementById('armaWrap');
      wrap.hidden = false;
      document.querySelectorAll('.arma-opt').forEach(function(lab){
        var match = lab.getAttribute('data-familia') === fam;
        lab.hidden = !match;
        var inp = lab.querySelector('input[name=arma]');
        if (inp && !match) inp.checked = false;
        if (inp) inp.required = match;
      });
      updatePreview();
    });
  });
  document.querySelectorAll('input[name=arma]').forEach(function(r){
    r.addEventListener('change', updatePreview);
  });

  // ---- Stats en vivo con perfil racial fijo (sin elección) ----
  function getRazaMods(){
    var r = document.querySelector('input[name=raza]:checked');
    if (!r) return {};
    try { return JSON.parse(r.dataset.mods || '{}'); } catch (e) { return {}; }
  }

  function getStatEff(ps, sig, mods){
    mods = mods || {};
    return Math.max(1, STAT_BASE + ps + (mods[sig] || 0));
  }

  function buildStatRow(sig, label, prev, mods){
    var delta = (mods && mods[sig]) || 0;
    var row = document.createElement('div');
    row.className = 'stat-row';
    row.innerHTML =
      '<span class="stat-name">' + label + ' <span class="sig">' + sig + '</span></span>' +
      '<button type="button" class="stat-btn stat-minus" data-stat="' + sig + '" disabled>&minus;</button>' +
      '<span class="stat-val" id="statVal_' + sig + '">' + prev + '</span>' +
      '<button type="button" class="stat-btn stat-plus" data-stat="' + sig + '">+</button>' +
      '<span class="stat-eff" id="statEff_' + sig + '">' + getStatEff(prev, sig, mods) + '</span>' +
      '<input type="hidden" name="ps_stats[' + sig + ']" class="ps-input" data-stat="' + sig + '" id="ps_' + sig + '" value="' + prev + '">' +
      (delta ? ('<span class="stat-bump' + (delta < 0 ? ' stat-bump--neg' : '') + '">' + (delta > 0 ? '&check; +' : '&#9660; ') + delta + ' racial</span>') : '');
    return row;
  }

  function renderStats(){
    var container = document.getElementById('statsContainer');
    var mods = getRazaMods();

    var prevPs = {};
    container.querySelectorAll('.ps-input').forEach(function(inp){
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
        pdiv.appendChild(buildStatRow(sig, pillar.stats[sig], prev, mods));
      });
      container.appendChild(pdiv);
    });

    function recompute(){
      var totalUsado = 0;
      container.querySelectorAll('.ps-input').forEach(function(inp){
        totalUsado += parseInt(inp.value, 10) || 0;
      });
      var restante = PS_TOTAL - totalUsado;

      container.querySelectorAll('.ps-input').forEach(function(inp){
        var sig = inp.dataset.stat;
        var ps = parseInt(inp.value, 10) || 0;
        var capped = STAT_CAP - STAT_BASE;
        var plusBtn = inp.closest('.stat-row').querySelector('.stat-plus');
        var minusBtn = inp.closest('.stat-row').querySelector('.stat-minus');
        plusBtn.disabled = (ps >= capped) || (restante <= 0);
        minusBtn.disabled = (ps <= 0);
        document.getElementById('statEff_' + sig).textContent = getStatEff(ps, sig, mods);
        document.getElementById('statVal_' + sig).textContent = ps;
      });
      var modsSum = 0;
      Object.keys(mods).forEach(function(k){ modsSum += mods[k]; });
      document.getElementById('statSum').textContent = (8 * STAT_BASE) + totalUsado + modsSum;
      document.getElementById('psRestantes').textContent = Math.max(0, PS_TOTAL - totalUsado);
      updatePreview();
    }

    container.querySelectorAll('.stat-plus').forEach(function(btn){
      btn.addEventListener('click', function(){
        var sig = btn.dataset.stat;
        var inp = document.getElementById('ps_' + sig);
        var ps = parseInt(inp.value, 10) || 0;
        var capped = STAT_CAP - STAT_BASE;
        var totalUsado = 0;
        container.querySelectorAll('.ps-input').forEach(function(i){ totalUsado += parseInt(i.value, 10) || 0; });
        if (ps < capped && totalUsado < PS_TOTAL){
          inp.value = ps + 1;
          recompute();
        }
      });
    });
    container.querySelectorAll('.stat-minus').forEach(function(btn){
      btn.addEventListener('click', function(){
        var sig = btn.dataset.stat;
        var inp = document.getElementById('ps_' + sig);
        var ps = parseInt(inp.value, 10) || 0;
        if (ps > 0){
          inp.value = ps - 1;
          recompute();
        }
      });
    });
    recompute();
  }

  document.querySelectorAll('input[name=raza]').forEach(function(r){
    r.addEventListener('change', function(){ renderStats(); updatePreview(); refreshFactorLinaje(); updateHibAviso(); });
  });

  // ---- Live preview card ----
  function selectedNombre(name){
    var el = document.querySelector('input[name="' + name + '"]:checked');
    if (!el) return '';
    return el.dataset.nombre || '';
  }

  function effOrDash(sig){
    var inp = document.getElementById('ps_' + sig);
    if (!inp) return dash;
    return String(getStatEff(parseInt(inp.value, 10) || 0, sig, getRazaMods()));
  }

  function updatePreview(){
    var card = document.getElementById('wizCard');
    var nombre = (form.querySelector('[name=nombre]') || {}).value || '';
    nombre = nombre.trim();
    document.getElementById('wizNamePrev').textContent = nombre || 'Sin nombre';
    document.getElementById('wizInitial').textContent = nombre ? nombre.charAt(0).toUpperCase() : '?';

    var idSel = document.querySelector('input[name=identidad]:checked');
    card.setAttribute('data-element', idSel ? idSel.value : '');

    var raza = selectedNombre('raza');
    var idN = selectedNombre('identidad');
    var fam = selectedNombre('familia_arma');
    var arma = selectedNombre('arma');
    var fac = selectedNombre('faccion');

    document.getElementById('wizLineRaza').textContent = raza || dash;
    document.getElementById('wizLineId').textContent = idN || dash;
    document.getElementById('wizLineArma').textContent = arma || dash;
    document.getElementById('wizLineFam').textContent = fam || dash;
    document.getElementById('wizLineFac').textContent = fac || dash;

    var chips = document.getElementById('wizChips');
    var parts = [];
    if (idN) parts.push(idN);
    if (raza) parts.push(raza);
    if (fac) parts.push(fac);
    if (!parts.length) parts.push('Borrador');
    chips.innerHTML = parts.map(function(t){ return '<span class="wiz-chip">' + t + '</span>'; }).join('');

    document.getElementById('wizStatFue').textContent = effOrDash('FUE');
    document.getElementById('wizStatRes').textContent = effOrDash('RES');
    document.getElementById('wizStatAgi').textContent = effOrDash('AGI');
    document.getElementById('wizStatVol').textContent = effOrDash('VOL');
  }

  form.addEventListener('change', updatePreview);
  form.addEventListener('input', function(e){
    if (e.target && (e.target.name === 'nombre' || e.target.name === 'apodo')) updatePreview();
  });

  // ---- Factor Linaje: config de reglas (espejo de inc/ope_rol/dominio/creacion.php) ----
  var FL = {
    tam: <?php echo json_encode(ope_rol_linaje_tamano_idx(), JSON_UNESCAPED_UNICODE); ?>,
    lab: ['lunarians','minks']
  };

  function flPureza(){
    var r = document.querySelector('input[name=pureza]:checked');
    return r ? r.value : 'pura';
  }
  function flLinaje1(){
    var r = document.querySelector('input[name=raza]:checked');
    return r ? r.value : '';
  }
  function flLinaje2(){
    var s = document.getElementById('linaje2Sel');
    return (flPureza() === 'hibrida' && s) ? s.value : '';
  }
  function flLinajes(){
    var l = [flLinaje1()];
    var l2 = flLinaje2();
    if (l2 && l2 !== l[0]) l.push(l2);
    return l.filter(Boolean);
  }
  // Compatibilidad de híbrido (espejo de ope_pj_hibridacion).
  function flHib(a, b){
    if (!a || !b || a === b) return { ok:false, motivo:'Un híbrido combina dos linajes distintos.', exp:false };
    var da = (a in FL.tam) ? FL.tam[a] : 2, db = (b in FL.tam) ? FL.tam[b] : 2;
    if (Math.abs(da - db) >= 3) return { ok:false, motivo:'Incompatibilidad física: la diferencia de tamaño es demasiado grande.', exp:false };
    var exp = FL.lab.indexOf(a) !== -1 || FL.lab.indexOf(b) !== -1;
    return { ok:true, motivo: exp ? 'Mezcla de Laboratorio/Anomalía: requiere el rasgo "Experimento / Anomalía".' : '', exp:exp };
  }

  // Muestra/oculta secciones dependientes del linaje y la pureza.
  function refreshFactorLinaje(){
    var pureza = flPureza();
    var linajes = flLinajes();
    var wrap2 = document.getElementById('linaje2Wrap');
    if (wrap2) wrap2.hidden = (pureza !== 'hibrida');

    // Rasgos Raciales (solo filas directas de la sección, no puro/dotes).
    var anyRac = false;
    document.querySelectorAll('#flRacialesSec > .fl-linaje-row').forEach(function(row){
      var show = linajes.indexOf(row.getAttribute('data-linaje-row')) !== -1;
      row.hidden = !show;
      var chk = row.querySelector('input[type=checkbox]');
      if (!show && chk) chk.checked = false;
      if (show) anyRac = true;
    });
    var racEmpty = document.getElementById('flRacialesEmpty');
    if (racEmpty) racEmpty.hidden = anyRac;

    // Rasgo Puro: solo Sangre Pura y del linaje primario.
    var anyPuro = false;
    document.querySelectorAll('#flPuroWrap .fl-linaje-row').forEach(function(row){
      var show = (pureza === 'pura') && row.getAttribute('data-linaje-row') === flLinaje1();
      row.hidden = !show;
      var chk = row.querySelector('input[type=checkbox]');
      if (!show && chk) chk.checked = false;
      if (show) anyPuro = true;
    });
    var puroEmpty = document.getElementById('flPuroEmpty');
    if (puroEmpty) puroEmpty.hidden = anyPuro || pureza !== 'pura';

    // Dotes Innatas: solo las de tus linajes.
    var anyDote = false;
    document.querySelectorAll('#flDotesWrap .fl-linaje-row').forEach(function(row){
      var show = linajes.indexOf(row.getAttribute('data-linaje-row')) !== -1;
      row.hidden = !show;
      var chk = row.querySelector('input[type=checkbox]');
      if (!show && chk) chk.checked = false;
      if (show) anyDote = true;
    });
    var doteEmpty = document.getElementById('flDotesEmpty');
    if (doteEmpty) doteEmpty.hidden = anyDote || !linajes.length;

    // Defectos de Hibridación: solo híbridos.
    var hibSec = document.getElementById('flHibSec');
    if (hibSec){
      hibSec.hidden = (pureza !== 'hibrida');
      if (pureza !== 'hibrida'){
        hibSec.querySelectorAll('input[type=checkbox]:checked').forEach(function(c){ c.checked = false; });
      }
    }
    recomputePL();
  }

  // ---- Balance de PL (suma 0) ----
  function recomputePL(){
    var balance = 0;
    document.querySelectorAll('input[data-pl]:checked').forEach(function(c){
      if (c.offsetParent === null && c.closest('.fl-linaje-row')) return; // fila oculta por linaje
      balance += parseInt(c.dataset.pl, 10) || 0;
    });
    document.getElementById('dotesNum').textContent = (balance > 0 ? '+' : '') + balance;
    document.getElementById('dotesBar').classList.toggle('bad', balance !== 0);
  }

  document.querySelectorAll('input[data-pl]').forEach(function(c){
    c.addEventListener('change', function(){
      if (c.dataset.spec === '1'){
        var specBox = c.closest('.item-row').querySelector('.item-spec');
        if (specBox) specBox.classList.toggle('show', c.checked);
      }
      recomputePL();
    });
  });

  // Categorías colapsables (Rasgos Generales).
  document.querySelectorAll('.fl-sec .cat-h').forEach(function(btn){
    btn.addEventListener('click', function(){
      var body = btn.parentNode.querySelector('.cat-body');
      if (!body) return;
      body.hidden = !body.hidden;
      var tog = btn.querySelector('.cat-toggle');
      if (tog) tog.textContent = body.hidden ? '+' : '\u2212';
    });
  });

  document.querySelectorAll('input[name=pureza]').forEach(function(r){
    r.addEventListener('change', refreshFactorLinaje);
  });
  var l2sel = document.getElementById('linaje2Sel');
  if (l2sel) l2sel.addEventListener('change', function(){ refreshFactorLinaje(); updateHibAviso(); });

  function updateHibAviso(){
    var box = document.getElementById('hibAviso');
    if (!box) return;
    if (flPureza() !== 'hibrida'){ box.textContent = ''; return; }
    var a = flLinaje1(), b = flLinaje2();
    if (!a || !b){ box.textContent = 'Elige tu linaje primario y el secundario.'; box.className = 'fl-hib-aviso'; return; }
    var h = flHib(a, b);
    box.textContent = h.ok ? (h.motivo || 'Combinación válida.') : h.motivo;
    box.className = 'fl-hib-aviso ' + (h.ok ? (h.exp ? 'warn' : 'ok') : 'bad');
  }

  recomputePL();

  // ---- Resumen final ----
  function renderSummary(){
    var out = document.getElementById('wizSummary');
    var r1 = document.querySelector('input[name=raza]:checked');
    var razaTxt = r1 ? r1.dataset.nombre : dash;
    var bonusTxt = r1 ? (r1.dataset.bonuslabel || dash) : dash;

    var idTxt = selectedNombre('identidad') || dash;
    var famTxt = selectedNombre('familia_arma') || dash;
    var armaTxt = selectedNombre('arma') || dash;
    var faccionTxt = selectedNombre('faccion') || dash;
    var packTxt = selectedNombre('pack_equipo') || dash;
    var nombre = form.querySelector('[name=nombre]').value || dash;

    function flNames(sel){
      var out = [];
      document.querySelectorAll(sel).forEach(function(c){
        if (c.offsetParent === null && c.closest('.fl-linaje-row')) return;
        out.push(c.closest('.item-row').querySelector('.item-name').firstChild.textContent.trim());
      });
      return out;
    }
    var rasgosNames = flNames('input[name="rasgos_raciales[]"]:checked')
      .concat(flNames('input[name="rasgo_puro"]:checked'))
      .concat(flNames('input[name="dotes_innatas[]"]:checked'))
      .concat(flNames('input[name="rasgos_generales[]"]:checked'));
    var defectosNames = flNames('input[name="defectos[]"]:checked')
      .concat(flNames('input[name="defectos_hibridacion[]"]:checked'));
    var plBalance = document.getElementById('dotesNum').textContent;
    var l2sel2 = document.getElementById('linaje2Sel');
    var l2name = (l2sel2 && l2sel2.value) ? l2sel2.options[l2sel2.selectedIndex].text : '';
    var purezaTxt = flPureza() === 'hibrida' ? ('Híbrido' + (l2name ? ' · ' + l2name : '')) : 'Puro';

    var statsHtml = '';
    var mods = getRazaMods();
    Object.keys(STAT_LABELS).forEach(function(pk){
      var pillar = STAT_LABELS[pk];
      Object.keys(pillar.stats).forEach(function(sig){
        var inp = document.getElementById('ps_' + sig);
        var ps = inp ? parseInt(inp.value, 10) || 0 : 0;
        var eff = getStatEff(ps, sig, mods);
        var delta = mods[sig] || 0;
        var bonusTag = delta ? (' <span class="stat-bonus-badge' + (delta < 0 ? ' stat-bonus-badge--neg' : '') + '">' + (delta > 0 ? '+' : '') + delta + ' racial</span>') : '';
        statsHtml += '<span class="sum-stat"><b>' + sig + '</b> ' + eff + bonusTag + '</span>';
      });
    });

    var berriesTxt = BERRIES.toLocaleString('es-ES');
    var sumTotal = document.getElementById('statSum') ? document.getElementById('statSum').textContent : '0';

    out.innerHTML =
      '<div class="sum-block"><h4>Personaje</h4>' +
      '<div class="line"><b>Nombre</b>' + nombre + '</div>' +
      '<div class="line"><b>Linaje</b>' + razaTxt + ' <span class="c-dim">(' + bonusTxt + ')</span></div>' +
      '<div class="line"><b>Sangre</b>' + purezaTxt + '</div>' +
      '<div class="line"><b>Identidad</b>' + idTxt + '</div>' +
      '<div class="line"><b>Familia</b>' + famTxt + '</div>' +
      '<div class="line"><b>Arma</b>' + armaTxt + '</div>' +
      '<div class="line"><b>Facción</b>' + faccionTxt + '</div></div>' +
      '<div class="sum-block"><h4>Atributos</h4>' +
      '<div class="sum-stats">' + statsHtml + '</div>' +
      '<div class="line sum-total"><b>Total</b> ' + sumTotal + '</div></div>' +
      '<div class="sum-block"><h4>Factor Linaje <span class="c-dim">// balance PL ' + plBalance + '</span></h4><div class="line"><b>Rasgos</b>' + (rasgosNames.join(', ') || 'Ninguno') + '</div>' +
      '<div class="line"><b>Defectos</b>' + (defectosNames.join(', ') || 'Ninguno') + '</div>' +
      (document.querySelector('input[data-cyborg="1"]:checked')
        ? ('<div class="line"><b>Cyborg</b>sí · slot ' + ((document.getElementById('cyborgSlot') && document.getElementById('cyborgSlot').value) || '—') + '</div>')
        : '') +
      '</div>' +
      '<div class="sum-block"><h4>Akuma no Mi</h4><div class="line"><b>Opción</b>' + ((document.querySelector('input[name=fruta_opcion]:checked') || {}).value === 'tirar' ? 'Tirar (aleatoria)' : 'Sin fruta') + '</div></div>' +
      '<div class="sum-block"><h4>Equipo</h4><div class="line"><b>Pack</b>' + packTxt + '</div>' +
      '<div class="line"><b>Berries iniciales</b>' + berriesTxt + '</div></div>' +
      '<p class="hint">Al enviar, el personaje queda en <b>revisión</b>. Cuando el staff lo apruebe recibirás 1 PT para empezar a comprar nodos Eternal.</p>';
  }

  showStep(1);
  updatePreview();
  refreshFactorLinaje();
  updateHibAviso();

  // ---- Modales de mecánicas + nodos Eternal ----
  var MECH_HELP = <?php echo json_encode($MECH_HELP, JSON_UNESCAPED_UNICODE); ?>;
  var modal = document.getElementById('opeMechModal');
  var modalTitle = document.getElementById('opeMechTitle');
  var modalBody = document.getElementById('opeMechBody');
  var cyborgSeen = false;

  function openMechModal(title, bodyHtml){
    if (!modal) return;
    modalTitle.textContent = title || 'Ayuda';
    modalBody.innerHTML = bodyHtml || '';
    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.setAttribute('open', 'open');
  }

  function openHelpKey(key){
    var pack = MECH_HELP[key];
    if (!pack) return;
    openMechModal(pack.title, pack.body);
  }

  function escHtml(s){
    return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
  }

  function buildEternalNodeModalHtml(data){
    var tipoLabel = data.tipo_label || data.tipo || '';
    if (data.pinaculo) tipoLabel = 'Pináculo';
    var nivel = data.nivel ? (' · Nv ' + escHtml(data.nivel)) : '';
    var codigo = data.codigo ? ('<span class="ope-mech-codigo">' + escHtml(data.codigo) + '</span> ') : '';
    var html = '<p class="ope-mech-meta">' + codigo + '<b>' + escHtml(tipoLabel) + '</b> · Corriente ' + escHtml(data.foco || '') +
      ' · Tier ' + escHtml(data.tier || '') + nivel + ' · Coste <b>' + (data.coste_pt || 1) + ' PT</b></p>';

    html += '<p class="ope-mech-efecto">' + escHtml(data.efecto || 'Sin descripción.') + '</p>';

    if (data.tipo === 'habilitador') {
      html += '<p class="hint">Este nodo es un <b>habilitador</b>: no te da una técnica hecha, sino el <b>derecho a crear</b> técnicas de este tipo y pagarlas con PP.</p>';
    }
    if (data.profundidad) {
      html += '<p class="ope-mech-profundidad"><b>Profundidad:</b> ' + escHtml(data.profundidad) + '</p>';
    }
    if (data.afinidad_bonus) {
      html += '<p class="ope-mech-afinidad"><b>Afinidad</b>' + (data.afinidad ? (' (' + escHtml(data.afinidad) + ')') : '') + ': ' + escHtml(data.afinidad_bonus) + '</p>';
    }

    var excluyeNombres = (data.excluye_nombres && data.excluye_nombres.length) ? data.excluye_nombres : (data.excluye || []);
    if (excluyeNombres.length) {
      html += '<p class="ope-mech-excluye"><b>Excluye:</b> ' + excluyeNombres.map(escHtml).join(', ') + ' (eliges 1 de 3 pináculos)</p>';
    }
    if (data.blocked) {
      html += '<p class="ope-mech-bloqueo"><b>Bloqueado:</b> ya elegiste ' + escHtml(data.blocked_by_nombre || 'otro pináculo') + '. No puedes elegir este.</p>';
    }
    if (data.pinaculo) html += '<p class="ope-mech-tag">Pináculo de la corriente' + (data.afinidad ? (' ' + escHtml(data.afinidad)) : '') + '</p>';
    html += '<p class="hint">En creación solo exploras. Tras la aprobación del staff elegirás nodos con PT (1 PT por tier: Nv 1/10/20/30/45).</p>';
    return html;
  }

  // ---- Modal "Ver árbol completo" (SVG entero con zoom, sin scroll a ciegas) ----
  function eternalScaleEl(dlg){ return dlg.querySelector('[data-eternal-tree-scale]'); }
  function eternalBoardEl(dlg){ return dlg.querySelector('[data-eternal-tree-board]'); }
  function applyEternalZoom(dlg, zoom){
    var scaleEl = eternalScaleEl(dlg), board = eternalBoardEl(dlg);
    if (!scaleEl || !board) return;
    zoom = Math.max(0.25, Math.min(2, zoom));
    dlg._etZoom = zoom;
    var w = parseFloat(board.getAttribute('data-board-w')) || board.offsetWidth;
    var h = parseFloat(board.getAttribute('data-board-h')) || board.offsetHeight;
    scaleEl.style.transform = 'scale(' + zoom + ')';
    scaleEl.style.width = (w * zoom) + 'px';
    scaleEl.style.height = (h * zoom) + 'px';
    var lvl = dlg.querySelector('[data-eternal-zoom-level]');
    if (lvl) lvl.textContent = Math.round(zoom * 100) + '%';
  }
  function fitEternalTree(dlg){
    var viewport = dlg.querySelector('[data-eternal-tree-viewport]');
    var board = eternalBoardEl(dlg);
    if (!viewport || !board) return;
    var w = parseFloat(board.getAttribute('data-board-w')) || 1;
    var h = parseFloat(board.getAttribute('data-board-h')) || 1;
    var scale = Math.min(1, (viewport.clientWidth - 24) / w, (viewport.clientHeight - 24) / h);
    applyEternalZoom(dlg, isFinite(scale) && scale > 0 ? scale : 1);
  }

  document.addEventListener('click', function(ev){
    var helpBtn = ev.target.closest('[data-ope-help]');
    if (helpBtn) {
      ev.preventDefault();
      openHelpKey(helpBtn.getAttribute('data-ope-help'));
      return;
    }
    var openBtn = ev.target.closest('[data-eternal-tree-open]');
    if (openBtn) {
      ev.preventDefault();
      var dlg = document.getElementById('eternal-tree-modal-' + openBtn.getAttribute('data-eternal-tree-open'));
      if (dlg) {
        if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', 'open');
        requestAnimationFrame(function(){ fitEternalTree(dlg); });
      }
      return;
    }
    var closeBtn = ev.target.closest('[data-eternal-tree-close]');
    if (closeBtn) {
      ev.preventDefault();
      var dlgc = closeBtn.closest('dialog.eternal-tree-modal');
      if (dlgc) { if (dlgc.close) dlgc.close(); else dlgc.removeAttribute('open'); }
      return;
    }
    var zoomBtn = ev.target.closest('[data-eternal-zoom]');
    if (zoomBtn) {
      ev.preventDefault();
      var dlgz = zoomBtn.closest('dialog.eternal-tree-modal');
      if (!dlgz) return;
      var action = zoomBtn.getAttribute('data-eternal-zoom');
      if (action === 'fit') fitEternalTree(dlgz);
      else applyEternalZoom(dlgz, (dlgz._etZoom || 1) + (action === 'in' ? 0.15 : -0.15));
      return;
    }
    var nodeBtn = ev.target.closest('.eternal-node[data-ope-node]');
    if (nodeBtn) {
      ev.preventDefault();
      var data;
      try { data = JSON.parse(nodeBtn.getAttribute('data-ope-node')); } catch (e) { return; }
      openMechModal(data.nombre || 'Nodo', buildEternalNodeModalHtml(data));
    }
  });
  window.addEventListener('resize', function(){
    document.querySelectorAll('dialog.eternal-tree-modal[open]').forEach(fitEternalTree);
  });

  function syncCyborgBanner(){
    var cy = document.querySelector('input[data-cyborg="1"]');
    var ban = document.getElementById('cyborgBanner');
    if (!cy || !ban) return;
    ban.hidden = !cy.checked;
    if (cy.checked && !cyborgSeen) {
      cyborgSeen = true;
      openHelpKey('cyborg');
    }
    if (!cy.checked) {
      var slot = document.getElementById('cyborgSlot');
      if (slot) slot.value = '';
    }
  }
  var cyChk = document.querySelector('input[data-cyborg="1"]');
  if (cyChk) cyChk.addEventListener('change', syncCyborgBanner);
  syncCyborgBanner();
})();
</script>

<dialog class="ope-mech-modal" id="opeMechModal">
  <form method="dialog" class="ope-mech-modal__panel">
    <header class="ope-mech-modal__h">
      <h2 id="opeMechTitle">Ayuda</h2>
      <button type="submit" class="ope-mech-modal__close" aria-label="Cerrar">×</button>
    </header>
    <div class="ope-mech-modal__b" id="opeMechBody"></div>
    <footer class="ope-mech-modal__f">
      <button type="submit" class="btn btn-hot">Entendido</button>
    </footer>
  </form>
</dialog>
<?php endif; ?>

</body>
</html>
