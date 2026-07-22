<?php
/**
 * One Piece: Eternal · Personajes ("Mi expediente" / roster)
 * Página de front-end MyBB (dirección "One Piece: Eternal").
 *
 * El usuario autenticado ve sus personajes (mybb_rol_personajes) en tarjetas
 * y puede marcar cuál es el personaje ACTIVO (con el que publica). La selección
 * hace un POST a esta misma página, validando la post key de MyBB.
 * Sin fichas de ejemplo: si no hay personajes, estado vacío honesto.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'personajes.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid       = (int) ($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

require_once MYBB_ROOT . 'inc/ope_user_init.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
if (is_file(MYBB_ROOT . 'inc/ope_rol_system.php')) {
    require_once MYBB_ROOT . 'inc/ope_rol_system.php';
}

// ── Nivel de staff (lo expone el plugin ope_rol; con respaldo directo) ──
$staff_level = ope_get_staff_level($uid);

// Iniciales para el botón de usuario
$initials   = ope_get_initials($mybb->user['username'] ?? '');
$initials_e = htmlspecialchars_uni($initials);

// Nombre a mostrar en la navbar: personaje activo o, en su defecto, la cuenta.
$display_name   = ope_get_display_name();
$display_name_e = htmlspecialchars_uni($display_name);

// ── Mapa de tramo → token de calor (clase CSS) ──
function ope_heat_token(string $rango): string
{
    $map = array(
        'I' => 'h2', 'II' => 'h4', 'III' => 'h6', 'IV' => 'h7', 'V' => 'h8', 'P' => 'h9',
    );
    $rango = strtoupper(trim($rango));
    return $map[$rango] ?? 'h3';
}

function ope_estado_meta(string $estado): array
{
    switch ($estado) {
        case 'aprobado':  return array('Aprobado', 'ok');
        case 'revision':  return array('En revisión', 'rev');
        case 'rechazado': return array('Rechazado', 'bad');
        case 'eliminado': return array('Eliminado', 'gone');
        default:          return array('Borrador', 'draft');
    }
}

/** Progreso hacia el siguiente nivel a partir de stats_ganados. */
function ope_pj_xp_progress(int $nivel, int $stats_ganados): array
{
    $nivel = max(1, $nivel);
    $floor = function_exists('ope_rol_stats_para_nivel') ? (int) ope_rol_stats_para_nivel($nivel) : ($nivel - 1) * 20;
    $ceil  = function_exists('ope_rol_stats_para_nivel') ? (int) ope_rol_stats_para_nivel($nivel + 1) : $nivel * 20;
    $span  = max(1, $ceil - $floor);
    $into  = max(0, min($span, $stats_ganados - $floor));
    return array(
        'pct'  => (int) round(100 * $into / $span),
        'next' => max(0, $ceil - $stats_ganados),
    );
}

// ── Vista: personajes propios o NPCs asignados ──
$vista = $mybb->get_input('vista');
if ($vista !== 'npcs') {
    $vista = 'personajes';
}

// ¿Cuenta narrador? (tiene algún personaje con staff_narrador=1)
$es_narrador_cuenta = false;
if ($loggedin && $db->table_exists('rol_personajes')) {
    $nrq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND staff_narrador = 1", array('limit' => 1));
    $es_narrador_cuenta = $db->num_rows($nrq) > 0;
}

