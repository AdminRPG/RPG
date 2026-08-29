<?php
/**
 * One Piece: Eternal · Tienda Personal — Dashboard RPG de gestión
 * ----------------------------------------------------------------
 * Inventario completo: agregar, editar, eliminar, poner/quitar de venta,
 * gestión de precios, cantidades y categorías.
 *
 * Fallback demo cuando rol_inventario no existe.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tienda-personal.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);
$pid    = (int) ($mybb->user['ope_active_pid'] ?? 0);

if ($uid < 1) {
    header('Location: $bburl/member.php?action=login');
    exit;
}
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

// ── Personaje activo (D6.3: fuente canónica mybb_ope_personajes) ──
$pj = null;
$pj_name = 'Sin personaje activo';
$pj_money = 0;
if ($pid > 0 && $db->table_exists('ope_personajes')) {
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
        $pj_name = htmlspecialchars_uni($pj['nombre'] ?? '');
        // Economía canónica: cartera en mybb_ope_carteras (F3).
        $pj_money = (function_exists('ope7_cartera_get') && $pid > 0)
            ? (int) (ope7_cartera_get($pid)['cartera'] ?? 0)
            : (int) (json_decode((string) ($pj['economia'] ?? '{}'), true)['berries'] ?? 0);
    }
}

// ── Flash messages ──
$flash_msg  = '';
$flash_type = 'ok';

// ── Detectar tablas canónicas de inventario (D6.3: ope_inventario_personaje) ──
$has_inv_table = $db->table_exists('ope_inventario_personaje') && $db->table_exists('ope_objetos');

// ── POST actions ──
if ($mybb->request_method === 'post' && $pid > 0) {
    verify_post_check($mybb->get_input('my_post_key'));
    $action = $mybb->get_input('action');

    if ($action === 'add' && $has_inv_table) {
        // D6.3: los objetos se obtienen en tiendas y recompensas (F3) — el
        // inventario canónico referencia el catálogo ope_objetos, no se crea a mano.
        $flash_msg = 'Los objetos se obtienen en tiendas y recompensas (sistema de tiendas F3), no se crean a mano.';
        $flash_type = 'info';
    }

    if ($action === 'edit' && $has_inv_table) {
        $flash_msg = 'La gestión del inventario se realiza desde el sistema de tiendas (F3).';
        $flash_type = 'info';
    }

    if ($action === 'delete' && $has_inv_table) {
        $iid = (int) $mybb->get_input('item_id');
        if ($iid > 0) {
            $db->delete_query('ope_inventario_personaje', "id = {$iid} AND personaje_id = {$pid}");
            $flash_msg = 'Objeto eliminado del inventario.';
            $flash_type = 'info';
        }
    }

    if ($action === 'toggle_venta' && $has_inv_table) {
        $iid = (int) $mybb->get_input('item_id');
        if ($iid > 0) {
            // D6.3: vender = ope7_tienda_venta_npc (F3, paga con la cartera).
            $oq = $db->simple_select('ope_inventario_personaje', 'objeto_id, cantidad', "id = {$iid} AND personaje_id = {$pid}", array('limit' => 1));
            if ($db->num_rows($oq)) {
                $row = $db->fetch_array($oq);
                if (function_exists('ope7_tienda_venta_npc')) {
                    $res = ope7_tienda_venta_npc($pid, (int) $row['objeto_id'], max(1, (int) $row['cantidad']));
                    $flash_msg = (string) ($res['msg'] ?? 'Venta procesada.');
                    $flash_type = !empty($res['ok']) ? 'ok' : 'info';
                } else {
                    $flash_msg = 'Sistema de venta no disponible.';
                    $flash_type = 'info';
                }
            }
        }
    }
}

// ── Cargar inventario (canónico: ope_inventario_personaje → ope_objetos) ──
$objetos = array();
if ($pid > 0 && $has_inv_table) {
    $qi = $db->query("SELECT i.id AS iid, o.nombre, o.categoria, o.rareza, o.notas AS descripcion, i.cantidad,\n        COALESCE(o.precio_base, 0) AS precio_venta, 0 AS en_venta\n        FROM " . TABLE_PREFIX . "ope_inventario_personaje i\n        JOIN " . TABLE_PREFIX . "ope_objetos o ON o.id = i.objeto_id\n        WHERE i.personaje_id = {$pid} AND i.cantidad > 0\n        ORDER BY o.categoria, o.nombre");
    while ($row = $db->fetch_array($qi)) $objetos[] = $row;
}

// ── Fallback demo ──
$is_demo = empty($objetos) && !$has_inv_table;
if ($is_demo) {
    $objetos = array(
        array('iid'=>1,'nombre'=>'Espada Wado','categoria'=>'arma','rareza'=>'rara','cantidad'=>1,'precio_venta'=>500000,'en_venta'=>1,'descripcion'=>'Katana de acero Wazamono. Empuñadura envuelta en seda blanca, perteneciente a la escuela de三刀流.'),
        array('iid'=>2,'nombre'=>'Cronómetro de Hazashi','categoria'=>'equipo','rareza'=>'comun','cantidad'=>1,'precio_venta'=>0,'en_venta'=>0,'descripcion'=>'Reloj de bolsillo que mide el tiempo de navegación. Imprescindible para calcular rutas.'),
        array('iid'=>3,'nombre'=>'Carne seca','categoria'=>'consumible','rareza'=>'comun','cantidad'=>12,'precio_venta'=>50,'en_venta'=>1,'descripcion'=>'Ración para alta mar. Cada unidad dura 1 día de travesía.'),
        array('iid'=>4,'nombre'=>'Sake de Arlong Park','categoria'=>'consumible','rareza'=>'poco_comun','cantidad'=>3,'precio_venta'=>150,'en_venta'=>0,'descripcion'=>'Botella de sake del sótano del bar de los hombres-pescado. Sabor ahumado.'),
        array('iid'=>5,'nombre'=>'Mapa del Calm Belt','categoria'=>'especial','rareza'=>'legendaria','cantidad'=>1,'precio_venta'=>0,'en_venta'=>0,'descripcion'=>'Mapa antiguo con rutas seguras a través del Calm Belt. Extremadamente raro.'),
        array('iid'=>6,'nombre'=>'Hierro de Ebisu','categoria'=>'arma','rareza'=>'poco_comun','cantidad'=>5,'precio_venta'=>200,'en_venta'=>1,'descripcion'=>'Lingote de hierro pulido, material para forjar armas de grado superior.'),
        array('iid'=>7,'nombre'=>'Akuma no Mi (Hito Hito no Mi)','categoria'=>'especial','rareza'=>'epica','cantidad'=>1,'precio_venta'=>0,'en_venta'=>0,'descripcion'=>'Fruta del Diablo tipo Zoan. Permite transformarse en humano.'),
        array('iid'=>8,'nombre'=>'Cuerda de kaya','categoria'=>'equipo','rareza'=>'comun','cantidad'=>20,'precio_venta'=>30,'en_venta'=>1,'descripcion'=>'Cuerda resistente de fibra natural. Útil para amar cargas o escalar.'),
        array('iid'=>9,'nombre'=>'Tabaco de la Ruta Mayor','categoria'=>'consumible','rareza'=>'comun','cantidad'=>8,'precio_venta'=>25,'en_venta'=>1,'descripcion'=>'Cigarros de tabaco costeño. Marca favorita de cierto capitán pelirrojo.'),
        array('iid'=>10,'nombre'=>'Grappling Hook','categoria'=>'equipo','rareza'=>'rara','cantidad'=>1,'precio_venta'=>120000,'en_venta'=>1,'descripcion'=>'Gancho de acero con cable retráctil. Alcance: 30 metros. Usado por francotiradores.'),
    );
}

// ── Estadísticas ──
$total_tipos   = count($objetos);
$total_unidades = 0;
$total_valor   = 0;
$en_venta      = 0;
$legendarias   = 0;
$raras         = 0;
$stats_cat     = array('arma'=>0,'equipo'=>0,'consumible'=>0,'especial'=>0);
foreach ($objetos as $o) {
    $total_unidades += (int) ($o['cantidad'] ?? 1);
    $pv = (int) ($o['precio_venta'] ?? 0);
    $total_valor += $pv * (int) ($o['cantidad'] ?? 1);
    if (!empty($o['en_venta']) && $pv > 0) $en_venta++;
    $rz = $o['rareza'] ?? 'comun';
    if ($rz === 'legendaria') $legendarias++;
    if ($rz === 'rara') $raras++;
    $cat = $o['categoria'] ?? 'otro';
    if (isset($stats_cat[$cat])) $stats_cat[$cat]++;
}
$berries_str = number_format($pj_money, 0, ',', '.');
$valor_str   = number_format($total_valor, 0, ',', '.');

$page_title = $bbname . ' · Tienda Personal';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>
<?php echo ope_rol_head_base(); ?>
<style>
/* ═══ Dashboard RPG ═══ */
.rpg-dash{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;margin-bottom:20px}
.rpg-stat{padding:16px 14px;background:var(--ope-card);border:1px solid var(--ope-line);border-radius:12px;text-align:center;position:relative;overflow:hidden}
.rpg-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.rpg-stat--gold::before{background:linear-gradient(90deg,var(--ope-gold),var(--ope-gold-deep))}
.rpg-stat--green::before{background:linear-gradient(90deg,var(--patina),var(--patina-deep))}
.rpg-stat--red::before{background:var(--crack)}
.rpg-stat--blue::before{background:var(--ope-sky-deep)}
.rpg-stat--purple::before{background:#7c3aed}
.rpg-stat-val{display:block;font-family:var(--ope-disp);font-weight:800;font-size:1.5rem;color:var(--ope-ink);line-height:1.1}
.rpg-stat-lbl{font-family:var(--mono);font-size:.52rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--ope-ink-dim);margin-top:3px}
html[data-theme="noche"] .rpg-stat{background:rgba(14,23,48,.72);border-color:rgba(185,198,221,.12)}
/* ═══ Toolbar ═══ */
.rpg-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:16px}
.rpg-toolbar-filters{display:flex;flex-wrap:wrap;gap:5px;flex:1}
.rpg-toolbar-actions{display:flex;gap:8px}
.rpg-filter{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:6px 12px;border:1px solid var(--ope-line);border-radius:8px;background:var(--ope-card);color:var(--ope-ink-soft);cursor:pointer;transition:all .14s}
.rpg-filter:hover{color:var(--ope-ink);border-color:var(--ope-gold)}
.rpg-filter.active{background:var(--ope-gold-deep);color:#fff;border-color:var(--ope-gold-deep)}
html[data-theme="noche"] .rpg-filter{background:rgba(14,23,48,.72);border-color:rgba(185,198,221,.12)}
html[data-theme="noche"] .rpg-filter.active{background:var(--ope-gold);color:var(--ink)}
/* ═══ Tabla inventario ═══ */
.rpg-table-wrap{overflow-x:auto;border:1px solid var(--ope-line);border-radius:14px;background:var(--ope-card)}
html[data-theme="noche"] .rpg-table-wrap{background:rgba(14,23,48,.72);border-color:rgba(185,198,221,.12)}
.rpg-table{width:100%;border-collapse:collapse;font-size:.86rem}
.rpg-table th{font-family:var(--mono);font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ope-ink-dim);padding:11px 12px;text-align:left;border-bottom:2px solid var(--ope-line);background:var(--ope-card-2);position:sticky;top:0;z-index:1}
html[data-theme="noche"] .rpg-table th{background:rgba(185,198,221,.05)}
.rpg-table td{padding:10px 12px;border-bottom:1px solid var(--ope-line-soft);vertical-align:middle;color:var(--ope-ink)}
html[data-theme="noche"] .rpg-table td{border-color:rgba(185,198,221,.06)}
.rpg-table tr:last-child td{border-bottom:none}
.rpg-table tr:hover td{background:var(--ope-card-2)}
html[data-theme="noche"] .rpg-table tr:hover td{background:rgba(185,198,221,.04)}
/* Fila destacada (en venta) */
.rpg-table tr.on-sale td{background:color-mix(in srgb,var(--patina) 4%,transparent)}
html[data-theme="noche"] .rpg-table tr.on-sale td{background:color-mix(in srgb,var(--patina-hi) 3%,transparent)}
/* Nombre del objeto */
.rpg-obj-name{font-family:var(--ope-disp);font-weight:700;font-size:.9rem}
.rpg-obj-desc{font-size:.72rem;color:var(--ope-ink-soft);margin-top:1px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Badges */
.rpg-badge{display:inline-block;font-family:var(--mono);font-size:.5rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:2px 7px;border-radius:5px;line-height:1.3}
.rpg-badge--arma{background:var(--crack);color:#fff}
.rpg-badge--equipo{background:var(--ope-sky-deep);color:#fff}
.rpg-badge--consumible{background:var(--patina-deep);color:#fff}
.rpg-badge--especial{background:var(--ope-gold-deep);color:#fff}
.rpg-badge--otro{background:var(--ope-ink-dim);color:#fff}
/* Rareza */
.rpg-rare{font-family:var(--mono);font-size:.54rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.rpg-rare--comun{color:var(--ope-ink-dim)}
.rpg-rare--poco_comun{color:var(--patina-deep)}
.rpg-rare--rara{color:var(--ope-sky-deep)}
.rpg-rare--epica{color:var(--crack)}
.rpg-rare--legendaria{background:linear-gradient(90deg,var(--ope-gold),var(--crack));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
/* Cantidad */
.rpg-qty{text-align:center;font-family:var(--mono);font-weight:700;font-size:.88rem}
/* Precio */
.rpg-price{font-family:var(--mono);font-weight:700;font-size:.82rem}
.rpg-price--zero{color:var(--ope-ink-dim)}
.rpg-price--has{color:var(--patina-deep)}
/* Badge venta */
.rpg-sale-badge{display:inline-flex;align-items:center;gap:4px;font-family:var(--mono);font-size:.5rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:3px 8px;border-radius:5px;cursor:pointer;transition:all .14s}
.rpg-sale-badge--on{background:var(--patina);color:#06202e;border:1px solid var(--patina-deep)}
.rpg-sale-badge--off{background:var(--ope-card-2);color:var(--ope-ink-dim);border:1px solid var(--ope-line-soft)}
html[data-theme="noche"] .rpg-sale-badge--on{background:var(--patina-hi);color:#041a14}
html[data-theme="noche"] .rpg-sale-badge--off{background:rgba(185,198,221,.06)}
.rpg-sale-badge--on:hover{box-shadow:0 0 0 2px color-mix(in srgb,var(--patina) 30%,transparent)}
/* Acciones */
.rpg-actions{display:flex;gap:4px}
.rpg-action{width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--ope-line-soft);border-radius:6px;background:transparent;color:var(--ope-ink-soft);cursor:pointer;transition:all .12s}
.rpg-action:hover{border-color:var(--ope-gold);color:var(--ope-gold-deep);background:color-mix(in srgb,var(--ope-gold) 6%,transparent)}
.rpg-action--del:hover{border-color:var(--crack);color:var(--crack);background:color-mix(in srgb,var(--crack) 6%,transparent)}
html[data-theme="noche"] .rpg-action{border-color:rgba(185,198,221,.1)}
/* ═══ Modales ═══ */
.rpg-modal{display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.5);backdrop-filter:blur(6px);align-items:center;justify-content:center}
.rpg-modal.open{display:flex}
.rpg-modal-inner{background:var(--ope-card);border:1px solid var(--ope-line);border-radius:16px;padding:28px;max-width:500px;width:92%;max-height:88vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
html[data-theme="noche"] .rpg-modal-inner{background:rgba(14,23,48,.95)}
.rpg-modal h3{font-family:var(--ope-disp);font-weight:700;font-size:1.2rem;color:var(--ope-ink);margin:0 0 16px;display:flex;align-items:center;gap:8px}
/* Formularios RPG */
.rpg-form-group{margin-bottom:14px}
.rpg-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:500px){.rpg-form-row{grid-template-columns:1fr}}
.rpg-form-label{display:block;font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--ope-ink-dim);margin-bottom:5px}
.rpg-form-input,.rpg-form-select,.rpg-form-textarea{width:100%;font-family:var(--body);font-size:.88rem;padding:9px 12px;border:1px solid var(--ope-line);border-radius:8px;background:var(--ope-card-2);color:var(--ope-ink);transition:border-color .15s,box-shadow .15s}
.rpg-form-input:focus,.rpg-form-select:focus,.rpg-form-textarea:focus{outline:none;border-color:var(--ope-gold);box-shadow:0 0 0 3px color-mix(in srgb,var(--ope-gold) 12%,transparent)}
.rpg-form-textarea{resize:vertical;min-height:60px}
.rpg-form-select{cursor:pointer}
.rpg-form-hint{font-family:var(--mono);font-size:.54rem;color:var(--ope-ink-dim);margin-top:3px}
html[data-theme="noche"] .rpg-form-input,html[data-theme="noche"] .rpg-form-select,html[data-theme="noche"] .rpg-form-textarea{background:rgba(185,198,221,.05);border-color:rgba(185,198,221,.1)}
.rpg-form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid var(--ope-line-soft)}
/* Confirm delete */
.rpg-confirm-msg{font-size:.9rem;color:var(--ope-ink-soft);margin-bottom:16px;line-height:1.5}
.rpg-confirm-msg b{color:var(--crack)}
/* Empty state */
.rpg-empty{text-align:center;padding:48px 20px}
.rpg-empty svg{opacity:.3;margin-bottom:12px}
.rpg-empty h3{font-family:var(--ope-disp);font-weight:700;font-size:1.1rem;color:var(--ope-ink);margin:0 0 6px}
.rpg-empty p{font-size:.86rem;color:var(--ope-ink-soft);max-width:36ch;margin:0 auto;line-height:1.5}
/* Demo badge */
.rpg-demo-badge{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:6px 14px;border-radius:8px;background:color-mix(in srgb,var(--ope-gold) 12%,transparent);color:var(--ope-gold-deep);border:1px solid color-mix(in srgb,var(--ope-gold) 25%,var(--ope-line));margin-bottom:18px}
.rpg-btn-sm{font-size:.78rem!important}
.rpg-th-c,.rpg-td-c{text-align:center!important}
.rpg-th-r,.rpg-td-r{text-align:right!important}
.ope-btn-danger{background:var(--crack);color:#fff;border-color:var(--crack)}
@media(max-width:600px){
  .rpg-dash{grid-template-columns:repeat(2,1fr)}
  .rpg-table{font-size:.78rem}
  .rpg-table th,.rpg-table td{padding:8px}
}
</style>
</head>
<body class="ope-pg-tienda-personal">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/gestion.php">Gestión</a><span class="sep">›</span><b>Tienda Personal</b>
</div></div>
<div class="wrap">
<?php if ($flash_msg): ?>
  <div class="ope-toasts tp-flash"><div class="ope-toast ope-toast-<?php echo $flash_type; ?>"><span class="ope-toast-msg"><?php echo htmlspecialchars_uni($flash_msg); ?></span></div></div>
<?php endif; ?>

  <section class="reveal">
    <div class="shead">
      <h1>Tienda Personal</h1>
      <span class="code">// <?php echo $pj_name; ?></span>
      <span class="rule"></span>
    </div>
    <?php if ($is_demo): ?>
      <div class="rpg-demo-badge">⚠ Modo demo — datos de ejemplo</div>
    <?php endif; ?>
  </section>

  <!-- ═══ DASHBOARD STATS ═══ -->
  <div class="rpg-dash reveal">
    <div class="rpg-stat rpg-stat--gold">
      <span class="rpg-stat-val"><?php echo $berries_str; ?></span>
      <span class="rpg-stat-lbl">Berries</span>
    </div>
    <div class="rpg-stat rpg-stat--blue">
      <span class="rpg-stat-val"><?php echo $total_tipos; ?></span>
      <span class="rpg-stat-lbl">Tipos de objeto</span>
    </div>
    <div class="rpg-stat rpg-stat--green">
      <span class="rpg-stat-val"><?php echo $total_unidades; ?></span>
      <span class="rpg-stat-lbl">Unidades</span>
    </div>
    <div class="rpg-stat rpg-stat--green">
      <span class="rpg-stat-val"><?php echo $en_venta; ?></span>
      <span class="rpg-stat-lbl">En venta</span>
    </div>
    <div class="rpg-stat rpg-stat--purple">
      <span class="rpg-stat-val"><?php echo $raras; ?></span>
      <span class="rpg-stat-lbl">Raras</span>
    </div>
    <div class="rpg-stat rpg-stat--red">
      <span class="rpg-stat-val"><?php echo $legendarias; ?></span>
      <span class="rpg-stat-lbl">Legendarias</span>
    </div>
  </div>

  <!-- ═══ TOOLBAR ═══ -->
  <div class="rpg-toolbar reveal">
    <div class="rpg-toolbar-filters">
      <button class="rpg-filter active" data-filter="all">Todo</button>
      <button class="rpg-filter" data-filter="arma">⚔ Armas</button>
      <button class="rpg-filter" data-filter="equipo">🛡 Equipo</button>
      <button class="rpg-filter" data-filter="consumible">🍖 Consumibles</button>
      <button class="rpg-filter" data-filter="especial">✦ Especiales</button>
    </div>
    <div class="rpg-toolbar-actions">
      <button class="ope-btn ope-btn-hot ope-btn-sm rpg-btn-sm" id="btn-add-item">+ Agregar objeto</button>
    </div>
  </div>

  <!-- ═══ TABLA INVENTARIO ═══ -->
  <?php if (empty($objetos)): ?>
    <div class="rpg-empty reveal">
      <svg viewBox="0 0 24 24" width="48" height="48"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" fill="none" stroke="currentColor" stroke-width="1.5"/><line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="1.5"/></svg>
      <h3>Inventario vacío</h3>
      <p>Consigue equipamiento mediante trámites, intercambios o compras en las tiendas.</p>
    </div>
  <?php else: ?>
    <div class="rpg-table-wrap reveal">
      <table class="rpg-table" id="rpg-table">
        <thead>
          <tr>
            <th>Objeto</th>
            <th>Categoría</th>
            <th>Rareza</th>
            <th class="rpg-th-c">Cant.</th>
            <th>Precio</th>
            <th class="rpg-th-c">Venta</th>
            <th class="rpg-th-r">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($objetos as $o):
            $iid    = (int) ($o['iid'] ?? 0);
            $cat    = $o['categoria'] ?? 'otro';
            $rz     = $o['rareza'] ?? 'comun';
            $cant   = (int) ($o['cantidad'] ?? 1);
            $precio = (int) ($o['precio_venta'] ?? 0);
            $sale   = !empty($o['en_venta']) && $precio > 0;
            $row_cls = $sale ? ' class="on-sale"' : '';
            $data_cat = ' data-cat="' . htmlspecialchars_uni($cat) . '"';
            $data_iid = ' data-iid="' . $iid . '"';
            $data_json = htmlspecialchars_uni(json_encode(array(
                'iid'=>$iid,'nombre'=>$o['nombre']??'','categoria'=>$cat,'rareza'=>$rz,
                'cantidad'=>$cant,'precio_venta'=>$precio,'descripcion'=>$o['descripcion']??'',
            ), JSON_UNESCAPED_UNICODE));
          ?>
          <tr<?php echo $row_cls . $data_cat . $data_iid; ?>>
            <td>
              <div class="rpg-obj-name"><?php echo htmlspecialchars_uni($o['nombre'] ?? ''); ?></div>
              <?php if (!empty($o['descripcion'])): ?>
                <div class="rpg-obj-desc" title="<?php echo htmlspecialchars_uni($o['descripcion']); ?>"><?php echo htmlspecialchars_uni($o['descripcion']); ?></div>
              <?php endif; ?>
            </td>
            <td><span class="rpg-badge rpg-badge--<?php echo htmlspecialchars_uni($cat); ?>"><?php echo htmlspecialchars_uni($cat); ?></span></td>
            <td><span class="rpg-rare rpg-rare--<?php echo htmlspecialchars_uni($rz); ?>"><?php echo htmlspecialchars_uni(str_replace('_',' ',$rz)); ?></span></td>
            <td class="rpg-qty"><?php echo $cant; ?></td>
            <td><span class="rpg-price <?php echo $precio > 0 ? 'rpg-price--has' : 'rpg-price--zero'; ?>"><?php echo $precio > 0 ? number_format($precio,0,',','.') . ' B' : '—'; ?></span></td>
            <td class="rpg-td-c">
              <span class="rpg-sale-badge <?php echo $sale ? 'rpg-sale-badge--on' : 'rpg-sale-badge--off'; ?>"
                    data-action="toggle" data-iid="<?php echo $iid; ?>" title="Cambiar estado de venta">
                <?php echo $sale ? '✓ En venta' : '○ Privado'; ?>
              </span>
            </td>
            <td class="rpg-td-r">
              <div class="rpg-actions">
                <button class="rpg-action" data-action="edit" data-json='<?php echo $data_json; ?>' title="Editar">✎</button>
                <button class="rpg-action rpg-action--del" data-action="delete" data-iid="<?php echo $iid; ?>" data-name="<?php echo htmlspecialchars_uni($o['nombre'] ?? ''); ?>" title="Eliminar">✕</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- ═══════ MODAL: AGREGAR ═══════ -->
<div class="rpg-modal" id="modal-add">
  <div class="rpg-modal-inner">
    <h3>⚔ Agregar objeto</h3>
    <form method="post" id="form-add">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="add">
      <div class="rpg-form-group">
        <label class="rpg-form-label">Nombre del objeto</label>
        <input type="text" name="nombre" class="rpg-form-input" required placeholder="Ej: Espada de acero..." maxlength="160">
      </div>
      <div class="rpg-form-row">
        <div class="rpg-form-group">
          <label class="rpg-form-label">Categoría</label>
          <select name="categoria" class="rpg-form-select">
            <option value="arma">⚔ Arma</option>
            <option value="equipo">🛡 Equipo</option>
            <option value="consumible">🍖 Consumible</option>
            <option value="especial">✦ Especial</option>
          </select>
        </div>
        <div class="rpg-form-group">
          <label class="rpg-form-label">Rareza</label>
          <select name="rareza" class="rpg-form-select">
            <option value="comun">Común</option>
            <option value="poco_comun">Poco común</option>
            <option value="rara">Rara</option>
            <option value="epica">Épica</option>
            <option value="legendaria">Legendaria</option>
          </select>
        </div>
      </div>
      <div class="rpg-form-row">
        <div class="rpg-form-group">
          <label class="rpg-form-label">Cantidad</label>
          <input type="number" name="cantidad" class="rpg-form-input" value="1" min="1" max="9999">
        </div>
        <div class="rpg-form-group">
          <label class="rpg-form-label">Precio de venta (berries)</label>
          <input type="number" name="precio_venta" class="rpg-form-input" value="0" min="0">
          <div class="rpg-form-hint">0 = no está a la venta</div>
        </div>
      </div>
      <div class="rpg-form-group">
        <label class="rpg-form-label">Descripción</label>
        <textarea name="descripcion" class="rpg-form-textarea" rows="3" placeholder="Historia, origen, propiedades del objeto..."></textarea>
      </div>
      <div class="rpg-form-actions">
        <button type="button" class="ope-btn ope-btn-ghost" onclick="closeModals()">Cancelar</button>
        <button type="submit" class="ope-btn ope-btn-hot">Agregar al inventario</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════ MODAL: EDITAR ═══════ -->
<div class="rpg-modal" id="modal-edit">
  <div class="rpg-modal-inner">
    <h3>✎ Editar objeto</h3>
    <form method="post" id="form-edit">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="item_id" id="edit-iid">
      <div class="rpg-form-group">
        <label class="rpg-form-label">Nombre del objeto</label>
        <input type="text" name="nombre" id="edit-nombre" class="rpg-form-input" required maxlength="160">
      </div>
      <div class="rpg-form-row">
        <div class="rpg-form-group">
          <label class="rpg-form-label">Categoría</label>
          <select name="categoria" id="edit-categoria" class="rpg-form-select">
            <option value="arma">⚔ Arma</option>
            <option value="equipo">🛡 Equipo</option>
            <option value="consumible">🍖 Consumible</option>
            <option value="especial">✦ Especial</option>
          </select>
        </div>
        <div class="rpg-form-group">
          <label class="rpg-form-label">Rareza</label>
          <select name="rareza" id="edit-rareza" class="rpg-form-select">
            <option value="comun">Común</option>
            <option value="poco_comun">Poco común</option>
            <option value="rara">Rara</option>
            <option value="epica">Épica</option>
            <option value="legendaria">Legendaria</option>
          </select>
        </div>
      </div>
      <div class="rpg-form-row">
        <div class="rpg-form-group">
          <label class="rpg-form-label">Cantidad</label>
          <input type="number" name="cantidad" id="edit-cantidad" class="rpg-form-input" min="1" max="9999">
        </div>
        <div class="rpg-form-group">
          <label class="rpg-form-label">Precio de venta (berries)</label>
          <input type="number" name="precio_venta" id="edit-precio" class="rpg-form-input" min="0">
          <div class="rpg-form-hint">0 = no está a la venta</div>
        </div>
      </div>
      <div class="rpg-form-group">
        <label class="rpg-form-label">Descripción</label>
        <textarea name="descripcion" id="edit-desc" class="rpg-form-textarea" rows="3"></textarea>
      </div>
      <div class="rpg-form-actions">
        <button type="button" class="ope-btn ope-btn-ghost" onclick="closeModals()">Cancelar</button>
        <button type="submit" class="ope-btn ope-btn-hot">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════ MODAL: ELIMINAR ═══════ -->
<div class="rpg-modal" id="modal-delete">
  <div class="rpg-modal-inner">
    <h3>✕ Eliminar objeto</h3>
    <p class="rpg-confirm-msg">¿Seguro que quieres eliminar <b id="del-name"></b> de tu inventario? Esta acción no se puede deshacer.</p>
    <form method="post" id="form-delete">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="item_id" id="del-iid">
      <div class="rpg-form-actions">
        <button type="button" class="ope-btn ope-btn-ghost" onclick="closeModals()">Cancelar</button>
        <button type="submit" class="ope-btn ope-btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════ MODAL: TOGGLE VENTA ═══════ -->
<div class="rpg-modal" id="modal-toggle">
  <div class="rpg-modal-inner">
    <h3 id="toggle-title">Venta</h3>
    <p class="rpg-confirm-msg" id="toggle-msg"></p>
    <form method="post" id="form-toggle">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="toggle_venta">
      <input type="hidden" name="item_id" id="toggle-iid">
      <div class="rpg-form-actions">
        <button type="button" class="ope-btn ope-btn-ghost" onclick="closeModals()">Cancelar</button>
        <button type="submit" class="ope-btn ope-btn-hot" id="toggle-btn">Confirmar</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
/* ── Modales ── */
function closeModals(){ document.querySelectorAll('.rpg-modal').forEach(function(m){m.classList.remove('open');}); }
document.querySelectorAll('.rpg-modal').forEach(function(m){
  m.addEventListener('click',function(e){if(e.target===m) closeModals();});
});
document.addEventListener('keydown',function(e){if(e.key==='Escape') closeModals();});

/* ── Agregar ── */
document.getElementById('btn-add-item').addEventListener('click',function(){
  document.getElementById('modal-add').classList.add('open');
});

/* ── Editar ── */
document.querySelectorAll('[data-action="edit"]').forEach(function(btn){
  btn.addEventListener('click',function(){
    var d=JSON.parse(this.getAttribute('data-json'));
    document.getElementById('edit-iid').value=d.iid;
    document.getElementById('edit-nombre').value=d.nombre;
    document.getElementById('edit-categoria').value=d.categoria;
    document.getElementById('edit-rareza').value=d.rareza;
    document.getElementById('edit-cantidad').value=d.cantidad;
    document.getElementById('edit-precio').value=d.precio_venta;
    document.getElementById('edit-desc').value=d.descripcion||'';
    document.getElementById('modal-edit').classList.add('open');
  });
});

/* ── Eliminar ── */
document.querySelectorAll('[data-action="delete"]').forEach(function(btn){
  btn.addEventListener('click',function(){
    document.getElementById('del-iid').value=this.getAttribute('data-iid');
    document.getElementById('del-name').textContent=this.getAttribute('data-name');
    document.getElementById('modal-delete').classList.add('open');
  });
});

/* ── Toggle venta ── */
document.querySelectorAll('[data-action="toggle"]').forEach(function(el){
  el.addEventListener('click',function(){
    var iid=this.getAttribute('data-iid');
    var isOn=this.classList.contains('rpg-sale-badge--on');
    document.getElementById('toggle-iid').value=iid;
    document.getElementById('toggle-title').textContent=isOn?'Retirar de la venta':'Poner a la venta';
    document.getElementById('toggle-msg').innerHTML=isOn
      ? '¿Quieres retirar este objeto de la venta? No será visible para otros jugadores.'
      : '¿Poner este objeto a la venta? Aparecerá en tu tienda pública.';
    document.getElementById('toggle-btn').textContent=isOn?'Retirar':'Poner a la venta';
    document.getElementById('modal-toggle').classList.add('open');
  });
});

/* ── Filtros ── */
document.querySelectorAll('.rpg-filter').forEach(function(btn){
  btn.addEventListener('click',function(){
    document.querySelectorAll('.rpg-filter').forEach(function(b){b.classList.remove('active');});
    this.classList.add('active');
    var f=this.getAttribute('data-filter');
    document.querySelectorAll('#rpg-table tbody tr').forEach(function(row){
      if(f==='all'||row.getAttribute('data-cat')===f){row.style.display='';}else{row.style.display='none';}
    });
  });
});

/* ── Reveal ── */
if('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches){
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vis');io.unobserve(e.target);}});},{threshold:.08});
  document.querySelectorAll('.reveal').forEach(function(el){io.observe(el);});
} else { document.querySelectorAll('.reveal').forEach(function(el){el.classList.add('vis');}); }
</script>
</body>
</html>
