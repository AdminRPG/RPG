<?php
/**
 * One Piece: Eternal · Ficha, Álbum & Cuadro de Mando del Barco (barco.php)
 * -------------------------------------------------------------------------
 * Ficha completa y Hub de Gestión de Embarcación para el personaje activo.
 * Muestra estadísticas de navegación, salud del casco, despensa de víveres,
 * galería de fotos del barco (álbum naval), puestos de a bordo, plano de cubiertas
 * y panel de gestión integrado (solo dueño) para invitar nakamas y modificar la nave.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'barco.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

if (!$loggedin) {
    header('Location: ' . $bburl . '/member.php?action=login');
    exit;
}

$pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

// Cargar personaje activo
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

// Mensaje de feedback
$flash_msg = '';
$flash_type = 'info';

// Procesar peticiones POST (Gestión, Galería, Invitaciones, Activo)
if ($mybb->request_method === 'post' && $pid > 0) {
    verify_post_check($mybb->get_input('my_post_key'));
    $action = $mybb->get_input('action');

    if ($action === 'guardar_gestion') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        $nombre_post   = $mybb->get_input('nombre');
        $foto_post     = $mybb->get_input('foto_url');
        $desc_post     = $mybb->get_input('descripcion');
        $set_act_post  = (int)$mybb->get_input('es_activo');

        // Procesar fotos adicionales de la galería
        $foto_urls_post = $mybb->get_input('foto_urls', MyBB::INPUT_ARRAY);
        $foto_caps_post = $mybb->get_input('foto_captions', MyBB::INPUT_ARRAY);
        $galeria_new = array();

        if (!empty($foto_post)) {
            $galeria_new[] = array('url' => trim((string)$foto_post), 'caption' => 'Foto Principal');
        }
        if (is_array($foto_urls_post)) {
            foreach ($foto_urls_post as $idx => $f_url) {
                $f_url = trim((string)$f_url);
                if ($f_url !== '' && $f_url !== $foto_post) {
                    $cap = trim((string)($foto_caps_post[$idx] ?? 'Foto de la nave'));
                    $galeria_new[] = array('url' => $f_url, 'caption' => $cap);
                }
            }
        }

        $res = ope_barco_actualizar_datos($barco_id_post, $pid, $nombre_post, $foto_post, $desc_post, $galeria_new);
        if ($res['ok']) {
            if ($set_act_post === 1) {
                ope_barco_set_activo($pid, $barco_id_post);
            }
            $flash_msg = $res['msg'];
            $flash_type = 'ok';
        } else {
            $flash_msg = $res['msg'];
            $flash_type = 'err';
        }
    } elseif ($action === 'invitar_tripulante') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        $pid_target    = (int)$mybb->get_input('pid_invitado');
        $puesto_target = $mybb->get_input('puesto');
        if ($pid_target < 1) {
            $pid_target = $mybb->get_input('nombre_invitado');
        }

        $res = ope_barco_invitar($barco_id_post, $pid, $pid_target, $puesto_target);
        $flash_msg = $res['msg'];
        $flash_type = $res['ok'] ? 'ok' : 'err';
    } elseif ($action === 'responder_invitacion') {
        $inv_id = (int)$mybb->get_input('invitacion_id');
        $decision = (int)$mybb->get_input('aceptar');

        $res = ope_barco_responder_invitacion($inv_id, $pid, ($decision === 1));
        $flash_msg = $res['msg'];
        $flash_type = $res['ok'] ? 'ok' : 'err';
    } elseif ($action === 'desembarcar') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        $expulsa_pid   = (int)$mybb->get_input('expulsar_pid');

        $res = ope_barco_desembarcar($barco_id_post, $pid, $expulsa_pid);
        $flash_msg = $res['msg'];
        $flash_type = $res['ok'] ? 'ok' : 'err';
    } elseif ($action === 'set_activo') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        if (ope_barco_set_activo($pid, $barco_id_post)) {
            $flash_msg = 'Nave designada como buque insignia activo.';
            $flash_type = 'ok';
        }
    } elseif ($action === 'cofre_depositar') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        $monto_post    = (int)$mybb->get_input('monto');
        $res = ope_barco_cofre_depositar_berries($barco_id_post, $pid, $monto_post);
        $flash_msg = $res['msg'];
        $flash_type = $res['ok'] ? 'ok' : 'err';
    } elseif ($action === 'cofre_retirar') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        $monto_post    = (int)$mybb->get_input('monto');
        $res = ope_barco_cofre_retirar_berries($barco_id_post, $pid, $monto_post);
        $flash_msg = $res['msg'];
        $flash_type = $res['ok'] ? 'ok' : 'err';
    } elseif ($action === 'cofre_repartir') {
        $barco_id_post = (int)$mybb->get_input('barco_id');
        $target_pid    = (int)$mybb->get_input('target_pid');
        $monto_post    = (int)$mybb->get_input('monto');
        $res = ope_barco_cofre_repartir_berries($barco_id_post, $pid, $target_pid, $monto_post);
        $flash_msg = $res['msg'];
        $flash_type = $res['ok'] ? 'ok' : 'err';
    }
}

// Cargar lista de barcos del personaje
$barcos = $pid > 0 ? ope_barco_lista($pid) : array();

// Si no tiene barcos, crear bote por defecto
if (empty($barcos) && $pj && function_exists('ope_barco_crear_defecto')) {
    ope_barco_crear_defecto($pid, $uid, $pj['nombre']);
    $barcos = ope_barco_lista($pid);
}

// Barco seleccionado
$selected_barco_id = (int) $mybb->get_input('barco_id');
$barco = null;
if ($selected_barco_id > 0) {
    foreach ($barcos as $b) {
        if ((int)$b['barco_id'] === $selected_barco_id) {
            $barco = $b;
            break;
        }
    }
}
if (!$barco && !empty($barcos)) {
    foreach ($barcos as $b) {
        if (!empty($b['activo'])) {
            $barco = $b;
            break;
        }
    }
    if (!$barco) {
        $barco = $barcos[0];
    }
    $selected_barco_id = (int)$barco['barco_id'];
}

// Comprobar si el usuario activo es el dueño
$is_owner = ($barco && $pid > 0) ? ope_barco_es_dueno($barco, $pid) : false;

// Invitaciones pendientes para el personaje activo
$invitaciones_pendientes = $pid > 0 ? ope_barco_obtener_invitaciones_pendientes($pid) : array();

// Tripulación embarcada aceptada en la nave seleccionada
$trip_embarcada = $barco ? ope_barco_obtener_tripulacion_embarcada($barco['barco_id']) : array();

// Galería de fotos del barco
$galeria_fotos = $barco['galeria_fotos'] ?? array();

// Modificadores del barco y catálogo de mejoras
$barco_mods = $barco ? ope_navegacion_mods_barco($barco) : array();
$catalogo_mejoras = function_exists('ope_barco_mejoras_catalogo') ? ope_barco_mejoras_catalogo() : array();
$tipos_barco = function_exists('ope_navegacion_barcos_tipos') ? ope_navegacion_barcos_tipos() : array();
$tipo_info = ($barco && isset($tipos_barco[$barco['tipo']])) ? $tipos_barco[$barco['tipo']] : array();

// Items náuticos del personaje
$items_pj = $pid > 0 ? ope_nav_item_lista($pid) : array();

// Tripulantes asignados
$tripulantes = $pid > 0 ? ope_viaje_tripulantes_data($pid, array()) : array();

// Logs de movimientos del Cofre de la Nave
$cofre_logs = ($barco && function_exists('ope_barco_cofre_obtener_logs')) ? ope_barco_cofre_obtener_logs($barco['barco_id']) : array();

// Historial de viajes del barco (Bitácora)
$historial_viajes = ($barco && function_exists('ope_viaje_historial_por_barco')) ? ope_viaje_historial_por_barco($barco['barco_id']) : array();

// Lista de todos los personajes aprobados para el selector de invitación
$personajes_disponibles = array();
if ($db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', 'pid, nombre', "pid != {$pid} AND estado = 'aprobado'", array('order_by' => 'nombre', 'order_dir' => 'ASC'));
    while ($prow = $db->fetch_array($pq)) {
        $personajes_disponibles[] = $prow;
    }
}

// Ubicación insular actual
$isla_actual_slug = (string) ($pj['isla_actual'] ?? 'isla_dawn');
$isla_actual = ope_isla_por_slug($isla_actual_slug);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Ficha del Barco</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-barco">
<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b>Ficha del Barco</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Cuadro de Mando Naval</h1>
      <span class="code">// bitácora &amp; navío</span>
      <span class="rule"></span>
    </div>
    <p class="barco-intro">
      Expediente técnico de embarcación. Consulta la resistencia de tu casco, la despensa de víveres, explora el álbum de fotos del barco y gestiona tu buque insignia.
<?php if ($pj): ?>
      Capitán activo: <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b> &middot; Puerto actual: <b><?php echo htmlspecialchars_uni($isla_actual['nombre'] ?? 'Isla Dawn'); ?></b>.
<?php endif; ?>
    </p>
  </section>

<?php if ($flash_msg !== ''): ?>
  <div class="barco-flash-banner flash-<?php echo $flash_type; ?> reveal">
    <span><?php echo htmlspecialchars_uni($flash_msg); ?></span>
  </div>
<?php endif; ?>

<!-- Notificaciones de Invitación a Embarcar -->
<?php if (!empty($invitaciones_pendientes)): ?>
  <section class="plate barco-invitaciones-plate reveal">
    <div class="plate-h">
      <span class="t">Invitaciones de Embarque Pendientes</span>
      <span class="c">// convocatorias de capitán</span>
    </div>
    <div class="plate-b">
<?php foreach ($invitaciones_pendientes as $inv): ?>
      <div class="invitacion-item">
        <div class="invitacion-info">
          <strong><?php echo htmlspecialchars_uni($inv['capitana_nombre']); ?></strong> te ha invitado a embarcar en la nave 
          <b><?php echo htmlspecialchars_uni($inv['barco_nombre']); ?></b> (<?php echo htmlspecialchars_uni(strtoupper($inv['barco_tipo'])); ?>)
          como <span><?php echo htmlspecialchars_uni($inv['puesto']); ?></span>.
        </div>
        <div class="invitacion-actions">
          <form method="post" action="<?php echo $bburl; ?>/barco.php" class="ope-form-inline">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="responder_invitacion">
            <input type="hidden" name="invitacion_id" value="<?php echo (int)$inv['invitacion_id']; ?>">
            <input type="hidden" name="aceptar" value="1">
            <button type="submit" class="btn btn-sm btn-hot">Aceptar y Embarcar</button>
          </form>
          <form method="post" action="<?php echo $bburl; ?>/barco.php" class="ope-form-inline">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="responder_invitacion">
            <input type="hidden" name="invitacion_id" value="<?php echo (int)$inv['invitacion_id']; ?>">
            <input type="hidden" name="aceptar" value="0">
            <button type="submit" class="btn btn-sm btn-ghost">Rechazar</button>
          </form>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php if (!$pj): ?>
  <div class="plate">
    <div class="plate-b">
      <p class="pj-empty">Debes activar un personaje para visualizar tu embarcación.</p>
    </div>
  </div>
<?php elseif (!$barco): ?>
  <div class="plate">
    <div class="plate-b">
      <p class="pj-empty">No tienes un barco registrado. Puedes solicitar uno o adquirirlo en los astilleros de Water Seven o Loguetown.</p>
    </div>
  </div>
<?php else: ?>

  <!-- Selector de Barcos -->
<?php if (count($barcos) > 1): ?>
  <div class="barco-switcher-bar reveal">
    <span class="switcher-lbl">Flota propia (1 activo permitido):</span>
    <div class="switcher-btns">
<?php foreach ($barcos as $b_opt): ?>
      <a href="<?php echo $bburl; ?>/barco.php?barco_id=<?php echo (int)$b_opt['barco_id']; ?>" class="btn btn-sm <?php echo ((int)$b_opt['barco_id'] === $selected_barco_id) ? 'btn-hot' : 'btn-ghost'; ?>">
        <?php echo htmlspecialchars_uni($b_opt['nombre']); ?> 
        <?php echo !empty($b_opt['activo']) ? ' (Activo)' : ''; ?>
      </a>
<?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

  <!-- PESTAÑAS PRINCIPALES DEL BARCO -->
  <div class="barco-nav-tabs reveal">
    <button type="button" class="tab-btn active" onclick="switchBarcoTab('ficha', this)">Ficha &amp; Galería Naval</button>
<?php if ($is_owner): ?>
    <button type="button" class="tab-btn" onclick="switchBarcoTab('gestion', this)">Panel de Gestión &amp; Astillero</button>
<?php endif; ?>
  </div>

  <!-- PESTAÑA 1: FICHA GENERAL & GALERÍA -->
  <div id="tab-barco-ficha" class="barco-tab-content active">

    <!-- CABECERA HERO DEL BARCO -->
    <section class="plate barco-hero-plate reveal">
      <div class="barco-hero-header">
        
<?php if (!empty($barco['foto_url'])): ?>
        <div class="barco-hero-avatar">
          <img src="<?php echo htmlspecialchars_uni($barco['foto_url']); ?>" alt="<?php echo htmlspecialchars_uni($barco['nombre']); ?>">
        </div>
<?php endif; ?>

        <div class="barco-title-box">
          <div class="barco-chip-row">
            <span class="barco-type-chip"><?php echo htmlspecialchars_uni($barco['tipo_label']); ?> &middot; Estadio <?php echo ucfirst(htmlspecialchars_uni($barco['estadio'])); ?></span>
<?php if (!empty($barco['activo'])): ?>
            <span class="barco-active-badge">Buque Insignia Activo</span>
<?php endif; ?>
          </div>
          <h2 class="barco-hero-title"><?php echo htmlspecialchars_uni($barco['nombre']); ?></h2>
          
<?php if (!empty($barco['descripcion'])): ?>
          <p class="barco-hero-desc"><?php echo nl2br(htmlspecialchars_uni($barco['descripcion'])); ?></p>
<?php endif; ?>

          <div class="barco-hero-meta">
            <span>Puerto actual: <strong><?php echo htmlspecialchars_uni($isla_actual['nombre'] ?? 'Isla Dawn'); ?></strong></span>
            <span>Capacidad: <strong><?php echo (int)($tipo_info['capacidad'] ?? 1); ?> nakamas</strong></span>
            <span>Registro: <strong><?php echo my_date('j F Y', (int)$barco['dateline']); ?></strong></span>
          </div>

<?php if ($is_owner && empty($barco['activo'])): ?>
          <div class="barco-hero-actions">
            <form method="post" action="<?php echo $bburl; ?>/barco.php" class="ope-form-inline">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="action" value="set_activo">
              <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">
              <button type="submit" class="btn btn-ghost btn-sm">Establecer como Barco Activo</button>
            </form>
          </div>
<?php endif; ?>

        </div>

      </div>
    </section>

    <!-- GALERÍA DE FOTOS DE LA NAVE (ÁLBUM NAVAL) -->
    <section class="plate reveal mb-18">
      <div class="plate-h">
        <span class="t">Álbum de Fotos de la Nave</span>
        <span class="c">// galería gráfica del navío</span>
      </div>
      <div class="plate-b">
<?php if (empty($galeria_fotos)): ?>
        <p class="pj-empty">No se han registrado imágenes en la galería de este navío.</p>
<?php else: ?>
        <div class="barco-galeria-grid">
<?php foreach ($galeria_fotos as $foto_elem): ?>
<?php if (!empty($foto_elem['url'])): ?>
          <div class="galeria-card">
            <div class="galeria-img-wrap">
              <img src="<?php echo htmlspecialchars_uni($foto_elem['url']); ?>" alt="<?php echo htmlspecialchars_uni($foto_elem['caption'] ?? 'Foto del barco'); ?>" loading="lazy">
            </div>
            <span class="galeria-caption"><?php echo htmlspecialchars_uni($foto_elem['caption'] ?? 'Fotografía Naval'); ?></span>
          </div>
<?php endif; ?>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
    </section>

    <div class="barco-dashboard-grid">

      <!-- COLUMNA IZQUIERDA: ATRIBUTOS Y TRIPULACIÓN -->
      <div class="barco-left-col">

        <!-- Ficha de Atributos Navales -->
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">Atributos de Navegación</span>
            <span class="c">// parámetros navales</span>
          </div>
          <div class="plate-b">
            <div class="attr-stats-grid">

              <div class="attr-card">
                <span class="attr-lbl">Velocidad Naval</span>
                <span class="attr-val">Velocidad x<?php echo (int)$barco['vel']; ?></span>
                <span class="attr-sub">Conversión OPE: <strong>1 día off-rol = 1.5 días en rol</strong></span>
              </div>

              <div class="attr-card">
                <span class="attr-lbl">Blindaje &amp; Resistencia</span>
                <span class="attr-val">Mod. Peligro: <?php echo (int)($barco_mods['peligro'] ?? 0); ?></span>
                <span class="attr-sub">Protección contra tempestades y rocas</span>
              </div>

              <div class="attr-card">
                <span class="attr-lbl">Estabilidad de Clima</span>
                <span class="attr-val">Mod. Clima: <?php echo (int)($barco_mods['clima'] ?? 0); ?></span>
                <span class="attr-sub">Maniobrabilidad en mar picado</span>
              </div>

              <div class="attr-card">
                <span class="attr-lbl">Sigilo &amp; Camuflaje</span>
                <span class="attr-val">Mod. Encuentro: <?php echo (int)($barco_mods['encuentro'] ?? 0); ?></span>
                <span class="attr-sub">Detección por patrullas de la Marine</span>
              </div>

            </div>
          </div>
        </section>

        <!-- Puestos de A Bordo y Tripulación Embarcada -->
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">Puestos de A Bordo &amp; Tripulación</span>
            <span class="c">// roles y embarcados</span>
          </div>
          <div class="plate-b">
            <div class="puestos-grid">
<?php
$puestos_map = array(
    'capitan'    => array('titulo' => 'Capitán',    'bono' => 'Comando de travesía &middot; Decisión de rumbo'),
    'navegante'  => array('titulo' => 'Navegante',  'bono' => 'Clima -12, Peligro -4 &middot; Lectura de cartas'),
    'timonel'    => array('titulo' => 'Timonel',    'bono' => 'Peligro -10, Encuentro -3 &middot; Maniobra evasiva'),
    'vigia'      => array('titulo' => 'Vigía',      'bono' => 'Hallazgo +12, Encuentro -8 &middot; Detección lejana'),
    'carpintero' => array('titulo' => 'Carpintero', 'bono' => 'Peligro -6 &middot; Reparación +10% casco por tramo'),
    'cocinero'   => array('titulo' => 'Cocinero',   'bono' => 'Peligro -4, Despensa +15% &middot; Moral'),
    'medico'     => array('titulo' => 'Médico',     'bono' => 'Peligro -5 &middot; Cura de epidemias y heridas'),
    'artillero'  => array('titulo' => 'Artillero',  'bono' => 'Encuentro +4 &middot; Potencia en combate naval'),
);

foreach ($puestos_map as $p_key => $p_info):
    $asignado = null;
    foreach ($tripulantes as $t_item) {
        if (strtolower($t_item['oficio']) === $p_key || strtolower($t_item['rol']) === $p_key) {
            $asignado = $t_item;
            break;
        }
    }
    if (!$asignado && !empty($trip_embarcada)) {
        foreach ($trip_embarcada as $emb) {
            if (strtolower($emb['puesto']) === $p_key) {
                $asignado = array('nombre' => $emb['pj_nombre']);
                break;
            }
        }
    }
    if (!$asignado && $p_key === 'capitan' && $pj) {
        $asignado = array('nombre' => $pj['nombre']);
    }
?>
              <div class="puesto-card <?php echo $asignado ? 'is-occupied' : 'is-empty'; ?>">
                <div class="puesto-head">
                  <span class="puesto-title"><?php echo htmlspecialchars_uni($p_info['titulo']); ?></span>
                  <span class="puesto-badge"><?php echo $asignado ? 'Cubierto' : 'Vacante'; ?></span>
                </div>
                <div class="puesto-body">
<?php if ($asignado): ?>
                  <strong class="member-name"><?php echo htmlspecialchars_uni($asignado['nombre']); ?></strong>
<?php else: ?>
                  <span class="member-empty">Sin personaje asignado a este puesto.</span>
<?php endif; ?>
                  <span class="puesto-bono"><?php echo $p_info['bono']; ?></span>
                </div>
              </div>
<?php endforeach; ?>
            </div>

            <!-- Tripulación Embarcada Lista -->
<?php if (!empty($trip_embarcada)): ?>
            <div class="embarcados-box mt-16">
              <h4 class="embarcados-title">Nakamas Embarcados a Bordo</h4>
              <div class="embarcados-list">
<?php foreach ($trip_embarcada as $emb_pj): ?>
                <div class="embarcado-chip">
                  <span><strong><?php echo htmlspecialchars_uni($emb_pj['pj_nombre']); ?></strong> (<?php echo htmlspecialchars_uni(ucfirst($emb_pj['puesto'])); ?>)</span>
<?php if ($is_owner): ?>
                  <form method="post" action="<?php echo $bburl; ?>/barco.php" class="ope-form-inline">
                    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                    <input type="hidden" name="action" value="desembarcar">
                    <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">
                    <input type="hidden" name="expulsar_pid" value="<?php echo (int)$emb_pj['pid_invitado']; ?>">
                    <button type="submit" class="btn-link-expulsar" title="Desembarcar nakama">&times;</button>
                  </form>
<?php endif; ?>
                </div>
<?php endforeach; ?>
              </div>
            </div>
<?php endif; ?>

          </div>
        </section>

        <!-- Mejoras Instaladas y Ranuras -->
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">Mejoras de Embarcación</span>
            <span class="c">// equipamiento &amp; reformas</span>
          </div>
          <div class="plate-b">
<?php
$mejoras_instaladas = $barco['mejoras'] ?? array();
?>
            <div class="mejoras-grid">
<?php foreach ($catalogo_mejoras as $m_key => $m_data): ?>
<?php $instalada = !empty($mejoras_instaladas[$m_key]); ?>
              <div class="mejora-card <?php echo $instalada ? 'is-installed' : 'is-locked'; ?>">
                <div class="mejora-h">
                  <span class="mejora-name"><?php echo htmlspecialchars_uni($m_data['nombre']); ?></span>
                  <span class="mejora-tag"><?php echo $instalada ? 'INSTALADA' : ('Estadio: ' . ucfirst($m_data['estadio_min'])); ?></span>
                </div>
                <p class="mejora-efecto"><?php echo htmlspecialchars_uni($m_data['efecto']); ?></p>
              </div>
<?php endforeach; ?>
            </div>
          </div>
        </section>

      </div>

      <!-- COLUMNA DERECHA: PLANOS, VÍNCULO Y HISTORIAL -->
      <div class="barco-right-col">

        <!-- Estado & Suministros de la Nave -->
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">Estado &amp; Suministros</span>
            <span class="c">// salud del casco &amp; víveres</span>
          </div>
          <div class="plate-b">
            <div class="barco-gauges-column">
              
              <!-- Medidor 1: Integridad del Casco -->
              <div class="barco-casco-widget">
                <div class="casco-lbl-row">
                  <span class="casco-title">Integridad del Casco</span>
                  <span class="casco-val"><?php echo (int)$barco['estado_casco']; ?>%</span>
                </div>
                <div class="casco-meter-bg">
                  <div class="casco-meter-fill" style="width: <?php echo (int)$barco['estado_casco']; ?>%;"></div>
                </div>
                <span class="casco-status-note">
<?php if ((int)$barco['estado_casco'] >= 80): ?>
                  Estado impecable. Casco listo para alta mar.
<?php elseif ((int)$barco['estado_casco'] >= 40): ?>
                  Desgaste moderado. Reparaciones en puerto recomendadas.
<?php else: ?>
                  ¡Daño estructural grave! Reparar en astillero.
<?php endif; ?>
                </span>
              </div>

              <!-- Medidor 2: Nivel de Despensa & Víveres -->
              <div class="barco-casco-widget despensa-widget mt-12">
                <div class="casco-lbl-row">
                  <span class="casco-title">Despensa &amp; Víveres</span>
                  <span class="casco-val"><?php echo (int)($barco['despensa'] ?? 100); ?>%</span>
                </div>
                <div class="casco-meter-bg">
                  <div class="casco-meter-fill despensa-fill" style="width: <?php echo (int)($barco['despensa'] ?? 100); ?>%;"></div>
                </div>
                <span class="casco-status-note">
<?php if ((int)($barco['despensa'] ?? 100) >= 70): ?>
                  Suministros abundantes para travesías largas.
<?php elseif ((int)($barco['despensa'] ?? 100) >= 30): ?>
                  Raciones moderadas. Reaprovisionar en el próximo puerto.
<?php else: ?>
                  ¡Despensa bajo mínimos! Riesgo de hambruna en viaje.
<?php endif; ?>
                </span>
              </div>

            </div>
          </div>
        </section>

          <!-- Cofre de la Nave -->
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">Cofre de la Nave</span>
            <span class="c">// tesoro &amp; aportes de a bordo</span>
          </div>
          <div class="plate-b">
            <div class="cofre-summary-box">
              <div class="cofre-balance-row">
                <span class="cofre-lbl">Saldo en Arca:</span>
                <span class="cofre-val"><?php echo number_format((int)($barco['berries_cofre'] ?? 0)); ?> <small>B.</small></span>
              </div>
              <p class="cofre-desc">Fondo común de la tripulación. Los nakamas pueden aportar Berries para reparaciones, suministros o reparto de botín.</p>
              <button type="button" class="btn btn-hot btn-block mt-10" onclick="document.getElementById('cofre-modal').classList.add('open')">
                Abrir Cofre de a Bordo &amp; Movimientos
              </button>
            </div>

            <!-- Vista previa de últimos movimientos -->
<?php if (!empty($cofre_logs)): ?>
            <div class="cofre-logs-preview mt-14">
              <h4 class="cofre-logs-title">Últimos Movimientos del Cofre</h4>
              <ul class="cofre-logs-mini">
<?php foreach (array_slice($cofre_logs, 0, 4) as $clog): ?>
                <li>
                  <strong><?php echo htmlspecialchars_uni($clog['actor_nombre'] ?? 'Nakama'); ?></strong>
<?php if ($clog['tipo'] === 'depositar_berries'): ?>
                  depositó <span class="c-gold">+<?php echo number_format((int)$clog['monto_berries']); ?> B.</span>
<?php elseif ($clog['tipo'] === 'retirar_berries'): ?>
                  retiró <span class="c-crack">-<?php echo number_format((int)$clog['monto_berries']); ?> B.</span>
<?php elseif ($clog['tipo'] === 'repartir_berries'): ?>
                  repartió <span class="c-patina"><?php echo number_format((int)$clog['monto_berries']); ?> B.</span> a <strong><?php echo htmlspecialchars_uni($clog['target_nombre'] ?? 'Tripulante'); ?></strong>
<?php endif; ?>
                </li>
<?php endforeach; ?>
              </ul>
            </div>
<?php endif; ?>
          </div>
        </section>

        <!-- Bitácora de Viajes del Barco -->
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">Bitácora de Travesías</span>
            <span class="c">// historial navegado</span>
          </div>
          <div class="plate-b">
<?php if (empty($historial_viajes)): ?>
            <p class="pj-empty">Este barco aún no ha registrado travesías oficiales en el cuaderno de bitácora.</p>
<?php else: ?>
            <ul class="bitacora-list">
<?php foreach ($historial_viajes as $v_hist): ?>
              <li>
                <div class="b-route">
                  <strong><?php echo htmlspecialchars_uni($v_hist['origen_nombre']); ?> &rarr; <?php echo htmlspecialchars_uni($v_hist['destino_nombre']); ?></strong>
                  <span class="b-status b-status--<?php echo htmlspecialchars_uni($v_hist['estado']); ?>"><?php echo strtoupper(htmlspecialchars_uni($v_hist['estado'])); ?></span>
                </div>
                <div class="b-meta">
                  <span>Peligro: <?php echo htmlspecialchars_uni(ucfirst($v_hist['nivel_peligro'] ?? 'bajo')); ?></span> &middot;
                  <span class="c-dim"><?php echo my_date('relative', (int)$v_hist['dateline']); ?></span>
                </div>
              </li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
          </div>
        </section>

      </div>

    </div>

  </div>

  <!-- PESTAÑA 2: HUB DE GESTIÓN Y ASTILLERO (SOLO DUEÑO) -->
<?php if ($is_owner): ?>
  <div id="tab-barco-gestion" class="barco-tab-content">
    
    <div class="gestion-hub-grid">
      
      <!-- SECCIÓN 1: DATOS Y ÁLBUM DE FOTOS -->
      <section class="plate reveal">
        <div class="plate-h">
          <span class="t">Configuración &amp; Álbum Fotográfico</span>
          <span class="c">// edición de datos y galería</span>
        </div>
        <div class="plate-b">
          <form method="post" action="<?php echo $bburl; ?>/barco.php">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="guardar_gestion">
            <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">

            <div class="form-row-2">
              <div class="form-group mb-14">
                <label class="form-label">Nombre del Navío:</label>
                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars_uni($barco['nombre']); ?>" required>
              </div>

              <div class="form-group mb-14">
                <label class="form-label">Foto Principal (Avatar / Banderín):</label>
                <input type="url" name="foto_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars_uni($barco['foto_url'] ?? ''); ?>">
              </div>
            </div>

            <div class="form-group mb-14">
              <label class="form-label">Descripción &amp; Lore del Barco:</label>
              <textarea name="descripcion" class="form-control" rows="3" placeholder="Mascarón de proa, madera empleada, leyenda de la nave..."><?php echo htmlspecialchars_uni($barco['descripcion'] ?? ''); ?></textarea>
            </div>

            <!-- Gestor de Múltiples Fotos de la Galería -->
            <div class="form-group mb-16">
              <label class="form-label">Galería de Fotos Adicionales (Álbum del Navío):</label>
              <p class="hint mb-10">Añade fotos de la cubierta, camarotes, proa o el navío navegando.</p>
              
              <div id="galeria-inputs-container">
<?php
$g_fotos = $barco['galeria_fotos'] ?? array();
$idx_count = 0;
foreach ($g_fotos as $gf):
    if (!empty($gf['url']) && $gf['url'] !== ($barco['foto_url'] ?? '')):
        $idx_count++;
?>
                <div class="galeria-input-row mb-8">
                  <input type="url" name="foto_urls[]" class="form-control" placeholder="https://URL-de-imagen.jpg" value="<?php echo htmlspecialchars_uni($gf['url']); ?>">
                  <input type="text" name="foto_captions[]" class="form-control" placeholder="Pie de foto (ej. Vista de Cubierta)" value="<?php echo htmlspecialchars_uni($gf['caption'] ?? ''); ?>">
                  <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()">&times;</button>
                </div>
<?php
    endif;
endforeach;
if ($idx_count === 0):
?>
                <div class="galeria-input-row mb-8">
                  <input type="url" name="foto_urls[]" class="form-control" placeholder="https://URL-de-imagen.jpg">
                  <input type="text" name="foto_captions[]" class="form-control" placeholder="Pie de foto (ej. Mascarón de Proa)">
                </div>
<?php endif; ?>
              </div>

              <button type="button" class="btn btn-ghost btn-sm mt-6" onclick="addFotoInputRow()">+ Añadir otra foto al álbum</button>
            </div>

            <div class="form-group mb-16">
              <label class="checkbox-lbl">
                <input type="checkbox" name="es_activo" value="1" <?php echo !empty($barco['activo']) ? 'checked' : ''; ?>>
                <span>Establecer como mi único <strong>Barco Activo / Buque Insignia</strong></span>
              </label>
            </div>

            <button type="submit" class="btn btn-hot">Guardar Configuración de Embarcación</button>
          </form>
        </div>
      </section>

      <!-- SECCIÓN 2: CONVOCATORIA DE TRIPULACIÓN Y EXPULSIÓN -->
      <section class="plate reveal">
        <div class="plate-h">
          <span class="t">Convocatoria &amp; Reclutamiento</span>
          <span class="c">// invitar nakamas a bordo</span>
        </div>
        <div class="plate-b">
          <form method="post" action="<?php echo $bburl; ?>/barco.php" class="invite-form-hub">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="invitar_tripulante">
            <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">

            <div class="form-group mb-14">
              <label class="form-label">Seleccionar Personaje a Invitar:</label>
              <select name="pid_invitado" class="form-control" required>
                <option value="">-- Seleccionar Nakama --</option>
<?php foreach ($personajes_disponibles as $pj_opt): ?>
                <option value="<?php echo (int)$pj_opt['pid']; ?>"><?php echo htmlspecialchars_uni($pj_opt['nombre']); ?></option>
<?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mb-16">
              <label class="form-label">Puesto Naval Asignado:</label>
              <select name="puesto" class="form-control">
                <option value="tripulante" selected>Tripulante (Navega a bordo sin bono especializado)</option>
                <option value="navegante">Navegante</option>
                <option value="timonel">Timonel</option>
                <option value="vigia">Vigía</option>
                <option value="carpintero">Carpintero</option>
                <option value="cocinero">Cocinero</option>
                <option value="medico">Médico</option>
                <option value="artillero">Artillero</option>
              </select>
            </div>

            <button type="submit" class="btn btn-hot btn-block">Enviar Invitación Oficial</button>
          </form>

<?php if (!empty($trip_embarcada)): ?>
          <hr class="modal-divider">
          <h4 class="embarcados-title">Gestión de Tripulantes Embarcados</h4>
          <div class="embarcados-manage-list">
<?php foreach ($trip_embarcada as $emb_manage): ?>
            <div class="manage-trip-row">
              <div>
                <strong><?php echo htmlspecialchars_uni($emb_manage['pj_nombre']); ?></strong>
                <span class="c-dim"> &middot; <?php echo htmlspecialchars_uni(ucfirst($emb_manage['puesto'])); ?></span>
              </div>
              <form method="post" action="<?php echo $bburl; ?>/barco.php" class="ope-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="action" value="desembarcar">
                <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">
                <input type="hidden" name="expulsar_pid" value="<?php echo (int)$emb_manage['pid_invitado']; ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Desembarcar</button>
              </form>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </section>

    </div>

  </div>
<?php endif; ?>

<?php endif; ?>

</div>

<!-- MODAL INTERACTIVO DEL COFRE DE LA NAVE -->
<div class="modal-overlay" id="cofre-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal-box modal-lg">
    <div class="modal-h">
      <h3 class="modal-title">Cofre de la Nave &amp; Tesoro Común</h3>
      <button type="button" class="modal-close" onclick="document.getElementById('cofre-modal').classList.remove('open')">&times;</button>
    </div>
    <div class="modal-b">
      
      <div class="cofre-modal-hero mb-16">
        <div class="cofre-hero-stat">
          <span class="lbl">Saldo en el Cofre del Barco</span>
          <span class="val c-gold"><?php echo number_format((int)($barco['berries_cofre'] ?? 0)); ?> Berries</span>
        </div>
        <div class="cofre-hero-stat">
          <span class="lbl">Tus Berries Personales</span>
          <span class="val"><?php echo number_format((int)($pj['berries'] ?? 0)); ?> Berries</span>
        </div>
      </div>

      <div class="cofre-actions-grid">
        
        <!-- Formulario 1: Depositar al Cofre -->
        <div class="cofre-action-card">
          <h4>Depositar al Cofre</h4>
          <p class="hint">Aporta fondos de tu bolsa personal al cofre común de la nave.</p>
          <form method="post" action="<?php echo $bburl; ?>/barco.php">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="cofre_depositar">
            <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">
            
            <div class="form-group mb-10">
              <input type="number" name="monto" class="form-control" placeholder="Cantidad de Berries" min="1" max="<?php echo (int)($pj['berries'] ?? 0); ?>" required>
            </div>
            <button type="submit" class="btn btn-hot btn-block btn-sm">Ingresar Berries</button>
          </form>
        </div>

<?php if ($is_owner): ?>
        <!-- Formulario 2: Retirar a Bolsa Personal (Capitán) -->
        <div class="cofre-action-card">
          <h4>Retirar del Cofre (Capitán)</h4>
          <p class="hint">Transfiere fondos del cofre a tu bolsa personal.</p>
          <form method="post" action="<?php echo $bburl; ?>/barco.php">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="cofre_retirar">
            <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">
            
            <div class="form-group mb-10">
              <input type="number" name="monto" class="form-control" placeholder="Cantidad de Berries" min="1" max="<?php echo (int)($barco['berries_cofre'] ?? 0); ?>" required>
            </div>
            <button type="submit" class="btn btn-ghost btn-block btn-sm">Retirar a mi Bolsa</button>
          </form>
        </div>

        <!-- Formulario 3: Repartir Botín a Nakama (Capitán) -->
        <div class="cofre-action-card full-width mt-12">
          <h4>Repartir Botín a un Tripulante</h4>
          <p class="hint">Entrega una porción de los Berries del cofre directamente a un nakama embarcado.</p>
          <form method="post" action="<?php echo $bburl; ?>/barco.php" class="form-row-2">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="cofre_repartir">
            <input type="hidden" name="barco_id" value="<?php echo (int)$barco['barco_id']; ?>">
            
            <div class="form-group mb-0">
              <select name="target_pid" class="form-control" required>
                <option value="">-- Seleccionar Tripulante Embarcado --</option>
<?php foreach ($trip_embarcada as $t_emb): ?>
                <option value="<?php echo (int)$t_emb['pid_invitado']; ?>"><?php echo htmlspecialchars_uni($t_emb['pj_nombre']); ?> (<?php echo htmlspecialchars_uni(ucfirst($t_emb['puesto'])); ?>)</option>
<?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mb-0 flex-row">
              <input type="number" name="monto" class="form-control" placeholder="Monto Berries" min="1" max="<?php echo (int)($barco['berries_cofre'] ?? 0); ?>" required>
              <button type="submit" class="btn btn-hot btn-sm">Entregar</button>
            </div>
          </form>
        </div>
<?php endif; ?>

      </div>

      <!-- Historial Auditado de Movimientos -->
      <div class="cofre-full-logs mt-18">
        <h4>Historial de Movimientos del Cofre</h4>
<?php if (empty($cofre_logs)): ?>
        <p class="pj-empty">Sin movimientos registrados en la bitácora del cofre.</p>
<?php else: ?>
        <div class="cofre-logs-list">
<?php foreach ($cofre_logs as $log_item): ?>
          <div class="cofre-log-row">
            <span class="log-time"><?php echo my_date('j F Y, H:i', (int)$log_item['dateline']); ?></span>
            <div class="log-desc">
              <strong><?php echo htmlspecialchars_uni($log_item['actor_nombre'] ?? 'Nakama'); ?></strong>
<?php if ($log_item['tipo'] === 'depositar_berries'): ?>
              depositó <strong class="c-gold">+<?php echo number_format((int)$log_item['monto_berries']); ?> Berries</strong> en el cofre.
<?php elseif ($log_item['tipo'] === 'retirar_berries'): ?>
              retiró <strong class="c-crack">-<?php echo number_format((int)$log_item['monto_berries']); ?> Berries</strong> del cofre.
<?php elseif ($log_item['tipo'] === 'repartir_berries'): ?>
              repartió <strong class="c-patina"><?php echo number_format((int)$log_item['monto_berries']); ?> Berries</strong> del cofre a <strong><?php echo htmlspecialchars_uni($log_item['target_nombre'] ?? 'Tripulante'); ?></strong>.
<?php endif; ?>
            </div>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
function switchBarcoTab(tabName, btnEl) {
  document.querySelectorAll('.barco-tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.barco-nav-tabs .tab-btn').forEach(el => el.classList.remove('active'));
  const target = document.getElementById('tab-barco-' + tabName);
  if (target) { target.classList.add('active'); }
  if (btnEl) { btnEl.classList.add('active'); }
}

function addFotoInputRow() {
  const container = document.getElementById('galeria-inputs-container');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'galeria-input-row mb-8';
  div.innerHTML = '<input type="url" name="foto_urls[]" class="form-control" placeholder="https://URL-de-imagen.jpg">' +
                  '<input type="text" name="foto_captions[]" class="form-control" placeholder="Pie de foto">' +
                  '<button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()">&times;</button>';
  container.appendChild(div);
}

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