// ─────────────────────────────────────────────────────────────
// POST: seleccionar personaje activo
// ─────────────────────────────────────────────────────────────
$flash = '';
$flash_kind = 'ok';
if ($loggedin && $mybb->request_method === 'post' && $db->table_exists('rol_personajes')) {
    $action  = $mybb->get_input('action');
    $set_pid = $mybb->get_input('pid', MyBB::INPUT_INT);

    if ($action === 'set_active' && verify_post_check($mybb->get_input('my_post_key'), true) && $set_pid > 0) {
        // Verifica que el personaje pertenece al usuario (jugador o NPC asignado).
        $npc_clause = $db->field_exists('es_npc', 'rol_personajes') ? ', es_npc' : '';
        $vq = $db->simple_select(
            'rol_personajes',
            'pid, estado, nombre' . $npc_clause,
            "pid = {$set_pid} AND uid = {$uid}",
            array('limit' => 1)
        );
        if ($db->num_rows($vq)) {
            $prow = $db->fetch_array($vq);
            $es_npc_row = (int) ($prow['es_npc'] ?? 0) === 1;
            // Se puede activar un personaje aprobado o EN REVISIÓN (con este
            // último solo se podrá postear en Off Topic hasta la aprobación).
            // NPCs asignados siempre están aprobados.
            if ($prow['estado'] !== 'eliminado' && $prow['estado'] !== 'rechazado') {
                // Solo un activo por cuenta: desactiva el resto y activa el elegido.
                $db->update_query('rol_personajes', array('activo' => 0), "uid = {$uid}");
                $db->update_query('rol_personajes', array('activo' => 1), "pid = {$set_pid} AND uid = {$uid}");

                // Sincroniza mybb_rol_cuentas.personaje_activo (upsert).
                if ($db->table_exists('rol_cuentas')) {
                    $exists = $db->simple_select('rol_cuentas', 'uid', "uid = {$uid}", array('limit' => 1));
                    if ($db->num_rows($exists)) {
                        $db->update_query('rol_cuentas', array('personaje_activo' => $set_pid), "uid = {$uid}");
                    } else {
                        $db->insert_query('rol_cuentas', array(
                            'uid'              => $uid,
                            'staff_level'      => 0,
                            'slots'            => 1,
                            'personaje_activo' => $set_pid,
                            'dateline'         => TIME_NOW,
                        ));
                    }
                }
                $mybb->user['ope_active_pid'] = $set_pid;
                $flash = 'Personaje activo actualizado: ' . htmlspecialchars_uni($prow['nombre']) . '.';
                if ($es_npc_row) {
                    $flash .= ' Estás posteando como NPC.';
                }
                if ($prow['estado'] === 'revision') {
                    $flash .= ' Está en revisión: solo podrás publicar en la zona Off Topic hasta que el staff lo apruebe.';
                }
            } else {
                $flash = 'No se puede activar un personaje rechazado o eliminado.';
                $flash_kind = 'warn';
            }
        } else {
            $flash = 'Ese personaje no existe o no es tuyo.';
            $flash_kind = 'warn';
        }
    } else {
        $flash = 'No se pudo procesar la solicitud (clave de sesión no válida).';
        $flash_kind = 'warn';
    }
}

// ─────────────────────────────────────────────────────────────
// Carga de personajes del usuario (excluye eliminados y NPCs)
// ─────────────────────────────────────────────────────────────
$personajes = array();
$npcs = array();
if ($loggedin && $db->table_exists('rol_personajes')) {
    $npc_col = $db->field_exists('es_npc', 'rol_personajes') ? ', es_npc' : '';
    $extra = '';
    if ($db->field_exists('datos', 'rol_personajes')) $extra .= ', datos';
    if ($db->field_exists('stats_json', 'rol_personajes')) $extra .= ', stats_json';
    if ($db->field_exists('stats_ganados', 'rol_personajes')) $extra .= ', stats_ganados';
    if ($db->field_exists('pv_max', 'rol_personajes')) $extra .= ', pv_max';
    if ($db->field_exists('en_max', 'rol_personajes')) $extra .= ', en_max';
    if ($db->field_exists('pa_por_turno', 'rol_personajes')) $extra .= ', pa_por_turno';
    $q = $db->simple_select(
        'rol_personajes',
        'pid, nombre, slug, estado, activo, rango, nivel, avatar, icono' . $npc_col . $extra,
        "uid = {$uid} AND estado <> 'eliminado'",
        array('order_by' => 'activo', 'order_dir' => 'DESC')
    );
    while ($row = $db->fetch_array($q)) {
        if ((int) ($row['es_npc'] ?? 0) === 1) {
            $npcs[] = $row;
        } else {
            $personajes[] = $row;
        }
    }
}
$tiene_personajes = count($personajes) > 0;
$tiene_npcs = count($npcs) > 0;

