<?php
/**
 * I-Forge · Personajes ("Mi expediente" / roster)
 * Página de front-end MyBB (dirección "One Piece Eternal").
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

// ── Nivel de staff (lo expone el plugin ope_rol; con respaldo directo) ──
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['ope_staff_level'])) {
        $staff_level = (int) $mybb->user['ope_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $staff_level = (int) $db->fetch_field($cq, 'staff_level');
        }
    }
}

// Iniciales para el botón de usuario
$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string) $mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

// Nombre a mostrar en la navbar: personaje activo o, en su defecto, la cuenta.
$display_name   = (string) ($mybb->user['ope_display_name'] ?? ($mybb->user['username'] ?? ''));
$display_name_e = htmlspecialchars_uni($display_name);

// ── Mapa de rango → variable de calor ──
function ope_heat_var(string $rango): string
{
    $map = array(
        'F' => '--h1', 'E' => '--h1', 'D' => '--h2', 'C' => '--h3', 'B' => '--h4',
        'A' => '--h5', 'S' => '--h6', 'SS' => '--h7', 'SSS' => '--h8', 'M+' => '--h9',
    );
    $rango = strtoupper(trim($rango));
    return $map[$rango] ?? '--h1';
}

function ope_estado_label(string $estado): array
{
    switch ($estado) {
        case 'aprobado':  return array('Aprobado', 'var(--patina-hi)');
        case 'revision':  return array('En revisi&oacute;n', 'var(--h6)');
        case 'rechazado': return array('Rechazado', 'var(--crack)');
        case 'eliminado': return array('Eliminado', 'var(--ash)');
        default:          return array('Borrador', 'var(--rivet)');
    }
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
            if ($prow['estado'] === 'aprobado' || $prow['estado'] === 'revision') {
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
                $flash = 'Personaje activo actualizado: ' . htmlspecialchars_uni($prow['nombre']) . '.';
                if ($es_npc_row) {
                    $flash .= ' Estás posteando como NPC.';
                }
                if ($prow['estado'] === 'revision') {
                    $flash .= ' Está en revisión: solo podrás publicar en la zona Off Topic hasta que el staff lo apruebe.';
                }
            } else {
                $flash = 'Solo puedes activar un personaje aprobado o en revisión.';
                $flash_kind = 'warn';
            }
        } else {
            $flash = 'Ese personaje no existe o no es tuyo.';
            $flash_kind = 'warn';
        }
    } else {
        $flash = 'No se pudo procesar la solicitud (clave de sesi&oacute;n no v&aacute;lida).';
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
    $q = $db->simple_select(
        'rol_personajes',
        'pid, nombre, slug, estado, activo, rango, nivel, avatar, icono' . $npc_col,
        "uid = {$uid} AND estado <> 'eliminado'",
        array('order_by' => 'activo', 'order_dir' => 'desc')
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
<title><?php echo $bbname; ?> &middot; Personaje</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-personajes) -->
</head>
<body class="ope-pg-personajes">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Personaje</b>
  </div>
</div>

<div class="wrap">

  <?php echo ope_rol_deco_banner('ope/deco/personajes', 'Galería de personajes de la tripulación', 'Expediente de personajes'); ?>


  <section class="reveal">
    <div class="shead">
      <h1>Personaje</h1>
      <span class="code">// expedientes</span>
      <span class="rule"></span>
    </div>
    <p class="pj-intro">El <b>registro de personajes</b> de tu cuenta. Elige cu&aacute;l es tu <b>personaje activo</b>: ser&aacute; con el que publiques en el foro. Cada expediente re&uacute;ne ficha, mec&aacute;nicas, inventario y cr&oacute;nica.</p>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
<?php endif; ?>



  <section class="reveal">
<?php if ($loggedin && ($tiene_personajes || $tiene_npcs)): ?>
    <div class="pj-bar">
      <span class="pj-count"><b><?php echo $usados_slots; ?>/<?php echo $slots; ?></b> hueco(s) usados &middot; sesi&oacute;n: <b><?php echo $username; ?></b></span>
<?php if ($es_narrador_cuenta && $tiene_npcs): ?>
      <div class="pj-toggle" role="tablist" aria-label="Tipo de personaje">
        <a href="<?php echo $bburl; ?>/personajes.php?vista=personajes" class="pj-toggle-btn<?php echo $vista === 'personajes' ? ' active' : ''; ?>" role="tab">Personajes</a>
        <a href="<?php echo $bburl; ?>/personajes.php?vista=npcs" class="pj-toggle-btn<?php echo $vista === 'npcs' ? ' active' : ''; ?>" role="tab">NPCs <span class="pj-toggle-n"><?php echo count($npcs); ?></span></a>
      </div>
<?php endif; ?>
      <span class="pj-spacer"></span>
<?php if ($vista === 'personajes' && $hay_hueco): ?>
      <a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot btn-sm">Crear personaje</a>
<?php elseif ($vista === 'personajes'): ?>
      <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost btn-sm">Pedir m&aacute;s huecos</a>
<?php endif; ?>
    </div>

<?php if ($vista === 'npcs'): ?>
    <p class="pj-intro npc-view">NPCs asignados a tu cuenta de narrador. Activa uno para <b>postear como &eacute;l</b> en el foro.</p>
<?php endif; ?>

<?php if ($tiene_visible): ?>
    <div class="cards">
<?php foreach ($lista_visible as $pj):
        $es_activo   = ((int) $pj['activo']) === 1;
        $rango       = (string) $pj['rango'];
        $rango_e     = htmlspecialchars_uni($rango);
        $heat        = ope_heat_var($rango);
        list($est_lbl, $est_col) = ope_estado_label((string) $pj['estado']);
        $nombre_e    = htmlspecialchars_uni($pj['nombre']);
        $nivel       = (int) $pj['nivel'];
        $es_npc_card = ($vista === 'npcs');
        // Contexto pequeño: ICONO del personaje (fallback a avatar, luego inicial).
        $img_small   = trim((string) ($pj['icono'] ?? ''));
        if ($img_small === '') $img_small = trim((string) $pj['avatar']);
        $av_initial  = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));
?>
      <article class="pjcard<?php echo $es_activo ? ' active' : ''; ?><?php echo $es_npc_card ? ' npc' : ''; ?>">
        <div class="pjcard-top">
          <span class="pjcard-av">
<?php if ($img_small !== ''): ?>
            <img src="<?php echo htmlspecialchars_uni($img_small); ?>" alt="" onerror="this.remove()">
<?php else: ?>
            <?php echo htmlspecialchars_uni($av_initial); ?>
<?php endif; ?>
          </span>
          <div>
            <a class="pjcard-name" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>"><?php echo $nombre_e; ?></a>
            <div class="pjcard-sub"><?php echo $es_npc_card ? 'NPC' : 'Nivel ' . $nivel; ?></div>
          </div>
        </div>
        <div class="pjcard-body">
          <span class="heat-badge" style="background:var(<?php echo $heat; ?>)"><?php echo $rango_e; ?></span>
<?php if ($es_npc_card): ?>
          <span class="pjcard-chip npc">NPC</span>
<?php else: ?>
          <span class="pjcard-chip" style="background:<?php echo $est_col; ?>"><?php echo $est_lbl; ?></span>
<?php endif; ?>
        </div>
        <div class="pjcard-foot">
          <div class="pjcard-actions">
            <a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">Ver ficha</a>
<?php if ($es_activo): ?>
            <span class="is-active-note">&#9670; Activo</span>
<?php elseif ($pj['estado'] === 'aprobado' || $pj['estado'] === 'revision'): ?>
            <form method="post" action="<?php echo $bburl; ?>/personajes.php?vista=<?php echo $vista; ?>">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="action" value="set_active">
              <input type="hidden" name="pid" value="<?php echo (int) $pj['pid']; ?>">
              <button type="submit" class="btn btn-hot btn-sm">Activar</button>
            </form>
<?php endif; ?>
<?php if (!$es_npc_card && $pj['estado'] === 'revision' && isset($personajes_moderados[(int)$pj['pid']])): ?>
            <a href="<?php echo $bburl; ?>/crear-personaje.php?editar=<?php echo (int)$pj['pid']; ?>" class="btn btn-hot btn-sm">Editar ficha</a>
<?php endif; ?>
          </div>
<?php if (!$es_npc_card && !$es_activo && $pj['estado'] !== 'aprobado' && $pj['estado'] !== 'revision'): ?>
          <span class="pjcard-note">Pendiente</span>
<?php endif; ?>
<?php if (!$es_npc_card && $pj['estado'] === 'revision'): ?>
          <span class="pjcard-note">En revisi&oacute;n &middot; solo Off Topic</span>
<?php endif; ?>
<?php if (!$es_npc_card && $pj['estado'] === 'revision' && isset($personajes_moderados[(int)$pj['pid']])): ?>
          <span class="pjcard-note warn">Cambios solicitados por el staff</span>
<?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>
    </div>
<?php elseif ($vista === 'npcs'): ?>
    <div class="plate">
      <div class="plate-b">
        <div class="pj-empty">
          <div class="big">Sin NPCs asignados</div>
          <p>Cuando un administrador te asigne NPCs desde la Zona Staff, aparecer&aacute;n aqu&iacute; para que puedas postear como ellos.</p>
        </div>
      </div>
    </div>
<?php else: ?>
    <div class="plate">
      <div class="plate-b">
        <div class="pj-empty">
          <div class="big">Sin personajes propios</div>
          <p>Tus NPCs asignados est&aacute;n en la pesta&ntilde;a <b>NPCs</b>. Puedes crear un personaje propio cuando quieras.</p>
<?php if ($hay_hueco): ?>
          <div class="acts"><a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot btn-sm">Crear personaje</a></div>
<?php endif; ?>
        </div>
      </div>
    </div>
<?php endif; ?>

<?php elseif ($loggedin): ?>
    <div class="plate">
      <div class="plate-h">
        <span class="t">Mi expediente</span>
        <span class="c">// <?php echo $username; ?></span>
      </div>
      <div class="plate-b">
        <div class="pj-empty">
          <span class="anvil" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
          <div class="big">A&uacute;n no has creado ning&uacute;n personaje</div>
          <p>Cuando crees tu primera ficha, tu expediente aparecer&aacute; aqu&iacute; con sus atributos, inventario y cr&oacute;nica. Da el primer paso cuando quieras.</p>
          <div class="acts">
            <a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot">Crear personaje</a>
            <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost">Ver tr&aacute;mites</a>
          </div>
          <span class="pj-who">Sesi&oacute;n iniciada como <b><?php echo $username; ?></b></span>
        </div>
      </div>
    </div>

<?php else: ?>
    <div class="plate">
      <div class="plate-h">
        <span class="t">Mi expediente</span>
        <span class="c">// acceso requerido</span>
      </div>
      <div class="plate-b">
        <div class="pj-empty">
          <span class="anvil" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
          <div class="big">Accede para ver tu expediente</div>
          <p>Necesitas una cuenta en el foro para crear y consultar personajes. Reg&iacute;strate o accede con la tuya para empezar.</p>
          <div class="acts">
            <a href="<?php echo $bburl; ?>/member.php?action=register" class="btn btn-hot">Reg&iacute;strate</a>
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
