<?php
/**
 * I-Forge · Panel de Mundo Vivo (Zona Staff · Web Master)
 * Recopila eventos/misiones/tablero/NPCs, genera el super-prompt para la IA,
 * ingiere el resultado estructurado, muestra vista previa y publica.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mundo-vivo.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);

$staff = $loggedin ? ope_rol_active_staff($uid) : array('rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank  = (int)$staff['rank'];
$is_webmaster = ($rank >= 4);

$flash = ''; $flash_kind = 'ok';
$preview = null;   // resultado parseado a previsualizar

$ciclo = ope_rol_mv_ciclo_actual();
$ciclo_id = is_array($ciclo) ? (int)$ciclo['ciclo_id'] : 0;

// ── POST ──
if ($is_webmaster && $mybb->request_method === 'post' && $ciclo_id > 0) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga e inténtalo de nuevo.'; $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('mv_action');

        if ($action === 'save_indicaciones') {
            $db->update_query('rol_mv_ciclos', array('indicaciones' => $db->escape_string(trim($mybb->get_input('indicaciones')))), 'ciclo_id = ' . $ciclo_id);
            $flash = 'Indicaciones guardadas.';

        } elseif ($action === 'evento_estado') {
            $eid = (int)$mybb->get_input('evento_id', MyBB::INPUT_INT);
            $est = $mybb->get_input('estado');
            if (in_array($est, array('pendiente', 'incluido', 'descartado'), true) && $eid > 0) {
                $db->update_query('rol_mv_eventos', array('estado' => $db->escape_string($est)), 'evento_id = ' . $eid);
                $flash = 'Evento actualizado.';
            }

        } elseif ($action === 'evento_delete') {
            $eid = (int)$mybb->get_input('evento_id', MyBB::INPUT_INT);
            if ($eid > 0) { $db->delete_query('rol_mv_eventos', 'evento_id = ' . $eid); $flash = 'Evento eliminado.'; }

        } elseif ($action === 'mision_estado') {
            $mid = (int)$mybb->get_input('mision_id', MyBB::INPUT_INT);
            $est = $mybb->get_input('estado');
            if (in_array($est, array('en_curso', 'completada', 'fallida'), true) && $mid > 0) {
                $db->update_query('rol_mv_misiones', array('estado' => $db->escape_string($est)), 'mision_id = ' . $mid);
                $flash = 'Misión actualizada.';
            }

        } elseif ($action === 'mision_delete') {
            $mid = (int)$mybb->get_input('mision_id', MyBB::INPUT_INT);
            if ($mid > 0) { $db->delete_query('rol_mv_misiones', 'mision_id = ' . $mid); $flash = 'Misión eliminada.'; }

        } elseif ($action === 'tablero_save') {
            $zKeys = array_keys(ope_rol_mv_zona_metrics());
            $fKeys = array_keys(ope_rol_mv_faccion_metrics());
            // Zonas
            $zin = $mybb->get_input('zona', MyBB::INPUT_ARRAY);
            if (is_array($zin)) {
                foreach ($zin as $slug => $vals) {
                    $upd = array();
                    foreach ($zKeys as $k) { if (isset($vals[$k])) $upd[$k] = max(0, min(100, (int)$vals[$k])); }
                    if (isset($vals['notas'])) $upd['notas'] = $db->escape_string((string)$vals['notas']);
                    if (!empty($upd)) $db->update_query('rol_mv_zonas', $upd, "slug = '" . $db->escape_string((string)$slug) . "'");
                }
            }
            // Facciones
            $fin = $mybb->get_input('fac', MyBB::INPUT_ARRAY);
            if (is_array($fin)) {
                foreach ($fin as $slug => $vals) {
                    $upd = array();
                    foreach ($fKeys as $k) {
                        if (!isset($vals[$k])) continue;
                        $upd[$k] = ($k === 'rep') ? max(-100, min(100, (int)$vals[$k])) : max(0, min(100, (int)$vals[$k]));
                    }
                    if (isset($vals['notas'])) $upd['notas'] = $db->escape_string((string)$vals['notas']);
                    if (!empty($upd)) $db->update_query('rol_mv_facciones', $upd, "slug = '" . $db->escape_string((string)$slug) . "'");
                }
            }
            // Tensión por mar: ten[zona][par][valor|notas]
            $tin = $mybb->get_input('ten', MyBB::INPUT_ARRAY);
            if (is_array($tin)) {
                foreach ($tin as $zslug => $pares) {
                    if (!is_array($pares)) continue;
                    foreach ($pares as $par => $vals) {
                        $upd = array();
                        if (isset($vals['valor'])) $upd['valor'] = max(0, min(100, (int)$vals['valor']));
                        if (isset($vals['notas'])) $upd['notas'] = $db->escape_string((string)$vals['notas']);
                        if (!empty($upd)) $db->update_query('rol_mv_tension', $upd, "zona_slug = '" . $db->escape_string((string)$zslug) . "' AND par = '" . $db->escape_string((string)$par) . "'");
                    }
                }
            }
            $flash = 'Tablero guardado.';

        } elseif ($action === 'arco_add') {
            $nombre = trim($mybb->get_input('nombre'));
            if ($nombre !== '') {
                $db->insert_query('rol_mv_arcos', array(
                    'nombre'      => $db->escape_string($nombre),
                    'estado'      => $db->escape_string($mybb->get_input('estado') ?: 'Activo'),
                    'zonas'       => $db->escape_string(trim($mybb->get_input('zonas'))),
                    'facciones'   => $db->escape_string(trim($mybb->get_input('facciones'))),
                    'descripcion' => $db->escape_string(trim($mybb->get_input('descripcion'))),
                    'dateline'    => (int)TIME_NOW,
                ));
                $flash = 'Arco añadido.';
            }

        } elseif ($action === 'arco_delete') {
            $aid = (int)$mybb->get_input('arco_id', MyBB::INPUT_INT);
            if ($aid > 0) { $db->delete_query('rol_mv_arcos', 'arco_id = ' . $aid); $flash = 'Arco eliminado.'; }

        } elseif ($action === 'npc_ubic') {
            $pid = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
            if ($pid > 0) {
                $db->update_query('rol_personajes', array(
                    'mundo_zona'      => $db->escape_string($mybb->get_input('mundo_zona')),
                    'mundo_ubic'      => $db->escape_string(trim($mybb->get_input('mundo_ubic'))),
                    'mundo_accion'    => $db->escape_string(trim($mybb->get_input('mundo_accion'))),
                    'mundo_estado_np' => $db->escape_string(trim($mybb->get_input('mundo_estado_np'))),
                ), 'pid = ' . $pid);
                $flash = 'Ubicación del NPC actualizada.';
            }

        } elseif ($action === 'npc_menor_add') {
            $nombre = trim($mybb->get_input('nombre'));
            if ($nombre !== '') {
                $db->insert_query('rol_mv_npc_menores', array(
                    'ciclo_id'    => $ciclo_id,
                    'nombre'      => $db->escape_string($nombre),
                    'descripcion' => $db->escape_string(trim($mybb->get_input('descripcion'))),
                    'zona_slug'   => $db->escape_string($mybb->get_input('zona_slug')),
                    'estado'      => $db->escape_string(trim($mybb->get_input('estado'))),
                    'dateline'    => (int)TIME_NOW,
                ));
                $flash = 'NPC menor registrado.';
            }

        } elseif ($action === 'npc_menor_delete') {
            $id = (int)$mybb->get_input('id', MyBB::INPUT_INT);
            if ($id > 0) { $db->delete_query('rol_mv_npc_menores', 'id = ' . $id); $flash = 'NPC menor eliminado.'; }

        } elseif ($action === 'generar_prompt') {
            $ciclo = ope_rol_mv_ciclo_by_id($ciclo_id);
            $prompt = ope_rol_mv_build_prompt($ciclo);
            $db->update_query('rol_mv_ciclos', array('prompt' => $db->escape_string($prompt), 'estado' => 'prompt'), 'ciclo_id = ' . $ciclo_id);
            $flash = 'Prompt generado. Cópialo y pégalo en tu IA.';

        } elseif ($action === 'ingerir') {
            $raw = (string)$mybb->get_input('resultado');
            $preview = ope_rol_mv_parse_resultado($raw);
            $db->update_query('rol_mv_ciclos', array('resultado_raw' => $db->escape_string($raw), 'estado' => 'preview'), 'ciclo_id = ' . $ciclo_id);
            if (!empty($preview['errores'])) {
                $flash = 'El resultado tiene problemas: ' . implode(' ', $preview['errores']); $flash_kind = 'warn';
            } else {
                $flash = 'Resultado interpretado. Revisa la vista previa y pulsa Publicar.';
            }

        } elseif ($action === 'publicar') {
            $ciclo = ope_rol_mv_ciclo_by_id($ciclo_id);
            $raw = (string)$ciclo['resultado_raw'];
            $parsed = ope_rol_mv_parse_resultado($raw);
            // Links de imagen pegados por el staff: map id => url. Se inyectan en el
            // periódico/noticia dentro de ope_rol_mv_publicar (evita subir al backend).
            $imgUrls = $mybb->get_input('img_url', MyBB::INPUT_ARRAY);
            if (!is_array($imgUrls)) { $imgUrls = array(); }
            $r = ope_rol_mv_publicar($ciclo_id, $parsed, $raw, $imgUrls);
            if (!empty($r['ok'])) {
                $flash = 'Publicado: el nuevo estado del mundo, el periódico Eternal News y la noticia ya están en línea.';
            } else {
                $flash = 'No se pudo publicar: ' . ($r['error'] ?? 'error desconocido'); $flash_kind = 'warn';
            }
        }
    }
    // Refrescar ciclo tras cambios
    $ciclo = ope_rol_mv_ciclo_by_id($ciclo_id);
}

// ── Datos para render ──
$zonas       = ope_rol_mv_zonas();
$facciones   = ope_rol_mv_facciones();
$tension     = ope_rol_mv_tension();          // anidado por zona
$zMetricsDef = ope_rol_mv_zona_metrics();
$fMetricsDef = ope_rol_mv_faccion_metrics();
$arcos       = ope_rol_mv_arcos();
$eventos   = $ciclo_id ? ope_rol_mv_eventos($ciclo_id) : array();
$misiones  = $ciclo_id ? ope_rol_mv_misiones($ciclo_id) : array();
$npcs      = ope_rol_mv_npc_mayores();
$menores   = $ciclo_id ? ope_rol_mv_npc_menores($ciclo_id) : array();
$periodicos = ope_rol_mv_periodicos(60);
$pk = htmlspecialchars_uni($mybb->post_code);
$mes_label = is_array($ciclo) ? htmlspecialchars_uni($ciclo['periodo']) : '';

function mv_zona_options($zonas, $sel = '') {
    $o = '<option value="">— zona —</option>';
    foreach ($zonas as $z) {
        $s = ($z['slug'] === $sel) ? ' selected' : '';
        $o .= '<option value="' . htmlspecialchars_uni($z['slug']) . '"' . $s . '>' . htmlspecialchars_uni($z['nombre']) . '</option>';
    }
    return $o;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Mundo Vivo</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-mundo-vivo) -->
</head>
<body class="ope-pg-mundo-vivo">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Mundo Vivo</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Mundo Vivo</h1>
      <span class="code">// la balanza &middot; mes <?php echo $mes_label; ?></span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$is_webmaster): ?>
  <section class="reveal">
    <div class="plate"><div class="plate-b">
      <div class="noperm"><div class="big">Zona reservada a Web Master</div>
      <p>El panel de Mundo Vivo solo está disponible para personajes con rol <b>Web Master</b>.</p>
      <a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost">Volver a Zona Staff</a></div>
    </div></div>
  </section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="mv-flash mv-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="mv-tabs" id="mvTabs">
      <button class="mv-tab on" data-tab="eventos">Eventos <span class="mv-pill"><?php echo count($eventos); ?></span></button>
      <button class="mv-tab" data-tab="misiones">Misiones <span class="mv-pill"><?php echo count($misiones); ?></span></button>
      <button class="mv-tab" data-tab="tablero">Tablero</button>
      <button class="mv-tab" data-tab="npcs">NPCs</button>
      <button class="mv-tab" data-tab="historico">Histórico</button>
      <button class="mv-tab" data-tab="generar">Generar / Publicar</button>
    </div>
  </section>

  <!-- ===== EVENTOS ===== -->
  <section class="mv-panel reveal" id="tab-eventos">
    <div class="plate">
      <div class="plate-h"><span class="t">Eventos notificados</span><span class="c">// temas del mes</span></div>
      <div class="plate-b">
<?php if (empty($eventos)): ?>
        <p class="mv-empty">No hay eventos notificados este mes.</p>
<?php else: foreach ($eventos as $e): ?>
        <div class="mv-row mv-ev mv-ev-<?php echo htmlspecialchars_uni($e['estado']); ?>">
          <div class="mv-ev-main">
            <a class="mv-ev-t" href="<?php echo htmlspecialchars_uni($e['enlace']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars_uni($e['titulo']); ?></a>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($e['zona_slug']); ?> &middot; estado: <b><?php echo htmlspecialchars_uni($e['estado']); ?></b></span>
            <p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$e['resumen'])); ?></p>
          </div>
          <div class="mv-ev-acts">
            <form method="post"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="evento_estado"><input type="hidden" name="evento_id" value="<?php echo (int)$e['evento_id']; ?>"><input type="hidden" name="estado" value="incluido"><button class="btn btn-sm">Incluir</button></form>
            <form method="post"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="evento_estado"><input type="hidden" name="evento_id" value="<?php echo (int)$e['evento_id']; ?>"><input type="hidden" name="estado" value="descartado"><button class="btn btn-sm btn-ghost">Descartar</button></form>
            <form method="post" onsubmit="return confirm('¿Eliminar este evento?');"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="evento_delete"><input type="hidden" name="evento_id" value="<?php echo (int)$e['evento_id']; ?>"><button class="btn btn-sm btn-danger">×</button></form>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== MISIONES ===== -->
  <section class="mv-panel reveal" id="tab-misiones" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Misiones del mes</span><span class="c">// en curso · completadas · fallidas</span></div>
      <div class="plate-b">
<?php if (empty($misiones)): ?>
        <p class="mv-empty">No ha llegado ninguna misión este mes.</p>
<?php else: foreach ($misiones as $m): ?>
        <div class="mv-row mv-mis mv-mis-<?php echo htmlspecialchars_uni($m['estado']); ?>">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($m['titulo']); ?></span>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($m['zona_slug']); ?> &middot; <b><?php echo htmlspecialchars_uni(str_replace('_', ' ', $m['estado'])); ?></b></span>
            <?php if (trim((string)$m['resumen']) !== ''): ?><p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$m['resumen'])); ?></p><?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <form method="post" class="mv-inline"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="mision_estado"><input type="hidden" name="mision_id" value="<?php echo (int)$m['mision_id']; ?>">
              <select name="estado" class="mv-input mv-input-sm" onchange="this.form.submit()">
                <option value="en_curso"<?php echo $m['estado']==='en_curso'?' selected':''; ?>>En curso</option>
                <option value="completada"<?php echo $m['estado']==='completada'?' selected':''; ?>>Completada</option>
                <option value="fallida"<?php echo $m['estado']==='fallida'?' selected':''; ?>>Fallida</option>
              </select>
            </form>
            <form method="post" onsubmit="return confirm('¿Eliminar misión?');"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="mision_delete"><input type="hidden" name="mision_id" value="<?php echo (int)$m['mision_id']; ?>"><button class="btn btn-sm btn-danger">×</button></form>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== TABLERO ===== -->
  <section class="mv-panel reveal" id="tab-tablero" hidden>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="tablero_save">
      <div class="plate">
        <div class="plate-h"><span class="t">Zonas (los mares)</span><span class="c">// métricas 0-100 + notas</span></div>
        <div class="plate-b">
<?php foreach ($zonas as $z): $zt = isset($tension[$z['slug']]) ? $tension[$z['slug']] : array(); ?>
          <div class="mv-zona">
            <div class="mv-zona-h"><b><?php echo htmlspecialchars_uni($z['nombre']); ?></b> <code><?php echo htmlspecialchars_uni($z['slug']); ?></code></div>
            <div class="mv-zona-vals">
<?php foreach ($zMetricsDef as $k => $m): ?>
              <label title="<?php echo htmlspecialchars_uni($m['label']); ?>"><?php echo strtoupper($k); ?> <input type="number" min="0" max="100" name="zona[<?php echo htmlspecialchars_uni($z['slug']); ?>][<?php echo $k; ?>]" value="<?php echo (int)($z[$k] ?? 0); ?>"></label>
<?php endforeach; ?>
            </div>
            <textarea name="zona[<?php echo htmlspecialchars_uni($z['slug']); ?>][notas]" class="mv-input" rows="2" placeholder="Notas del mar (islas, sucesos concretos...)"><?php echo htmlspecialchars_uni((string)$z['notas']); ?></textarea>
            <details class="mv-tenblock">
              <summary>Tensiones entre facciones en <?php echo htmlspecialchars_uni($z['nombre']); ?></summary>
<?php foreach ($zt as $par => $info):
                $na = isset($facciones[$info['a']]) ? $facciones[$info['a']]['nombre'] : $info['a'];
                $nb = isset($facciones[$info['b']]) ? $facciones[$info['b']]['nombre'] : $info['b'];
?>
              <div class="mv-tenrow">
                <label class="mv-tenrow-v"><span><?php echo htmlspecialchars_uni($na . ' vs ' . $nb); ?></span><input type="number" min="0" max="100" name="ten[<?php echo htmlspecialchars_uni($z['slug']); ?>][<?php echo htmlspecialchars_uni($par); ?>][valor]" value="<?php echo (int)$info['valor']; ?>"></label>
                <input type="text" class="mv-input mv-tenrow-n" name="ten[<?php echo htmlspecialchars_uni($z['slug']); ?>][<?php echo htmlspecialchars_uni($par); ?>][notas]" value="<?php echo htmlspecialchars_uni((string)$info['notas']); ?>" placeholder="por qué esta tensión en este mar">
              </div>
<?php endforeach; ?>
            </details>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="plate">
        <div class="plate-h"><span class="t">Facciones</span><span class="c">// REP -100..100 · resto 0-100 + notas</span></div>
        <div class="plate-b">
<?php foreach ($facciones as $f): ?>
          <div class="mv-zona">
            <div class="mv-zona-h"><b><?php echo htmlspecialchars_uni($f['nombre']); ?></b> <code><?php echo htmlspecialchars_uni($f['slug']); ?></code></div>
            <div class="mv-zona-vals">
<?php foreach ($fMetricsDef as $k => $m): $isRep = (!empty($m['special']) && $m['special'] === 'rep'); ?>
              <label title="<?php echo htmlspecialchars_uni($m['label']); ?>"><?php echo strtoupper($k); ?> <input type="number" min="<?php echo $isRep ? -100 : 0; ?>" max="100" name="fac[<?php echo htmlspecialchars_uni($f['slug']); ?>][<?php echo $k; ?>]" value="<?php echo (int)($f[$k] ?? 0); ?>"></label>
<?php endforeach; ?>
            </div>
            <textarea name="fac[<?php echo htmlspecialchars_uni($f['slug']); ?>][notas]" class="mv-input" rows="2" placeholder="Notas de la facción"><?php echo htmlspecialchars_uni((string)$f['notas']); ?></textarea>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="mv-save-bar"><button class="btn btn-primary">Guardar tablero</button></div>
    </form>

    <div class="plate">
      <div class="plate-h"><span class="t">Arcos abiertos</span><span class="c">// tramas mayores</span></div>
      <div class="plate-b">
        <form method="post" class="mv-addform">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="arco_add">
          <input type="text" name="nombre" placeholder="Nombre del arco" class="mv-input" required>
          <input type="text" name="estado" placeholder="Estado (Activo/Latente...)" class="mv-input">
          <input type="text" name="zonas" placeholder="Zonas" class="mv-input">
          <input type="text" name="facciones" placeholder="Facciones" class="mv-input">
          <textarea name="descripcion" placeholder="Descripción" class="mv-input" rows="2"></textarea>
          <button class="btn btn-primary btn-sm">Añadir arco</button>
        </form>
<?php if (empty($arcos)): ?>
        <p class="mv-empty">No hay arcos abiertos.</p>
<?php else: foreach ($arcos as $a): ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($a['nombre']); ?> <small>[<?php echo htmlspecialchars_uni($a['estado']); ?>]</small></span>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($a['zonas']); ?> &middot; <?php echo htmlspecialchars_uni($a['facciones']); ?></span>
            <?php if (trim((string)$a['descripcion']) !== ''): ?><p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$a['descripcion'])); ?></p><?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <form method="post" onsubmit="return confirm('¿Eliminar arco?');"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="arco_delete"><input type="hidden" name="arco_id" value="<?php echo (int)$a['arco_id']; ?>"><button class="btn btn-sm btn-danger">×</button></form>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== NPCs ===== -->
  <section class="mv-panel reveal" id="tab-npcs" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">NPCs mayores</span><span class="c">// con ficha · ubicación</span></div>
      <div class="plate-b">
<?php if (empty($npcs)): ?>
        <p class="mv-empty">No hay NPCs con ficha. Créalos desde Zona Staff → Crear NPC.</p>
<?php else: foreach ($npcs as $n): ?>
        <form method="post" class="mv-npc">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="npc_ubic"><input type="hidden" name="pid" value="<?php echo (int)$n['pid']; ?>">
          <div class="mv-npc-name"><b><?php echo htmlspecialchars_uni($n['nombre']); ?></b> <small><?php echo htmlspecialchars_uni($n['faccion']); ?> · <?php echo htmlspecialchars_uni($n['rango']); ?></small></div>
          <select name="mundo_zona" class="mv-input mv-input-sm"><?php echo mv_zona_options($zonas, $n['mundo_zona']); ?></select>
          <input type="text" name="mundo_ubic" class="mv-input mv-input-sm" placeholder="Isla/lugar" value="<?php echo htmlspecialchars_uni((string)$n['mundo_ubic']); ?>">
          <input type="text" name="mundo_estado_np" class="mv-input mv-input-sm" placeholder="Estado (activo/herido...)" value="<?php echo htmlspecialchars_uni((string)$n['mundo_estado_np']); ?>">
          <input type="text" name="mundo_accion" class="mv-input" placeholder="Acción actual" value="<?php echo htmlspecialchars_uni((string)$n['mundo_accion']); ?>">
          <button class="btn btn-sm">Guardar</button>
        </form>
<?php endforeach; endif; ?>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">NPCs menores (historial)</span><span class="c">// relleno de este mes</span></div>
      <div class="plate-b">
        <form method="post" class="mv-addform">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="npc_menor_add">
          <input type="text" name="nombre" placeholder="Nombre" class="mv-input" required>
          <select name="zona_slug" class="mv-input"><?php echo mv_zona_options($zonas); ?></select>
          <input type="text" name="estado" placeholder="Estado" class="mv-input">
          <textarea name="descripcion" placeholder="Descripción" class="mv-input" rows="2"></textarea>
          <button class="btn btn-primary btn-sm">Registrar</button>
        </form>
<?php if (empty($menores)): ?>
        <p class="mv-empty">Sin NPCs menores registrados este mes.</p>
<?php else: foreach ($menores as $mn): ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($mn['nombre']); ?></span>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($mn['zona_slug']); ?> &middot; <?php echo htmlspecialchars_uni($mn['estado']); ?></span>
            <?php if (trim((string)$mn['descripcion']) !== ''): ?><p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$mn['descripcion'])); ?></p><?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <form method="post" onsubmit="return confirm('¿Eliminar?');"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="npc_menor_delete"><input type="hidden" name="id" value="<?php echo (int)$mn['id']; ?>"><button class="btn btn-sm btn-danger">×</button></form>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== HISTÓRICO ===== -->
  <section class="mv-panel reveal" id="tab-historico" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Histórico</span><span class="c">// periódicos publicados</span></div>
      <div class="plate-b">
<?php if (empty($periodicos)): ?>
        <p class="mv-empty">Aún no se ha publicado ningún periódico.</p>
<?php else: foreach ($periodicos as $p): ?>
        <a class="mv-hist-item" href="<?php echo $bburl; ?>/periodicos.php?c=<?php echo (int)$p['ciclo_id']; ?>">
          <b>Eternal News</b> — <?php echo htmlspecialchars_uni($p['periodo']); ?>
          <span><?php echo htmlspecialchars_uni($p['noticia_titulo']); ?></span>
        </a>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== GENERAR / PUBLICAR ===== -->
  <section class="mv-panel reveal" id="tab-generar" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Indicaciones del staff</span><span class="c">// se incluyen en el prompt de este mes</span></div>
      <div class="plate-b">
        <form method="post">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="save_indicaciones">
          <textarea name="indicaciones" class="mv-input" rows="5" placeholder="Qué quieres que la IA tenga en cuenta este mes..."><?php echo htmlspecialchars_uni((string)($ciclo['indicaciones'] ?? '')); ?></textarea>
          <div class="mv-save-bar"><button class="btn btn-primary btn-sm">Guardar indicaciones</button></div>
        </form>
      </div>
    </div>

    <div class="plate">
      <div class="plate-h"><span class="t">1 · Generar prompt</span><span class="c">// autocontenido para IA externa</span></div>
      <div class="plate-b">
        <form method="post"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="generar_prompt"><button class="btn btn-primary">Generar prompt</button></form>
<?php if (!empty($ciclo['prompt'])): ?>
        <div class="mv-prompt-wrap">
          <textarea id="mvPrompt" class="mv-input mv-mono" rows="12" readonly><?php echo htmlspecialchars_uni((string)$ciclo['prompt']); ?></textarea>
          <button type="button" class="btn btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('mvPrompt').value);this.textContent='Copiado'">Copiar prompt</button>
        </div>
<?php endif; ?>
      </div>
    </div>

    <div class="plate">
      <div class="plate-h"><span class="t">2 · Pegar resultado</span><span class="c">// bloques estructurados de la IA</span></div>
      <div class="plate-b">
        <form method="post">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="ingerir">
          <textarea name="resultado" class="mv-input mv-mono" rows="12" placeholder="Pega aquí el resultado completo de la IA (bloques ===ESTADO_JSON===, ===PERIODICO_HTML===, ===NOTICIA===, ===IMAGENES===)"><?php echo htmlspecialchars_uni((string)($ciclo['resultado_raw'] ?? '')); ?></textarea>
          <div class="mv-save-bar"><button class="btn btn-primary">Interpretar resultado</button></div>
        </form>
      </div>
    </div>

<?php if ($preview !== null && empty($preview['errores'])):
      // Diff entre el tablero ACTUAL (aún no publicado) y el estado nuevo de la IA.
      $tablero = ope_rol_mv_tablero();
      $diff = ope_rol_mv_diff_estado($tablero, $preview['estado']);
      $diffVacio = empty($diff['zonas']) && empty($diff['facciones']) && empty($diff['tension']);
      $arcosNuevos = (is_array($preview['estado']) && !empty($preview['estado']['arcos']) && is_array($preview['estado']['arcos'])) ? $preview['estado']['arcos'] : array();
?>
    <div class="plate">
      <div class="plate-h"><span class="t">3 · Vista previa</span><span class="c">// revisa antes de publicar</span></div>
      <div class="plate-b">
        <h3 class="mv-prev-h">Noticia de portada</h3>
        <div class="mv-prev-box"><b><?php echo htmlspecialchars_uni($preview['noticia']['titulo']); ?></b><p><?php echo htmlspecialchars_uni($preview['noticia']['resumen']); ?></p><div class="mv-prev-html"><?php echo $preview['noticia']['cuerpo']; ?></div></div>

        <h3 class="mv-prev-h">Cómo se vería el periódico</h3>
        <div class="ope-periodico mv-prev-per" style="background-image:url('<?php echo $bburl; ?>/images/mundo-vivo/paper.jpg')"><?php echo $preview['periodico']; ?></div>

        <h3 class="mv-prev-h">Cambios en el mundo</h3>
<?php if ($diffVacio): ?>
        <p class="mv-empty">El nuevo estado no modifica ninguna métrica del tablero actual.</p>
<?php else: ?>
        <div class="mv-diff">
<?php if (!empty($diff['zonas'])): ?>
          <div class="mv-diff-group">
            <h4 class="mv-diff-gt">Zonas</h4>
<?php foreach ($diff['zonas'] as $zc): ?>
            <div class="mv-diff-block">
              <div class="mv-diff-name"><?php echo htmlspecialchars_uni($zc['nombre']); ?> <code><?php echo htmlspecialchars_uni($zc['slug']); ?></code></div>
<?php foreach ($zc['cambios'] as $c): ?>
              <div class="mv-diff-line mv-diff-<?php echo $c['dir'] > 0 ? 'up' : 'down'; ?>">
                <span class="mv-diff-m"><?php echo htmlspecialchars_uni($c['label']); ?></span>
                <span class="mv-diff-v"><?php echo htmlspecialchars_uni($c['antes_lbl']); ?> (<?php echo (int)$c['antes']; ?>) <span class="mv-diff-arrow"><?php echo $c['dir'] > 0 ? '↑' : '↓'; ?></span> <?php echo htmlspecialchars_uni($c['despues_lbl']); ?> (<?php echo (int)$c['despues']; ?>)</span>
              </div>
<?php endforeach; ?>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
<?php if (!empty($diff['facciones'])): ?>
          <div class="mv-diff-group">
            <h4 class="mv-diff-gt">Facciones</h4>
<?php foreach ($diff['facciones'] as $fc): ?>
            <div class="mv-diff-block">
              <div class="mv-diff-name"><?php echo htmlspecialchars_uni($fc['nombre']); ?> <code><?php echo htmlspecialchars_uni($fc['slug']); ?></code></div>
<?php foreach ($fc['cambios'] as $c): ?>
              <div class="mv-diff-line mv-diff-<?php echo $c['dir'] > 0 ? 'up' : 'down'; ?>">
                <span class="mv-diff-m"><?php echo htmlspecialchars_uni($c['label']); ?></span>
                <span class="mv-diff-v"><?php echo htmlspecialchars_uni($c['antes_lbl']); ?> (<?php echo (int)$c['antes']; ?>) <span class="mv-diff-arrow"><?php echo $c['dir'] > 0 ? '↑' : '↓'; ?></span> <?php echo htmlspecialchars_uni($c['despues_lbl']); ?> (<?php echo (int)$c['despues']; ?>)</span>
              </div>
<?php endforeach; ?>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
<?php if (!empty($diff['tension'])): ?>
          <div class="mv-diff-group">
            <h4 class="mv-diff-gt">Tensiones por mar</h4>
<?php foreach ($diff['tension'] as $tc): ?>
            <div class="mv-diff-line mv-diff-<?php echo $tc['dir'] > 0 ? 'up' : 'down'; ?>">
              <span class="mv-diff-m"><?php echo htmlspecialchars_uni($tc['zona_nombre']); ?> · <?php echo htmlspecialchars_uni(str_replace('|', ' vs ', $tc['par'])); ?></span>
              <span class="mv-diff-v"><?php echo htmlspecialchars_uni($tc['antes_lbl']); ?> (<?php echo (int)$tc['antes']; ?>) <span class="mv-diff-arrow"><?php echo $tc['dir'] > 0 ? '↑' : '↓'; ?></span> <?php echo htmlspecialchars_uni($tc['despues_lbl']); ?> (<?php echo (int)$tc['despues']; ?>)</span>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
<?php endif; ?>

        <h3 class="mv-prev-h">Misiones que surgirán</h3>
<?php if (empty($preview['misiones'])): ?>
        <p class="mv-empty">La IA no propuso misiones en el bloque ===MISIONES===.</p>
<?php else: ?>
        <div class="mv-mislist">
<?php foreach ($preview['misiones'] as $ms): ?>
          <div class="mv-miscard mv-mis-dif-<?php echo htmlspecialchars_uni($ms['dificultad'] !== '' ? $ms['dificultad'] : 'na'); ?>">
            <div class="mv-miscard-h">
              <span class="mv-miscard-t"><?php echo htmlspecialchars_uni($ms['titulo'] !== '' ? $ms['titulo'] : '(sin título)'); ?></span>
<?php if ($ms['dificultad'] !== ''): ?><span class="mv-miscard-dif"><?php echo htmlspecialchars_uni(ucfirst($ms['dificultad'])); ?></span><?php endif; ?>
            </div>
            <div class="mv-miscard-meta">
<?php if ($ms['zona'] !== ''): ?><span>Zona: <?php echo htmlspecialchars_uni($ms['zona']); ?></span><?php endif; ?>
<?php if ($ms['facciones'] !== ''): ?><span>Facciones: <?php echo htmlspecialchars_uni($ms['facciones']); ?></span><?php endif; ?>
            </div>
<?php if ($ms['resumen'] !== ''): ?><p class="mv-miscard-res"><?php echo htmlspecialchars_uni($ms['resumen']); ?></p><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
        <div class="mv-save-bar">
          <button type="button" class="btn btn-sm btn-ghost" disabled title="Disponible en una fase posterior">Publicar misiones (próximamente)</button>
          <span class="mv-note">Por ahora las misiones son solo vista previa; su publicación directa llegará más adelante.</span>
        </div>
<?php endif; ?>

        <h3 class="mv-prev-h">Tramas abiertas</h3>
<?php if (empty($arcosNuevos)): ?>
        <p class="mv-empty">El nuevo estado no deja arcos abiertos.</p>
<?php else: ?>
        <div class="mv-arclist">
<?php foreach ($arcosNuevos as $ar): if (!is_array($ar)) continue; ?>
          <div class="mv-arccard">
            <div class="mv-arccard-h"><span class="mv-arccard-t"><?php echo htmlspecialchars_uni((string)($ar['nombre'] ?? '(sin nombre)')); ?></span><span class="mv-arccard-est"><?php echo htmlspecialchars_uni((string)($ar['estado'] ?? '')); ?></span></div>
            <div class="mv-arccard-meta">
<?php if (!empty($ar['zonas'])): ?><span>Zonas: <?php echo htmlspecialchars_uni((string)$ar['zonas']); ?></span><?php endif; ?>
<?php if (!empty($ar['facciones'])): ?><span>Facciones: <?php echo htmlspecialchars_uni((string)$ar['facciones']); ?></span><?php endif; ?>
            </div>
<?php if (!empty($ar['descripcion'])): ?><p class="mv-arccard-desc"><?php echo htmlspecialchars_uni((string)$ar['descripcion']); ?></p><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <form method="post" onsubmit="return confirm('¿Publicar el nuevo estado del mundo, el periódico y la noticia?');">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="publicar">

          <h3 class="mv-prev-h">Imágenes a generar</h3>
<?php if (empty($preview['imagenes_list'])): ?>
          <p class="mv-empty">La IA no devolvió imágenes en el bloque ===IMAGENES===.</p>
<?php else: ?>
          <p class="mv-note">Pega el link directo de cada imagen ya generada. Al publicar se insertarán en el periódico en lugar de los recuadros de marcador; las que dejes en blanco se quedan como marcador.</p>
          <div class="mv-imglist">
<?php foreach ($preview['imagenes_list'] as $im): ?>
            <div class="mv-imgcard">
              <div class="mv-imgcard-h"><code><?php echo htmlspecialchars_uni($im['id']); ?></code><?php if ($im['tamano'] !== ''): ?> <span class="mv-imgcard-sz"><?php echo htmlspecialchars_uni($im['tamano']); ?></span><?php endif; ?></div>
<?php if ($im['prompt'] !== ''): ?><p class="mv-imgcard-prompt"><?php echo htmlspecialchars_uni($im['prompt']); ?></p><?php endif; ?>
              <input type="url" class="mv-input mv-imgcard-url" name="img_url[<?php echo htmlspecialchars_uni($im['id']); ?>]" placeholder="https://... link directo a la imagen">
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>

          <div class="mv-save-bar"><button class="btn btn-primary">Publicar</button></div>
        </form>
      </div>
    </div>
<?php endif; ?>
  </section>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var tabs = document.querySelectorAll('#mvTabs .mv-tab');
  var panels = document.querySelectorAll('.mv-panel');
  tabs.forEach(function(t){
    t.addEventListener('click', function(){
      tabs.forEach(function(x){x.classList.remove('on');});
      t.classList.add('on');
      var id = t.getAttribute('data-tab');
      panels.forEach(function(p){ p.hidden = (p.id !== 'tab-'+id); if(!p.hidden){ p.classList.add('vis'); } });
    });
  });
})();
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .05 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>