$personajes_moderados = array();
$unread_map = array();
if ($loggedin && $db->table_exists('rol_mensajes')) {
    $pids = array_column($personajes, 'pid');
    if ($pids) {
        $pid_list = implode(',', array_map('intval', $pids));
        $msgs = $db->query("
            SELECT destino_pid, COUNT(*) as cnt FROM " . TABLE_PREFIX . "rol_mensajes
            WHERE destino_pid IN ({$pid_list}) AND leido = 0
            AND asunto LIKE 'Moderación:%'
            GROUP BY destino_pid
        ");
        while ($row = $db->fetch_array($msgs)) {
            $unread_map[(int)$row['destino_pid']] = (int)$row['cnt'];
        }
    }
    foreach ($personajes as $pj) {
        if ($pj['estado'] === 'revision') {
            if (($unread_map[(int)$pj['pid']] ?? 0) > 0) {
                $personajes_moderados[(int)$pj['pid']] = true;
            }
        }
    }
}

// Huecos de personaje disponibles (mybb_rol_cuentas.slots, por defecto 1)
$slots = 1;
if ($loggedin && $db->table_exists('rol_cuentas')) {
    $scq = $db->simple_select('rol_cuentas', 'slots', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($scq)) {
        $slots = (int) $db->fetch_field($scq, 'slots');
    }
}
$usados_slots = 0;
foreach ($personajes as $pj) {
    if ($pj['estado'] !== 'rechazado' && $pj['estado'] !== 'eliminado') {
        $usados_slots++;
    }
}
$hay_hueco = $usados_slots < $slots;

// Lista visible según toggle
$lista_visible = ($vista === 'npcs') ? $npcs : $personajes;
$tiene_visible = count($lista_visible) > 0;

if ($flash === '' && $loggedin && $mybb->get_input('forjado', MyBB::INPUT_INT)) {
    $flash = 'Tu personaje se envió a revisión. El staff lo revisará y podrás activarlo en cuanto sea aprobado.';
    $flash_kind = 'ok';
}


header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Formación</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-personajes) -->
</head>
<body class="ope-pg-personajes">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Formación</b>
  </div>
</div>

<div class="wrap pj-wrap">

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_kind; ?> reveal vis"><?php echo $flash; ?></div>
<?php endif; ?>

  <section class="pj-formation reveal">
    <header class="pj-form-head">
      <div class="pj-form-titles">
        <h1><?php echo $vista === 'npcs' ? 'Narración' : 'Formación'; ?></h1>
        <p class="pj-form-sub"><?php echo $vista === 'npcs'
            ? 'Activa un NPC asignado para postear con su voz en el foro.'
            : 'Elige el personaje activo con el que publicarás en el foro.'; ?></p>
      </div>
<?php if ($loggedin && ($tiene_personajes || $tiene_npcs)): ?>
      <div class="pj-form-tools">
<?php if ($es_narrador_cuenta && $tiene_npcs): ?>
        <div class="pj-toggle" role="tablist" aria-label="Tipo de personaje">
          <a href="<?php echo $bburl; ?>/personajes.php?vista=personajes" class="pj-toggle-btn<?php echo $vista === 'personajes' ? ' active' : ''; ?>" role="tab">Personajes</a>
          <a href="<?php echo $bburl; ?>/personajes.php?vista=npcs" class="pj-toggle-btn<?php echo $vista === 'npcs' ? ' active' : ''; ?>" role="tab">NPCs <span class="pj-toggle-n"><?php echo count($npcs); ?></span></a>
        </div>
<?php endif; ?>
        <span class="pj-count"><b><?php echo $usados_slots; ?>/<?php echo $slots; ?></b> huecos</span>
      </div>
<?php endif; ?>
    </header>

<?php if ($loggedin && ($tiene_personajes || $tiene_npcs)): ?>

<?php if ($tiene_visible):
    $active_pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
    if ($active_pid < 1 && $loggedin && $db->table_exists('rol_cuentas')) {
        $acq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($acq)) {
            $active_pid = (int) $db->fetch_field($acq, 'personaje_activo');
        }
    }
    if ($active_pid < 1 && !empty($personajes)) {
        foreach ($personajes as $p_chk) {
            if ((int) ($p_chk['activo'] ?? 0) === 1) {
                $active_pid = (int) $p_chk['pid'];
                break;
            }
        }
        if ($active_pid < 1 && isset($personajes[0]['pid'])) {
            $active_pid = (int) $personajes[0]['pid'];
        }
    }
?>
    <div class="pj-party" role="list">
