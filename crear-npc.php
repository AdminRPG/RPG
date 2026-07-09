<?php
/**
 * I-Forge · Crear NPC (wizard de creación para staff)
 * Página restringida a Administrador+ (rank >= 3).
 *
 * Mismo wizard de 7 pasos que crear-personaje.php, adaptado para NPC:
 * - Sin comprobación de slots.
 * - uid = 0.
 * - es_npc = 1, estado = 'aprobado' (auto-aprobado).
 * - Sin trámites ni alertas.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-npc.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

$staff = $loggedin
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank = (int) $staff['rank'];

if ($rank < 3) {
    header('Location: ' . $mybb->settings['bburl'] . '/index.php');
    exit;
}

$RAZAS      = ope_rol_razas();
$VIRTUDES   = ope_rol_virtudes();
$DEFECTOS   = ope_rol_defectos();
$FACCIONES  = ope_rol_facciones();
$PACKS      = ope_rol_packs_equipo();
$STATS      = ope_rol_stats();
$STAT_KEYS  = ope_rol_stat_keys();
$PC_BASE    = ope_rol_pc_iniciales();
$BERRIES_BASE = ope_rol_berries_iniciales();

// ─────────────────────────────────────────────────────────────
// POST: validar y crear
// ─────────────────────────────────────────────────────────────
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

if ($mybb->request_method === 'post') {
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

        // ---- Concepto ----
        $nombre = ope_rol_clean($mybb->get_input('nombre'), 120);
        $apodo = ope_rol_clean($mybb->get_input('apodo'), 60);
        $edad = ope_rol_clean($mybb->get_input('edad'), 20);
        $genero = ope_rol_clean($mybb->get_input('genero'), 40);
        $concepto = ope_rol_clean($mybb->get_input('concepto'), 600);

        if ($nombre === '' || function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') < 3 : strlen($nombre) < 3) {
            $errores[] = 'El nombre del personaje debe tener al menos 3 caracteres.';
        }
        if ($concepto === '') {
            $errores[] = 'Describe brevemente el concepto del NPC.';
        }
        if ($nombre !== '' && $db->table_exists('rol_personajes')) {
            $dupe = $db->simple_select('rol_personajes', 'pid', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
            if ($db->num_rows($dupe)) {
                $errores[] = 'Ya existe un personaje con ese nombre.';
            }
        }

        // ---- Sub-opción racial (Herencia Tribal / Linaje Colosal, INI-01) ----
        $sub_opciones_disp = (!$hibrido && isset($RAZAS[$raza1]['sub_opciones'])) ? $RAZAS[$raza1]['sub_opciones'] : array();
        $sub_opcion = $mybb->get_input('sub_opcion');
        if (!empty($sub_opciones_disp)) {
            if (!isset($sub_opciones_disp[$sub_opcion])) {
                $errores[] = 'Elige una opción para la pasiva secundaria de la raza del NPC.';
                $sub_opcion = '';
            }
        } else {
            $sub_opcion = '';
        }

        // ---- Stats ----
        $stats_base = array_fill_keys($STAT_KEYS, 1);
        $stats_raciales = $stats_base;
        if (isset($RAZAS[$raza1])) {
            foreach ($RAZAS[$raza1]['mod'] as $k => $v) {
                $stats_raciales[$k] = ($stats_raciales[$k] ?? 1) + $v;
            }
            if (!$hibrido) {
                $mod_sec = (!empty($sub_opciones_disp) && isset($sub_opciones_disp[$sub_opcion]))
                    ? $sub_opciones_disp[$sub_opcion]['mod']
                    : $RAZAS[$raza1]['mod_secundaria'];
                foreach ($mod_sec as $k => $v) {
                    $stats_raciales[$k] = ($stats_raciales[$k] ?? 1) + $v;
                }
            } elseif (isset($RAZAS[$raza2])) {
                foreach ($RAZAS[$raza2]['mod'] as $k => $v) {
                    $stats_raciales[$k] = ($stats_raciales[$k] ?? 1) + $v;
                }
            }
        }

        $max_bumps = 1;
        if (isset($RAZAS[$raza1]) && !empty($RAZAS[$raza1]['extra_stat_bump'])) {
            $max_bumps = 2;
        }
        if ($hibrido && isset($RAZAS[$raza2]) && !empty($RAZAS[$raza2]['extra_stat_bump'])) {
            $max_bumps = 2;
        }

        $bumps = $mybb->get_input('stat_bump', MyBB::INPUT_ARRAY);
        if (!is_array($bumps)) {
            $bumps = array();
        }
        $bumps = array_values(array_unique(array_intersect($bumps, $STAT_KEYS)));
        if (count($bumps) < 1) {
            $errores[] = 'Sube al menos una estadística en la creación.';
        }
        if (count($bumps) > $max_bumps) {
            $errores[] = 'Tu raza solo permite subir ' . $max_bumps . ' estadística(s) en la creación.';
        }

        $stats_efectivas = $stats_raciales;
        foreach ($bumps as $b) {
            $stats_efectivas[$b] = ($stats_efectivas[$b] ?? 1) + 1;
        }
        $suma = array_sum($stats_efectivas);
        $rango = ope_rol_rank_from_sum($suma);

        // ---- Virtudes y Defectos ----
        $virtudes_in = $mybb->get_input('virtudes', MyBB::INPUT_ARRAY);
        $defectos_in = $mybb->get_input('defectos', MyBB::INPUT_ARRAY);
        if (!is_array($virtudes_in)) $virtudes_in = array();
        if (!is_array($defectos_in)) $defectos_in = array();

        $pc_gastado = 0;
        $virtudes_sel = array();
        foreach ($virtudes_in as $vid) {
            $v = ope_rol_find_virtud($vid);
            if ($v === null) continue;
            $spec = !empty($v['spec']) ? ope_rol_clean($mybb->get_input('virtud_spec_' . $vid), 200) : '';
            if (!empty($v['spec']) && $spec === '') {
                $errores[] = 'La virtud "' . $v['nombre'] . '" requiere que especifiques un detalle.';
            }
            $pc_gastado += (int)$v['coste'];
            $virtudes_sel[$vid] = array('nombre' => $v['nombre'], 'coste' => (int)$v['coste'], 'spec' => $spec);
        }
        if (isset($virtudes_sel['V-RIQ-02']) && !isset($virtudes_sel['V-RIQ-01'])) {
            $errores[] = 'Adinerado 2 requiere tener Adinerado 1.';
        }
        if (isset($virtudes_sel['V-RIQ-03']) && !isset($virtudes_sel['V-RIQ-02'])) {
            $errores[] = 'Adinerado 3 requiere tener Adinerado 2.';
        }

        $pc_devuelto = 0;
        $defectos_sel = array();
        foreach ($defectos_in as $did) {
            $d = ope_rol_find_defecto($did);
            if ($d === null) continue;
            $spec = !empty($d['spec']) ? ope_rol_clean($mybb->get_input('defecto_spec_' . $did), 200) : '';
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
        $berries = $BERRIES_BASE;
        if (isset($virtudes_sel['V-RIQ-01'])) $berries += 1000000;
        if (isset($virtudes_sel['V-RIQ-02'])) $berries += 3000000;
        if (isset($virtudes_sel['V-RIQ-03'])) $berries += 10000000;

        // ---- Historia ----
        $historia_pasado = ope_rol_clean($mybb->get_input('historia_pasado'), 6000);
        $historia_motivacion = ope_rol_clean($mybb->get_input('historia_motivacion'), 3000);
        $historia_relaciones = ope_rol_clean($mybb->get_input('historia_relaciones'), 3000);
        $min_len = function_exists('mb_strlen') ? mb_strlen($historia_pasado, 'UTF-8') : strlen($historia_pasado);
        if ($min_len < 80) {
            $errores[] = 'Cuenta el pasado del NPC con algo más de detalle (mínimo ~80 caracteres).';
        }

        // ---- Insertar si todo OK ----
        if (empty($errores) && $db->table_exists('rol_personajes')) {
            $slug = my_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
            $slug = trim($slug, '-');

            $datos = array(
                'raza_principal' => $raza1,
                'raza_secundaria' => $hibrido ? $raza2 : null,
                'hibrido' => $hibrido,
                'sub_opcion_racial' => $sub_opcion,
                'apodo' => $apodo,
                'edad' => $edad,
                'genero' => $genero,
                'stats_base' => $stats_base,
                'stats_raciales' => $stats_raciales,
                'stats_efectivas' => $stats_efectivas,
                'stat_bumps' => $bumps,
                'rango_suma' => $suma,
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
            $economia = array('berries' => $berries);
            $bio = array(
                'concepto' => $concepto,
                'pasado' => $historia_pasado,
                'motivacion' => $historia_motivacion,
                'relaciones' => $historia_relaciones,
            );

            $pid = $db->insert_query('rol_personajes', array(
                'uid' => 0,
                'nombre' => $db->escape_string($nombre),
                'slug' => $db->escape_string($slug),
                'estado' => 'aprobado',
                'es_npc' => 1,
                'activo' => 0,
                'rango' => $rango,
                'nivel' => 1,
                'avatar' => '',
                'datos' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
                'inventario' => $db->escape_string(json_encode($inventario, JSON_UNESCAPED_UNICODE)),
                'economia' => $db->escape_string(json_encode($economia, JSON_UNESCAPED_UNICODE)),
                'bio' => $db->escape_string(json_encode($bio, JSON_UNESCAPED_UNICODE)),
                'dateline' => TIME_NOW,
                'lastedit' => TIME_NOW,
            ));

            $ok = true;
        }
    }
}

if ($ok) {
    header('Location: ' . $mybb->settings['bburl'] . '/crear-npc.php?creado=1&nombre=' . urlencode($nombre));
    exit;
}

$show_flash = $mybb->get_input('creado', MyBB::INPUT_INT) ? true : false;
$creado_nombre = htmlspecialchars_uni($mybb->get_input('nombre') ?? '');

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Crear NPC</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-crear-personaje ope-pg-crear-npc">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Crear NPC</b>
  </div>
</div>

<div class="wrap">

  <section>
    <div class="shead">
      <h1>Crear NPC</h1>
      <span class="code">// one piece eternal · staff</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($show_flash): ?>
  <div class="flash ok" style="margin-bottom:16px;padding:14px 18px;border:2px solid var(--patina);background:var(--iron-plate);display:flex;align-items:center;justify-content:space-between;gap:14px">
    <span style="font-family:var(--mono);font-size:.72rem;color:var(--patina-hi)">El NPC <b style="color:var(--paper)"><?php echo $creado_nombre; ?></b> se ha creado con estado <b>aprobado</b> y est&aacute; listo para ser asignado.</span>
    <span style="display:flex;gap:8px">
      <a href="<?php echo $bburl; ?>/crear-npc.php" class="btn btn-hot btn-sm">Crear otro</a>
      <a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost btn-sm">Zona Staff</a>
    </span>
  </div>
<?php endif; ?>

<?php if (!empty($errores)): ?>
  <div class="flash warn">No se pudo crear el NPC:
    <ul><?php foreach ($errores as $e) echo '<li>' . htmlspecialchars_uni($e) . '</li>'; ?></ul>
  </div>
<?php endif; ?>

  <p class="mono" style="font-size:.78rem;color:var(--paper-dim);max-width:76ch;margin-bottom:16px">
    Sigue los <b style="color:var(--paper)">7 pasos</b> para crear un personaje no jugador. El NPC se crea con estado <b style="color:var(--patina)">aprobado</b> directamente (sin revisi&oacute;n) y sin due&ntilde;o. Podr&aacute; asignarse a un Narrador desde Zona Staff.
  </p>

  <div class="wiz-progress" id="wizProgress"></div>

  <form method="post" action="<?php echo $bburl; ?>/crear-npc.php" id="wizForm">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">

    <!-- PASO 1: RAZA -->
    <div class="wiz-step on" data-step="1">
      <div class="plate">
        <div class="plate-h"><span class="t">1. Raza</span><span class="c">// pura o híbrida</span></div>
        <div class="plate-b">
          <div class="race-grid" id="raceGrid">
<?php foreach ($RAZAS as $rid => $r):
    $mod = json_encode($r['mod'], JSON_UNESCAPED_UNICODE);
    $modsec = json_encode($r['mod_secundaria'], JSON_UNESCAPED_UNICODE);
    $subop = json_encode($r['sub_opciones'] ?? array(), JSON_UNESCAPED_UNICODE);
?>
            <label class="race-card">
              <input type="radio" name="raza_principal" value="<?php echo $rid; ?>" data-mod='<?php echo htmlspecialchars_uni($mod); ?>' data-modsec='<?php echo htmlspecialchars_uni($modsec); ?>' data-subop='<?php echo htmlspecialchars_uni($subop); ?>' data-extra-bump="<?php echo !empty($r['extra_stat_bump']) ? '1' : '0'; ?>" data-nombre="<?php echo htmlspecialchars_uni($r['nombre']); ?>" required<?php echo isset($old['raza_principal']) && $old['raza_principal'] === $rid ? ' checked' : ''; ?>>
              <div class="rc-body">
                <div class="rc-name"><?php echo htmlspecialchars_uni($r['nombre']); ?></div>
                <div class="rc-resumen"><?php echo htmlspecialchars_uni($r['resumen']); ?></div>
                <div class="rc-pas"><b>Primaria</b> — <?php echo htmlspecialchars_uni($r['primaria_nombre']); ?>: <?php echo htmlspecialchars_uni($r['primaria_desc']); ?><br><b>Secundaria</b> — <?php echo htmlspecialchars_uni($r['secundaria_nombre']); ?>: <?php echo htmlspecialchars_uni($r['secundaria_desc']); ?></div>
              </div>
            </label>
<?php endforeach; ?>
          </div>

          <div class="field" style="margin-top:16px">
            <label class="flabel"><input type="checkbox" id="esHibrido" name="es_hibrido" value="1"<?php echo !empty($old['es_hibrido']) ? ' checked' : ''; ?>> ¿Es un híbrido de dos razas?</label>
            <p class="hint">Un híbrido obtiene SOLO las pasivas primarias de ambas razas (ninguna secundaria).</p>
          </div>
          <div class="field" id="razaSecundariaWrap" style="display:<?php echo !empty($old['es_hibrido']) ? 'block' : 'none'; ?>">
            <label class="flabel">Raza secundaria</label>
            <select name="raza_secundaria" id="razaSecundaria">
              <option value="">— elige —</option>
<?php foreach ($RAZAS as $rid => $r):
    $mod = json_encode($r['mod'], JSON_UNESCAPED_UNICODE);
?>
              <option value="<?php echo $rid; ?>" data-mod='<?php echo htmlspecialchars_uni($mod); ?>' data-extra-bump="<?php echo !empty($r['extra_stat_bump']) ? '1' : '0'; ?>"<?php echo isset($old['raza_secundaria']) && $old['raza_secundaria'] === $rid ? ' selected' : ''; ?>><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="field" id="subOpcionWrap" style="display:none">
            <label class="flabel" id="subOpcionLabel">Pasiva secundaria</label>
            <div id="subOpcionGrid" class="race-grid"></div>
            <p class="hint">Solo se elige si la raza es <b>pura</b> (sin híbrido): sustituye la pasiva secundaria genérica.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- PASO 2: CONCEPTO -->
    <div class="wiz-step" data-step="2">
      <div class="plate">
        <div class="plate-h"><span class="t">2. Nombre y concepto</span><span class="c">// quién es</span></div>
        <div class="plate-b">
          <div class="grid2">
            <div class="field"><label class="flabel">Nombre del NPC *</label><input type="text" name="nombre" maxlength="120" required value="<?php echo htmlspecialchars_uni($old['nombre'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Apodo (opcional)</label><input type="text" name="apodo" maxlength="60" value="<?php echo htmlspecialchars_uni($old['apodo'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Edad</label><input type="text" name="edad" maxlength="20" value="<?php echo htmlspecialchars_uni($old['edad'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Género</label><input type="text" name="genero" maxlength="40" value="<?php echo htmlspecialchars_uni($old['genero'] ?? ''); ?>"></div>
          </div>
          <p class="hint">¿Quieres que el NPC tenga una "D." en su nombre? Elige la virtud <b style="color:var(--paper)">Voluntad de D.</b> en el siguiente paso (Virtudes y Defectos).</p>
          <div class="field"><label class="flabel">Concepto / aspecto *</label><textarea name="concepto" required maxlength="600" placeholder="Quién es, qué aspecto tiene, qué lo mueve..."><?php echo htmlspecialchars_uni($old['concepto'] ?? ''); ?></textarea></div>
        </div>
      </div>
    </div>

    <!-- PASO 3: STATS -->
    <div class="wiz-step" data-step="3">
      <div class="plate">
        <div class="plate-h"><span class="t">3. Estadísticas</span><span class="c">// F(1) a M+(10)</span></div>
        <div class="plate-b">
          <p class="mono" style="font-size:.72rem;color:var(--paper-dim);margin-bottom:10px">Todas empiezan en <b style="color:var(--paper)">F</b>. Las pasivas raciales ya modifican el valor efectivo. Después puedes subir <b id="maxBumpsLabel" style="color:var(--h6)">1 estadística</b> un rango más.</p>
          <div id="statsContainer"></div>
          <div class="wiz-sum-bar"><span>Suma total: <b id="statSum">0</b></span><span>Rango del personaje: <b id="statRank">F</b></span></div>
        </div>
      </div>
    </div>

    <!-- PASO 4: VIRTUDES Y DEFECTOS -->
    <div class="wiz-step" data-step="4">
      <div class="plate">
        <div class="plate-h"><span class="t">4. Virtudes y defectos</span><span class="c">// 6 PC iniciales</span></div>
        <div class="plate-b">
          <div class="pc-bar" id="pcBar">PC disponibles: <span class="pc-num" id="pcNum">6</span> <span class="mono" style="font-size:.62rem;color:var(--ash)">(6 base − coste virtudes + devuelto por defectos)</span></div>

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
          <div class="fac-grid">
<?php foreach ($FACCIONES as $fid => $f): ?>
            <label class="fac-card">
              <input type="radio" name="faccion" value="<?php echo $fid; ?>" required<?php echo isset($old['faccion']) && $old['faccion'] === $fid ? ' checked' : ''; ?>>
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
        <div class="plate-h"><span class="t">6. Equipo inicial</span><span class="c">// elige el Pack</span></div>
        <div class="plate-b">
          <p class="hint" style="margin-bottom:12px">Elige el Pack de Equipo Inicial que mejor se adapte al concepto del NPC. Todos incluyen vestimenta básica de viaje, raciones para 5 días y <b style="color:var(--paper)">50.000 berries</b> iniciales.</p>
          <div class="race-grid" id="packGrid">
<?php foreach ($PACKS as $pid => $p): ?>
            <label class="race-card">
              <input type="radio" name="pack_equipo" value="<?php echo $pid; ?>" required<?php echo isset($old['pack_equipo']) && $old['pack_equipo'] === $pid ? ' checked' : ''; ?>>
              <div class="rc-body">
                <div class="rc-name"><?php echo htmlspecialchars_uni($p['nombre']); ?></div>
                <div class="rc-resumen"><?php echo htmlspecialchars_uni($p['resumen']); ?></div>
                <div class="rc-pas"><?php echo implode('<br>', array_map('htmlspecialchars_uni', $p['contenido'])); ?></div>
              </div>
            </label>
<?php endforeach; ?>
          </div>
          <div class="wiz-sum-bar">Berries iniciales: <b id="berriesOut">50.000</b></div>
          <p class="hint">Sin Fruta del Diablo ni Haki al inicio (se obtienen en juego).</p>
        </div>
      </div>
    </div>

    <!-- PASO 7: HISTORIA -->
    <div class="wiz-step" data-step="7">
      <div class="plate">
        <div class="plate-h"><span class="t">7. Historia</span><span class="c">// pasado, motivación, relaciones</span></div>
        <div class="plate-b">
          <div class="field"><label class="flabel">Pasado *</label><textarea name="historia_pasado" required style="min-height:160px" placeholder="De dónde viene, qué le ha pasado antes de empezar a rolear..."><?php echo htmlspecialchars_uni($old['historia_pasado'] ?? ''); ?></textarea></div>
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
      <button type="submit" class="btn btn-hot" id="wizSubmit" style="display:none">Crear NPC</button>
    </div>
  </form>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var STAT_LABELS = <?php echo json_encode($STATS, JSON_UNESCAPED_UNICODE); ?>;
  var RANK_BREAKS = [[66,'M+'],[56,'M'],[47,'SS'],[39,'S'],[32,'A'],[26,'B'],[21,'C'],[17,'D'],[14,'E'],[0,'F']];
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
        if (Object.keys(subop).length && !document.querySelector('input[name=sub_opcion]:checked')) return 'Elige una opción para la pasiva secundaria de la raza.';
      }
    }
    if (n === 2){
      var nombre = form.querySelector('[name=nombre]').value.trim();
      var concepto = form.querySelector('[name=concepto]').value.trim();
      if (nombre.length < 3) return 'Escribe un nombre de al menos 3 caracteres.';
      if (!concepto) return 'Describe el concepto del NPC.';
    }
    if (n === 3){
      var bumps = form.querySelectorAll('input[name="stat_bump[]"]:checked');
      if (bumps.length < 1) return 'Sube al menos una estadística.';
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
      if (form.querySelector('[name=historia_pasado]').value.trim().length < 80) return 'Cuenta el pasado del NPC con algo más de detalle (mínimo ~80 caracteres).';
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
    var mod1 = r1 ? JSON.parse(r1.dataset.mod || '{}') : {};
    var modsec1 = {};
    if (r1 && !hib){
      var subChk = subOpcionGrid.querySelector('input[name=sub_opcion]:checked');
      if (subChk){
        var subop = JSON.parse(r1.dataset.subop || '{}');
        modsec1 = (subop[subChk.value] && subop[subChk.value].mod) || {};
      } else {
        modsec1 = JSON.parse(r1.dataset.modsec || '{}');
      }
    }
    var mod2 = (r2opt && r2opt.value) ? JSON.parse(r2opt.dataset.mod || '{}') : {};
    var extraBump = (r1 && r1.dataset.extraBump === '1') || (r2opt && r2opt.value && r2opt.dataset.extraBump === '1');
    return {mod1: mod1, modsec1: modsec1, mod2: mod2, extraBump: extraBump};
  }

  function renderStats(){
    var container = document.getElementById('statsContainer');
    var rd = getRazaData();
    var maxBumps = rd.extraBump ? 2 : 1;
    document.getElementById('maxBumpsLabel').textContent = maxBumps + ' estadística' + (maxBumps > 1 ? 's' : '');

    var prevBumps = {};
    container.querySelectorAll('input[name="stat_bump[]"]:checked').forEach(function(c){ prevBumps[c.value] = true; });

    container.innerHTML = '';
    var totalSum = 0;
    Object.keys(STAT_LABELS).forEach(function(pk){
      var pillar = STAT_LABELS[pk];
      var pdiv = document.createElement('div');
      pdiv.className = 'stats-pillar';
      var h = document.createElement('div');
      h.className = 'stats-pillar-h';
      h.textContent = pillar.label;
      pdiv.appendChild(h);

      Object.keys(pillar.stats).forEach(function(sig){
        var base = 1;
        var racial = base + (rd.mod1[sig]||0) + (rd.modsec1[sig]||0) + (rd.mod2[sig]||0);
        var row = document.createElement('div');
        row.className = 'stat-row';
        var delta = racial - base;
        var deltaTxt = delta === 0 ? '=' : (delta > 0 ? '+' + delta : String(delta));
        var deltaCls = delta > 0 ? 'pos' : (delta < 0 ? 'neg' : '');
        row.innerHTML =
          '<span class="stat-name">' + pillar.stats[sig] + ' <span class="sig">' + sig + '</span></span>' +
          '<span class="stat-val ' + deltaCls + '">' + deltaTxt + '</span>' +
          '<span class="stat-eff" data-eff="' + sig + '">' + racial + '</span>' +
          '<label class="stat-bump"><input type="checkbox" name="stat_bump[]" value="' + sig + '"' + (prevBumps[sig] ? ' checked' : '') + '> +1</label>';
        pdiv.appendChild(row);
      });
      container.appendChild(pdiv);
    });

    // Enforce max bumps + recompute effective values & sum
    var bumpBoxes = container.querySelectorAll('input[name="stat_bump[]"]');
    function recompute(){
      var checked = container.querySelectorAll('input[name="stat_bump[]"]:checked');
      bumpBoxes.forEach(function(b){ b.disabled = (checked.length >= maxBumps && !b.checked); });
      var sum = 0;
      container.querySelectorAll('[data-eff]').forEach(function(el){
        var sig = el.dataset.eff;
        var box = container.querySelector('input[name="stat_bump[]"][value="' + sig + '"]');
        var raw = 1 + (rd.mod1[sig]||0) + (rd.modsec1[sig]||0) + (rd.mod2[sig]||0);
        var withBump = raw + (box && box.checked ? 1 : 0);
        el.textContent = withBump;
        sum += withBump;
      });
      document.getElementById('statSum').textContent = sum;
      var rank = 'F';
      for (var i=0;i<RANK_BREAKS.length;i++){ if (sum >= RANK_BREAKS[i][0]){ rank = RANK_BREAKS[i][1]; break; } }
      document.getElementById('statRank').textContent = rank;
    }
    bumpBoxes.forEach(function(b){ b.addEventListener('change', recompute); });
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

    // berries live
    var berries = 50000;
    if (r1c && r1c.checked) berries += 1000000;
    if (r2c && r2c.checked) berries += 3000000;
    if (r3c && r3c.checked) berries += 10000000;
    var out = document.getElementById('berriesOut');
    if (out) out.textContent = berries.toLocaleString('es-ES');
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
      '<div class="line"><b>Suma total</b>' + document.getElementById('statSum').textContent + ' — Rango ' + document.getElementById('statRank').textContent + '</div></div>' +
      '<div class="sum-block"><h4>Virtudes (' + virtudesNames.length + ')</h4><div class="line">' + (virtudesNames.join(', ') || 'Ninguna') + '</div></div>' +
      '<div class="sum-block"><h4>Defectos (' + defectosNames.length + ')</h4><div class="line">' + (defectosNames.join(', ') || 'Ninguno') + '</div></div>' +
      '<div class="sum-block"><h4>Equipo</h4><div class="line"><b>Pack</b>' + packTxt + '</div><div class="line"><b>Berries</b>' + document.getElementById('berriesOut').textContent + '</div></div>';
  }

  showStep(1);

  // ---- Reveal animation (same as zona-staff) ----
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){
      if (e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target); }
    }); }, { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
  }
})();
</script>
</body>
</html>
