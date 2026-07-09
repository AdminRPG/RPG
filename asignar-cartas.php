<?php
/**
 * I-Forge · Asignar Cartas de Técnica (Administrador+)
 * ----------------------------------------------------
 * Asocia cartas YA CREADAS (biblioteca mybb_rol_cartas) al deck de un
 * personaje (mybb_rol_tecnicas). Al asignar se COPIA la carta al deck, con
 * su propia insignia; editar la biblioteca luego no altera lo ya asignado.
 *
 * Flujo:
 *   1. Elegir personaje (buscador).
 *   2. Ver su deck (quitar carta / marcar insignia).
 *   3. Explorar la biblioteca y asignar cartas al deck.
 *
 * Requiere mybb_rol_cartas y mybb_rol_tecnicas (scripts/migrate-rol-tecnicas.php).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'asignar-cartas.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

$staff = $loggedin ? ope_rol_active_staff($uid) : array('rank' => 0);
$rank  = (int) $staff['rank'];
if (!$loggedin || $rank < 3) {
    header('Location: ' . $bburl . '/index.php');
    exit;
}

$TIERS    = ope_rol_tecnica_tiers();
$pid      = (int) $mybb->get_input('pid', MyBB::INPUT_INT);
$buscar   = trim((string) $mybb->get_input('q'));
$libq     = trim((string) $mybb->get_input('lq'));
$ltier    = (int) $mybb->get_input('ltier', MyBB::INPUT_INT);
$hi_carta = (int) $mybb->get_input('carta', MyBB::INPUT_INT);
$flash    = '';
$flash_kind = 'ok';
$table_ok = $db->table_exists('rol_cartas') && $db->table_exists('rol_tecnicas');

// ── POST ──
if ($pid > 0 && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $table_ok && $db->table_exists('rol_personajes')) {

    $pj_exists = $db->num_rows($db->simple_select('rol_personajes', 'pid', "pid = {$pid}", array('limit' => 1))) > 0;
    $action = $mybb->get_input('action');

    if ($pj_exists && $action === 'assign') {
        $carta_id = (int) $mybb->get_input('carta_id', MyBB::INPUT_INT);
        $cq = $db->simple_select('rol_cartas', '*', "id = {$carta_id}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $c = $db->fetch_array($cq);
            $ordq = $db->simple_select('rol_tecnicas', 'MAX(disporder) AS m', "pid = {$pid}");
            $order = (int) $db->fetch_field($ordq, 'm') + 1;
            $db->insert_query('rol_tecnicas', array(
                'pid'             => $pid,
                'origen_id'       => $carta_id,
                'nombre'          => $db->escape_string((string) $c['nombre']),
                'tier'            => (int) $c['tier'],
                'es_insignia'     => 0,
                'tags'            => $db->escape_string((string) $c['tags']),
                'coste_pa'        => (int) $c['coste_pa'],
                'coste_en'        => (int) $c['coste_en'],
                'reposo'          => (int) $c['reposo'],
                'requisito_stats' => $db->escape_string((string) $c['requisito_stats']),
                'dados'           => $db->escape_string((string) $c['dados']),
                'descripcion'     => $db->escape_string((string) $c['descripcion']),
                'disporder'       => $order,
                'dateline'        => TIME_NOW,
                'lastedit'        => TIME_NOW,
            ));
        }
        header('Location: ' . $bburl . '/asignar-cartas.php?pid=' . $pid . '&ok=assign#deck');
        exit;

    } elseif ($pj_exists && $action === 'unassign') {
        $tid = (int) $mybb->get_input('tid', MyBB::INPUT_INT);
        if ($tid > 0) $db->delete_query('rol_tecnicas', "id = {$tid} AND pid = {$pid}");
        header('Location: ' . $bburl . '/asignar-cartas.php?pid=' . $pid . '&ok=unassign#deck');
        exit;

    } elseif ($pj_exists && $action === 'toggle_insignia') {
        $tid = (int) $mybb->get_input('tid', MyBB::INPUT_INT);
        $cq = $db->simple_select('rol_tecnicas', 'es_insignia', "id = {$tid} AND pid = {$pid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $cur = (int) $db->fetch_field($cq, 'es_insignia');
            if ($cur) {
                $db->update_query('rol_tecnicas', array('es_insignia' => 0), "id = {$tid} AND pid = {$pid}");
            } else {
                $db->update_query('rol_tecnicas', array('es_insignia' => 0), "pid = {$pid}");
                $db->update_query('rol_tecnicas', array('es_insignia' => 1), "id = {$tid} AND pid = {$pid}");
            }
        }
        header('Location: ' . $bburl . '/asignar-cartas.php?pid=' . $pid . '&ok=1#deck');
        exit;
    }
}

$okp = $mybb->get_input('ok');
if ($okp === 'assign')        { $flash = 'Carta asignada al deck del personaje.'; }
elseif ($okp === 'unassign')  { $flash = 'Carta retirada del deck.'; }
elseif ($okp === '1')         { $flash = 'Deck actualizado.'; }

// ── Personaje ──
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', 'pid, nombre, uid, estado, rango, nivel, es_npc', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) $pj = $db->fetch_array($q);
}

$deck = $pj && $table_ok ? ope_rol_char_tecnicas($pid) : array();
// ids de biblioteca ya asignados a este personaje (para marcar "ya asignada").
$asignados_origen = array();
foreach ($deck as $d) { if ((int)($d['origen_id'] ?? 0) > 0) $asignados_origen[(int)$d['origen_id']] = true; }

// ── Biblioteca (solo si hay personaje) ──
$lib = ($pj && $table_ok) ? ope_rol_cartas_lib($libq, $ltier) : array();

// ── Listado de personajes (sin pid) ──
$listado = array();
if (!$pj && $db->table_exists('rol_personajes')) {
    $where = '1=1';
    if ($buscar !== '') $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
    $lq = $db->simple_select('rol_personajes', 'pid, nombre, uid, estado, rango, es_npc', $where,
        array('order_by' => 'nombre', 'order_dir' => 'ASC', 'limit' => 200));
    while ($lrow = $db->fetch_array($lq)) {
        $lrow['owner'] = '?';
        if ((int) $lrow['uid'] > 0) {
            $uq = $db->simple_select('users', 'username', 'uid = ' . (int) $lrow['uid'], array('limit' => 1));
            if ($db->num_rows($uq)) $lrow['owner'] = $db->fetch_field($uq, 'username');
        } elseif ((int) ($lrow['es_npc'] ?? 0) === 1) { $lrow['owner'] = 'NPC'; }
        $lrow['ndeck'] = 0;
        if ($table_ok) {
            $cq = $db->simple_select('rol_tecnicas', 'COUNT(*) AS c', 'pid = ' . (int) $lrow['pid']);
            $lrow['ndeck'] = (int) $db->fetch_field($cq, 'c');
        }
        $listado[] = $lrow;
    }
}

$carry = $hi_carta > 0 ? '&amp;carta=' . $hi_carta : '';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; <?php echo $pj ? 'Asignar cartas: ' . htmlspecialchars_uni($pj['nombre']) : 'Asignar cartas'; ?></title>
<?php echo ope_rol_head_base(); ?>
<?php echo ope_rol_tecnica_card_css(); ?>
<?php echo ope_rol_tecnica_forge_css(); ?>
</head>
<body class="ope-pg-zona-staff ope-pg-crear-personaje ope-pg-gestionar-personaje ope-pg-gestionar-cartas">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
<?php if ($pj): ?>
    <a href="<?php echo $bburl; ?>/asignar-cartas.php">Asignar cartas</a>
    <span class="sep">&#8250;</span>
    <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b>
<?php else: ?>
    <b>Asignar cartas</b>
<?php endif; ?>
  </div>
</div>

<div class="wrap" id="top">

  <section class="reveal">
    <div class="shead">
      <h1>Asignar cartas</h1>
      <span class="code">// biblioteca &rarr; deck del personaje</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$table_ok): ?>
  <section class="reveal">
    <div class="gc-warn">Faltan las tablas de cartas. Ejec&uacute;talas una vez con:<br>
      <code class="c-ember">php scripts/migrate-rol-tecnicas.php</code></div>
  </section>
<?php endif; ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="zs-flash"><?php echo $flash; ?></div></section>
<?php endif; ?>

<?php if (!$pj): ?>
  <section class="reveal">
    <p class="zs-intro">Elige el personaje al que quieres <b>asignar cartas</b> ya creadas. &iquest;Todav&iacute;a no has creado cartas? Hazlo en <a href="<?php echo $bburl; ?>/crear-cartas.php">Crear cartas</a>.</p>
<?php if ($hi_carta > 0): ?>
    <div class="zs-flash bg-h6">Selecciona el personaje al que asignar la carta elegida.</div>
<?php endif; ?>
    <form method="get" action="<?php echo $bburl; ?>/asignar-cartas.php" class="zs-search">
      <input type="text" name="q" value="<?php echo htmlspecialchars_uni($buscar); ?>" placeholder="Buscar personaje por nombre&hellip;">
<?php if ($hi_carta > 0): ?><input type="hidden" name="carta" value="<?php echo $hi_carta; ?>"><?php endif; ?>
      <button type="submit" class="btn btn-hot btn-sm">Buscar</button>
    </form>
  </section>

  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl">Personajes</span>
      <span class="need bg-h6"><?php echo count($listado); ?> resultado(s)</span>
      <span class="rule"></span>
    </div>
<?php if (empty($listado)): ?>
    <div class="empty-state"><div class="big">Sin resultados</div><p>Prueba con otro nombre.</p></div>
<?php else: ?>
    <div class="zs-stafftbl">
<?php foreach ($listado as $row): ?>
      <div class="zs-staffrow">
        <div class="zs-staffwho">
          <span class="zs-staffname"><a href="<?php echo $bburl; ?>/asignar-cartas.php?pid=<?php echo (int) $row['pid']; ?><?php echo $carry; ?>#deck"><?php echo htmlspecialchars_uni($row['nombre']); ?></a></span>
          <span class="zs-staffowner">// <?php echo htmlspecialchars_uni($row['owner']); ?> &middot; <?php echo htmlspecialchars_uni($row['rango']); ?><?php if ((int)($row['es_npc'] ?? 0) === 1): ?> &middot; NPC<?php endif; ?></span>
        </div>
        <span class="zs-staffnarr"><?php echo (int) $row['ndeck']; ?> en deck</span>
        <a href="<?php echo $bburl; ?>/asignar-cartas.php?pid=<?php echo (int) $row['pid']; ?><?php echo $carry; ?>#deck" class="btn btn-hot btn-sm">Asignar cartas</a>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

<?php else: ?>

  <section class="reveal">
    <p class="zs-intro mb-10">
      Deck de <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b>
      (pid <?php echo (int) $pj['pid']; ?>) &middot; rango <b><?php echo htmlspecialchars_uni($pj['rango']); ?></b>
      &middot; <b><?php echo count($deck); ?></b> carta(s) asignada(s).
    </p>
    <div class="gp-actions">
      <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="btn btn-ghost btn-sm">Ver ficha</a>
      <a href="<?php echo $bburl; ?>/crear-cartas.php" class="btn btn-ghost btn-sm">Crear m&aacute;s cartas</a>
      <a href="<?php echo $bburl; ?>/asignar-cartas.php" class="btn btn-ghost btn-sm">Cambiar personaje</a>
    </div>
  </section>

  <!-- Deck actual -->
  <section class="zs-group reveal" id="deck">
    <div class="zs-group-h">
      <span class="lbl">Deck actual</span>
      <span class="need bg-patina"><?php echo count($deck); ?> carta(s)</span>
      <span class="rule"></span>
    </div>
<?php if (empty($deck)): ?>
    <div class="empty-state"><div class="big">Deck vac&iacute;o</div><p>Asigna cartas desde la biblioteca de abajo.</p></div>
<?php else: ?>
    <div class="ope-tk-deck">
<?php foreach ($deck as $carta): ?>
      <div class="deck-item">
        <?php echo ope_rol_tecnica_card_html($carta); ?>
        <div class="deck-tools">
          <form method="post" action="<?php echo $bburl; ?>/asignar-cartas.php?pid=<?php echo (int)$pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="toggle_insignia">
            <input type="hidden" name="tid" value="<?php echo (int)$carta['id']; ?>">
            <button type="submit" class="btn btn-ghost btn-sm"><?php echo (int)$carta['es_insignia'] ? 'Quitar insignia' : 'Marcar insignia'; ?></button>
          </form>
          <form method="post" action="<?php echo $bburl; ?>/asignar-cartas.php?pid=<?php echo (int)$pj['pid']; ?>" onsubmit="return confirm('¿Retirar esta carta del deck del personaje?');">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="unassign">
            <input type="hidden" name="tid" value="<?php echo (int)$carta['id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm">Retirar</button>
          </form>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

  <!-- Biblioteca para asignar -->
  <section class="zs-group reveal mt-8" id="biblioteca">
    <div class="zs-group-h">
      <span class="lbl">Biblioteca de cartas</span>
      <span class="need bg-h6"><?php echo count($lib); ?> disponible(s)</span>
      <span class="rule"></span>
    </div>
    <form method="get" action="<?php echo $bburl; ?>/asignar-cartas.php" class="zs-search">
      <input type="hidden" name="pid" value="<?php echo (int)$pj['pid']; ?>">
      <input type="text" name="lq" value="<?php echo htmlspecialchars_uni($libq); ?>" placeholder="Buscar carta por nombre&hellip;">
      <select name="ltier" class="zs-staffsel">
        <option value="0">Todos los tiers</option>
<?php foreach ($TIERS as $tn => $t): ?>
        <option value="<?php echo $tn; ?>"<?php echo $ltier === $tn ? ' selected' : ''; ?>>Tier <?php echo $t['romano']; ?></option>
<?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-hot btn-sm">Filtrar</button>
    </form>
<?php if (empty($lib)): ?>
    <div class="empty-state"><div class="big">Biblioteca vac&iacute;a</div><p>No hay cartas creadas (o no coinciden con el filtro). Crea cartas en <a href="<?php echo $bburl; ?>/crear-cartas.php">Crear cartas</a>.</p></div>
<?php else: ?>
    <div class="gc-libgrid">
<?php foreach ($lib as $carta): $ya = isset($asignados_origen[(int)$carta['id']]); $hl = ($hi_carta === (int)$carta['id']); ?>
      <div class="deck-item<?php echo $hl ? ' gc-assigned' : ''; ?>" id="lib-<?php echo (int)$carta['id']; ?>">
        <?php echo ope_rol_tecnica_card_html($carta); ?>
        <div class="deck-tools">
          <form method="post" action="<?php echo $bburl; ?>/asignar-cartas.php?pid=<?php echo (int)$pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="assign">
            <input type="hidden" name="carta_id" value="<?php echo (int)$carta['id']; ?>">
            <button type="submit" class="btn btn-hot btn-sm"><?php echo $ya ? 'Asignar otra copia' : 'Asignar al deck'; ?></button>
          </form>
          <a href="<?php echo $bburl; ?>/crear-cartas.php?edit=<?php echo (int)$carta['id']; ?>#editor" class="btn btn-ghost btn-sm">Editar carta</a>
<?php if ($ya): ?><span class="zs-staffnarr c-patina">&#10003; ya en deck</span><?php endif; ?>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target); } }); }, { threshold: .06 });
  document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
} else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }
<?php if ($pj && $hi_carta > 0): ?>
(function(){ var el = document.getElementById('lib-<?php echo $hi_carta; ?>'); if (el){ el.scrollIntoView({behavior:'smooth', block:'center'}); } })();
<?php endif; ?>
</script>
</body>
</html>