<?php foreach ($lista_visible as $pj):
        $es_activo   = ($active_pid > 0 && (int) $pj['pid'] === $active_pid);
        $stats_ganados = (int) ($pj['stats_ganados'] ?? 0);
        $nivel       = function_exists('ope_rol_nivel_from_stats_comprados')
            ? (int) ope_rol_nivel_from_stats_comprados($stats_ganados)
            : max(1, (int) $pj['nivel']);
        $rango_code  = ($nivel >= 50)
            ? 'P'
            : (function_exists('ope_rol_tramo_romano') ? ope_rol_tramo_romano(ope_rol_tramo($nivel)) : (string) $pj['rango']);
        $rango_lbl   = ($nivel >= 50)
            ? 'Prestigio'
            : ('Tramo ' . $rango_code);
        $rango_e     = htmlspecialchars_uni($rango_lbl);
        $heat        = ope_heat_token($rango_code);
        list($est_lbl, $est_cls) = ope_estado_meta((string) $pj['estado']);
        $nombre_e    = htmlspecialchars_uni($pj['nombre']);
        $es_npc_card = ($vista === 'npcs');
        $av_initial  = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));

        $datos = json_decode((string) ($pj['datos'] ?? ''), true);
        if (!is_array($datos)) $datos = array();
        $stats = json_decode((string) ($pj['stats_json'] ?? ''), true);
        if (!is_array($stats) || empty($stats)) {
            $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
        }

        $img_art = trim((string) ($datos['retrato'] ?? ''));
        if ($img_art === '') $img_art = trim((string) ($pj['avatar'] ?? ''));
        if ($img_art === '') $img_art = trim((string) ($pj['icono'] ?? ''));

        $pv = (int) ($pj['pv_max'] ?? 0);
        $en = (int) ($pj['en_max'] ?? 0);
        $pa = (int) ($pj['pa_por_turno'] ?? 0);
        if ($pv < 1 && function_exists('ope_combat_calc_pv') && !empty($stats)) {
            $pv = (int) ope_combat_calc_pv($stats, $nivel);
        }
        if ($en < 1 && function_exists('ope_combat_calc_en') && !empty($stats)) {
            $en = (int) ope_combat_calc_en($stats, $nivel);
        }
        if ($pa < 1 && function_exists('ope_combat_calc_pa') && !empty($stats)) {
            $pa = (int) ope_combat_calc_pa($stats, $nivel);
        }
        $xp = ope_pj_xp_progress($nivel, $stats_ganados);
        $can_activate = !$es_activo && ($pj['estado'] !== 'eliminado' && $pj['estado'] !== 'rechazado');
?>
      <article class="pj-slot<?php echo $es_activo ? ' is-active' : ''; ?><?php echo $es_npc_card ? ' is-npc' : ''; ?><?php echo $can_activate ? ' is-selectable' : ''; ?>" role="listitem">
        <div class="pj-slot-art" aria-hidden="true">
<?php if ($img_art !== ''): ?>
          <img src="<?php echo htmlspecialchars_uni($img_art); ?>" alt="" loading="lazy" onerror="this.remove()">
<?php else: ?>
          <span class="pj-slot-ph"><?php echo htmlspecialchars_uni($av_initial); ?></span>
<?php endif; ?>
          <div class="pj-slot-veil"></div>
        </div>

        <div class="pj-slot-meta">
          <div class="pj-slot-lv">
            <span class="pj-slot-lv-lbl">Lv</span>
            <b><?php echo $nivel; ?></b>
          </div>
          <div class="pj-slot-xp" title="Progreso al siguiente nivel">
            <span class="pj-slot-xp-bar"><i style="--pj-xp:<?php echo (int) $xp['pct']; ?>%"></i></span>
            <span class="pj-slot-xp-lbl">NEXT <b><?php echo (int) $xp['next']; ?></b></span>
          </div>
          <a class="pj-slot-name" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>"><?php echo $nombre_e; ?></a>
          <div class="pj-slot-stats">
            <span class="pj-st pj-st-pv" title="PV"><i></i><b><?php echo (int) $pv; ?></b></span>
            <span class="pj-st pj-st-en" title="Energía"><i></i><b><?php echo (int) $en; ?></b></span>
            <span class="pj-st pj-st-pa" title="PA / turno"><i></i><b><?php echo (int) $pa; ?></b></span>
          </div>
          <div class="pj-slot-chips">
            <span class="pj-chip heat heat-<?php echo htmlspecialchars_uni($heat); ?>"><?php echo $rango_e; ?></span>
<?php if ($es_npc_card): ?>
            <span class="pj-chip npc">NPC</span>
<?php else: ?>
            <span class="pj-chip st-<?php echo htmlspecialchars_uni($est_cls); ?>"><?php echo $est_lbl; ?></span>
<?php endif; ?>
<?php if ($es_activo): ?>
            <span class="pj-chip active-mark">Activo</span>
<?php endif; ?>
          </div>
<?php if (!$es_npc_card && $pj['estado'] === 'revision'): ?>
          <p class="pj-slot-note">En revisión &middot; solo Off Topic</p>
<?php endif; ?>
<?php if (!$es_npc_card && $pj['estado'] === 'revision' && isset($personajes_moderados[(int) $pj['pid']])): ?>
          <p class="pj-slot-note warn">Cambios solicitados por el staff</p>
<?php endif; ?>
<?php if (!$es_npc_card && !$es_activo && $pj['estado'] !== 'aprobado' && $pj['estado'] !== 'revision'): ?>
          <p class="pj-slot-note">Pendiente de aprobación</p>
<?php endif; ?>
        </div>

        <div class="pj-slot-acts">
          <a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">Ficha</a>
<?php if ($can_activate): ?>
          <form method="post" action="<?php echo $bburl; ?>/personajes.php?vista=<?php echo htmlspecialchars_uni($vista); ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="set_active">
            <input type="hidden" name="pid" value="<?php echo (int) $pj['pid']; ?>">
            <button type="submit" class="btn btn-hot btn-sm">Activar</button>
          </form>
<?php elseif ($es_activo): ?>
          <span class="btn btn-ghost btn-sm btn-active-badge">Activo</span>
<?php endif; ?>
<?php if (!$es_npc_card && $pj['estado'] === 'revision' && isset($personajes_moderados[(int) $pj['pid']])): ?>
          <a href="<?php echo $bburl; ?>/crear-personaje.php?editar=<?php echo (int) $pj['pid']; ?>" class="btn btn-hot btn-sm">Editar</a>
<?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>

<?php if ($vista === 'personajes'):
    $huecos_vacios = max(0, $slots - $usados_slots);
    for ($i = 0; $i < $huecos_vacios; $i++):
?>
      <a class="pj-slot is-empty" href="<?php echo $bburl; ?>/crear-personaje.php" role="listitem">
        <span class="pj-empty-plus" aria-hidden="true">+</span>
        <span class="pj-empty-lbl">Hueco libre</span>
        <span class="pj-empty-cta">Crear personaje</span>
      </a>
<?php
    endfor;
endif; ?>
    </div>

    <footer class="pj-form-foot">
      <span class="pj-form-foot-l">Sesión: <b><?php echo $username; ?></b></span>
<?php if ($vista === 'personajes' && !$hay_hueco): ?>
      <div class="pj-form-prompts">
        <a href="<?php echo $bburl; ?>/tramites.php" class="pj-form-foot-link">Más huecos</a>
      </div>
<?php endif; ?>
    </footer>

<?php elseif ($vista === 'npcs'): ?>
    <div class="plate">
      <div class="plate-b">
        <div class="pj-empty">
          <div class="big">Sin NPCs asignados</div>
          <p>Cuando un administrador te asigne NPCs desde la Zona Staff, aparecerán aquí para que puedas postear como ellos.</p>
        </div>
      </div>
    </div>
<?php else: ?>
    <div class="plate">
      <div class="plate-b">
        <div class="pj-empty">
          <div class="big">Sin personajes propios</div>
          <p>Tus NPCs asignados están en la pestaña <b>NPCs</b>. Puedes crear un personaje propio cuando quieras.</p>
<?php if ($hay_hueco): ?>
          <div class="acts"><a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot btn-sm">Crear personaje</a></div>
<?php endif; ?>
        </div>
      </div>
    </div>
<?php endif; ?>

<?php elseif ($loggedin): ?>
    <div class="plate">
      <div class="plate-b">
        <div class="pj-empty">
          <span class="anvil" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
          <div class="big">Aún no has creado ningún personaje</div>
          <p>Cuando crees tu primera ficha, tu formación aparecerá aquí con atributos, inventario y crónica.</p>
          <div class="acts">
            <a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot">Crear personaje</a>
            <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost">Ver trámites</a>
          </div>
          <span class="pj-who">Sesión iniciada como <b><?php echo $username; ?></b></span>
        </div>
      </div>
    </div>

<?php else: ?>
    <div class="plate">
      <div class="plate-b">
        <div class="pj-empty">
          <span class="anvil" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
          <div class="big">Accede para ver tu formación</div>
          <p>Necesitas una cuenta en el foro para crear y consultar personajes.</p>
          <div class="acts">
            <a href="<?php echo $bburl; ?>/member.php?action=register" class="btn btn-hot">Regístrate</a>
            <a href="<?php echo $bburl; ?>/member.php?action=login" class="btn btn-ghost">Acceder</a>
          </div>
        </div>
      </div>
    </div>
<?php endif; ?>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>
