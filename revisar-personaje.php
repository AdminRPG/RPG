<?php
/**
 * I-Forge · Revisión de expediente (Staff)
 * Vista detallada de ficha con botones Aprobar / Moderar / Rechazar.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'revisar-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/iforge_rol_data.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');
$pid       = (int)($mybb->get_input('pid', MyBB::INPUT_INT));

// Staff level
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['iforge_staff_level'])) {
        $staff_level = (int)$mybb->user['iforge_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) $staff_level = (int)$db->fetch_field($cq, 'staff_level');
    }
}

// Cargar personaje
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($q);
}

if (!$pj) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

if ($staff_level < 1) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

$datos     = $pj['datos'] ? json_decode($pj['datos'], true) : array();
$inventario = $pj['inventario'] ? json_decode($pj['inventario'], true) : array();
$economia   = $pj['economia'] ? json_decode($pj['economia'], true) : array();
$bio        = $pj['bio'] ? json_decode($pj['bio'], true) : array();

// Dueño
$owner_name = '?';
if ($pj['uid'] > 0) {
    $uq = $db->simple_select('users', 'username', "uid = " . (int)$pj['uid'], array('limit' => 1));
    $owner_name = htmlspecialchars_uni($db->fetch_field($uq, 'username'));
}

// POST: aprobar / rechazar / moderar
$flash = ''; $flash_kind = 'ok';
if ($loggedin && $staff_level >= 1 && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga la página.';
        $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        $mensaje_staff = trim($mybb->get_input('mensaje_staff'));

        if ($action === 'approve') {
            $db->update_query('rol_personajes', array('estado' => 'aprobado', 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ($db->table_exists('rol_tramites')) {
                $db->update_query('rol_tramites', array('estado' => 'aprobado', 'lastedit' => TIME_NOW), "pid = {$pid} AND tipo = 'crear_personaje'");
            }
            // Alerta para el jugador
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_aprobado',
                    'titulo' => 'Personaje aprobado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido aprobado. Ya puedes activarlo desde la página de Personaje.',
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha "' . htmlspecialchars_uni($pj['nombre']) . '" aprobada.';
            // Recargar estado
            $pj['estado'] = 'aprobado';

        } elseif ($action === 'reject') {
            $db->update_query('rol_personajes', array('estado' => 'rechazado', 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ($db->table_exists('rol_tramites')) {
                $db->update_query('rol_tramites', array('estado' => 'rechazado', 'lastedit' => TIME_NOW), "pid = {$pid} AND tipo = 'crear_personaje'");
            }
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_rechazado',
                    'titulo' => 'Personaje rechazado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido rechazado.' . ($mensaje_staff !== '' ? ' Motivo: ' . $db->escape_string($mensaje_staff) : ''),
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha "' . htmlspecialchars_uni($pj['nombre']) . '" rechazada.';
            $pj['estado'] = 'rechazado';

        } elseif ($action === 'moderate' && $mensaje_staff !== '') {
            // Moderar: enviar MD al personaje
            if ($db->table_exists('rol_mensajes')) {
                // Buscar staff pid del narrador
                $staff_pid = 0;
                $sq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
                if ($db->num_rows($sq)) $staff_pid = (int)$db->fetch_field($sq, 'pid');
                
                // Crear hilo
                $thread_id = TIME_NOW; // Simple unique thread ID
                $db->insert_query('rol_mensajes', array(
                    'thread_id' => $thread_id,
                    'origen_pid' => $staff_pid,
                    'destino_pid' => $pid,
                    'asunto' => 'Moderación: ' . $db->escape_string($pj['nombre']),
                    'cuerpo' => $db->escape_string($mensaje_staff),
                    'leido' => 0,
                    'dateline' => TIME_NOW
                ));
            }
            // Alerta
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_moderado',
                    'titulo' => 'Ficha moderada: cambios solicitados',
                    'cuerpo' => 'El staff ha solicitado cambios en tu personaje "' . $db->escape_string($pj['nombre']) . '". Revisa tus mensajes para ver los detalles.',
                    'link' => $bburl . '/mensajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Mensaje de moderación enviado a ' . htmlspecialchars_uni($pj['nombre']) . '.';
            $pj['estado'] = 'revision'; // Sigue en revisión
        }
    }
}

// Función local para heat
function iforge_heat_var($rango) {
    $map = ['F'=>'--h1','E'=>'--h1','D'=>'--h2','C'=>'--h3','B'=>'--h4','A'=>'--h5','S'=>'--h6','SS'=>'--h7','M'=>'--h8','M+'=>'--h9'];
    return $map[$rango] ?? '--h1';
}

// Función local para label de estado
function _estado_label($estado) {
    switch ($estado) {
        case 'aprobado': return ['Aprobado', 'var(--patina-hi)'];
        case 'revision': return ['En revisión', 'var(--h6)'];
        case 'rechazado': return ['Rechazado', 'var(--crack)'];
        default: return ['Borrador', 'var(--rivet)'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Revisar: <?php echo htmlspecialchars_uni($pj['nombre']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#0b3157; --iron-plate:#10477B; --iron-hi:#175a95; --iron-edge:#082742;
  --rivet:#3d6f9e;
  --concrete:#eef6fc; --concrete-2:#dbecf9; --concrete-line:#b2d3ea;
  --ink:#0a2f52; --ink-2:#1c5285; --ash:#5c83a7; --paper:#eaf4fb; --paper-dim:#a9c6e0;
  --ember:#FFCB93; --ember-hi:#FFE9A3; --patina:#41A4E0; --patina-hi:#63b8ea; --crack:#e63b2e; --red-hi:#ff5a49;
  --h1:#10477B; --h2:#2f6ea8; --h3:#458CC5; --h4:#41A4E0; --h5:#63b8ea;
  --h6:#FFCB93; --h7:#ffdcae; --h8:#FFE9A3; --h9:#fff6d8;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
.wrap{max-width:1100px;margin:0 auto;padding:0 18px}

/* BREADCRUMB */
.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1100px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim);text-decoration:none}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

/* BOTONES */
.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:12px 20px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{background:var(--ember-hi);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-danger{background:var(--crack);color:#fff}
.btn-danger:hover{background:var(--red-hi);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:7px 13px;font-size:.7rem}

/* SHEAD */
.shead{display:flex;align-items:baseline;gap:14px;margin:20px 0 14px}
.shead h1{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

/* FLASH */
.flash{font-family:var(--mono);font-size:.72rem;padding:10px 14px;border:2px solid #000;margin-bottom:14px}
.flash.ok{background:var(--iron-plate);color:var(--h6);border-color:var(--patina)}
.flash.warn{background:var(--iron-plate);color:var(--ember);border-color:var(--ember)}

/* FICHA */
.sheet{border:2px solid #000;background:var(--iron-plate);margin-bottom:14px}
.sheet-h{background:var(--iron-edge);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:2px solid #000}
.sheet-h-left{display:flex;align-items:center;gap:12px}
.sheet-av{width:56px;height:56px;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-weight:900;font-size:1.4rem;color:var(--ember-hi)}
.sheet-name{font-family:var(--disp);font-weight:800;font-size:1.7rem;text-transform:uppercase;color:var(--paper);line-height:1}
.sheet-owner{font-family:var(--mono);font-size:.62rem;color:var(--ash);text-transform:uppercase;margin-top:2px}
.sheet-badge{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;padding:5px 11px;border:2px solid #000;display:inline-block}
.sheet-b{padding:18px}

.sheet-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.sheet-block{border:2px solid #000;background:var(--iron);overflow:hidden}
.sheet-block-h{background:var(--iron-edge);padding:8px 12px;font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--paper-dim);border-bottom:2px solid #000}
.sheet-block-b{padding:12px}

.stat-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--iron-hi);font-size:.82rem}
.stat-row:last-child{border-bottom:none}
.stat-row .sn{font-family:var(--mono);font-size:.64rem;font-weight:700;color:var(--paper-dim);text-transform:uppercase;min-width:36px}
.stat-row .sl{color:var(--paper-dim);flex:1}
.stat-row .sv{font-family:var(--mono);font-size:.7rem;font-weight:700}
.stat-row .rb{font-family:var(--disp);font-weight:900;font-size:.85rem;padding:2px 8px;border:2px solid #000;line-height:1}

.chip-list{display:flex;flex-wrap:wrap;gap:6px}
.chip-item{font-family:var(--mono);font-size:.62rem;font-weight:700;padding:4px 10px;border:1px solid var(--rivet);color:var(--paper-dim)}
.chip-item.good{color:var(--patina-hi);border-color:var(--patina)}
.chip-item.bad{color:var(--crack);border-color:var(--crack)}

.text-block{font-size:.84rem;color:var(--paper-dim);line-height:1.6}
.text-block strong{color:var(--paper)}
.text-block p{margin-bottom:8px}

/* ACCIONES */
.actions-bar{border:2px solid #000;background:var(--iron-plate);margin:14px 0}
.actions-bar-in{padding:14px 18px;display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap}
.actions-bar .ab-label{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--paper-dim);margin-bottom:4px}
.actions-bar button{margin-left:auto}
.actions-bar textarea{flex:1;min-width:280px;min-height:60px;background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--mono);font-size:.72rem;padding:8px 10px;resize:vertical}
.actions-bar textarea:focus{outline:none;border-color:var(--ember)}

/* FOOTER */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}

/* RESPONSIVE */
@media(max-width:768px){
  .sheet-grid{grid-template-columns:1fr}
  .actions-bar-in{flex-direction:column;align-items:stretch}
  .actions-bar button{margin-left:0}
}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
    <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1>Revisar expediente</h1>
    <span class="code">// staff</span>
    <span class="rule"></span>
  </div>

  <?php if ($flash !== ''): ?>
    <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
  <?php endif; ?>

  <!-- HEADER FICHA -->
  <div class="sheet">
    <div class="sheet-h">
      <div class="sheet-h-left">
        <div class="sheet-av"><?php echo htmlspecialchars_uni(mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8')); ?></div>
        <div>
          <div class="sheet-name"><?php echo htmlspecialchars_uni($pj['nombre']); ?></div>
          <div class="sheet-owner">de <?php echo $owner_name; ?> · creado <?php echo date('d/m/Y', (int)$pj['dateline']); ?></div>
        </div>
      </div>
      <?php $est = _estado_label($pj['estado']); ?>
      <span class="sheet-badge" style="background:<?php echo $est[1]; ?>;color:var(--iron)"><?php echo $est[0]; ?></span>
    </div>

    <div class="sheet-b">
      <div class="sheet-grid">
        <!-- COL IZQUIERDA -->
        <div>
          <!-- IDENTIDAD -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Identidad</div>
            <div class="sheet-block-b">
              <div class="stat-row"><span class="sn">Raza</span><span class="sl"><?php echo htmlspecialchars_uni(ucfirst($datos['raza_principal'] ?? '?')); ?><?php echo !empty($datos['hibrido']) ? ' / ' . htmlspecialchars_uni(ucfirst($datos['raza_secundaria'] ?? '')) : ''; ?></span></div>
              <?php if (!empty($datos['apodo'])): ?>
                <div class="stat-row"><span class="sn">Apodo</span><span class="sl"><?php echo htmlspecialchars_uni($datos['apodo']); ?></span></div>
              <?php endif; ?>
              <div class="stat-row"><span class="sn">Edad</span><span class="sl"><?php echo htmlspecialchars_uni($datos['edad'] ?? '?'); ?></span></div>
              <div class="stat-row"><span class="sn">Género</span><span class="sl"><?php echo htmlspecialchars_uni($datos['genero'] ?? '?'); ?></span></div>
              <div class="stat-row"><span class="sn">Facción</span><span class="sl"><?php echo htmlspecialchars_uni(ucfirst($datos['faccion'] ?? '?')); ?></span></div>
              <?php if (!empty($datos['tiene_d'])): ?>
                <div class="stat-row"><span class="sn">D.</span><span class="sl" style="color:var(--ember-hi);font-weight:700">Portador de la D.</span></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- STATS -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Stats · Rango <?php echo htmlspecialchars_uni($pj['rango'] ?? '?'); ?></div>
            <div class="sheet-block-b">
              <?php 
              $pilares = array('Cuerpo'=>['FUE','DES','VIG','AGI'], 'Mente'=>['INT','ING','CON','PER'], 'Espíritu'=>['CAR','CTR','VOL','SEN']);
              $labels = array('FUE'=>'Fuerza','DES'=>'Destreza','VIG'=>'Vigor','AGI'=>'Agilidad','INT'=>'Intelecto','ING'=>'Ingenio','CON'=>'Concentración','PER'=>'Percepción','CAR'=>'Carisma','CTR'=>'Control','VOL'=>'Voluntad','SEN'=>'Sensibilidad');
              $stats = $datos['stats_efectivas'] ?? $datos['stats_base'] ?? array();
              if (!empty($stats)):
                foreach ($pilares as $pilarName => $keys): ?>
                  <div style="font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--ash);margin:8px 0 4px"><?php echo $pilarName; ?></div>
                  <?php foreach ($keys as $k):
                    $v = (int)($stats[$k] ?? 0);
                    $rangos = ['F','E','D','C','B','A','S','SS','M','M+'];
                    $rl = $rangos[max(0, min(9, $v))] ?? '?';
                    $hv = iforge_heat_var($rl);
                  ?>
                    <div class="stat-row">
                      <span class="sn"><?php echo $k; ?></span>
                      <span class="sl"><?php echo $labels[$k] ?? $k; ?></span>
                      <span class="sv" style="color:var(<?php echo $hv; ?>)"><?php echo $rl; ?></span>
                    </div>
                  <?php endforeach;
                endforeach;
              else: ?>
                <p style="color:var(--paper-dim);font-size:.82rem">Sin datos de stats.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- COL DERECHA -->
        <div>
          <!-- VIRTUDES Y DEFECTOS -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Virtudes <?php echo !empty($datos['pc_gastado']) ? '(+' . (int)$datos['pc_gastado'] . ' PC)' : ''; ?></div>
            <div class="sheet-block-b">
              <?php if (!empty($datos['virtudes'])): ?>
                <div class="chip-list">
                  <?php foreach ($datos['virtudes'] as $v): ?>
                    <span class="chip-item good"><?php echo htmlspecialchars_uni(is_array($v) ? ($v['nombre'] ?? '?') : $v); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p style="color:var(--paper-dim);font-size:.78rem">Sin virtudes.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Defectos <?php echo !empty($datos['pc_devuelto']) ? '(+' . (int)$datos['pc_devuelto'] . ' PC)' : ''; ?></div>
            <div class="sheet-block-b">
              <?php if (!empty($datos['defectos'])): ?>
                <div class="chip-list">
                  <?php foreach ($datos['defectos'] as $d): ?>
                    <span class="chip-item bad"><?php echo htmlspecialchars_uni(is_array($d) ? ($d['nombre'] ?? '?') : $d); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p style="color:var(--paper-dim);font-size:.78rem">Sin defectos.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- EQUIPO -->
          <div class="sheet-block" style="margin-bottom:14px">
            <div class="sheet-block-h">Equipo · <?php echo htmlspecialchars_uni(number_format((int)($economia['berries'] ?? 0))); ?> berries</div>
            <div class="sheet-block-b">
              <?php if (!empty($inventario['arma'])): ?>
                <div class="stat-row"><span class="sl">Arma</span><span class="sv" style="color:var(--paper-dim)"><?php echo htmlspecialchars_uni($inventario['arma']); ?></span></div>
              <?php endif; ?>
              <?php if (!empty($inventario['objeto_personal'])): ?>
                <div class="stat-row"><span class="sl">Objeto personal</span><span class="sv" style="color:var(--paper-dim)"><?php echo htmlspecialchars_uni($inventario['objeto_personal']); ?></span></div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- BIO -->
      <?php if (!empty($bio)): ?>
        <div class="sheet-block" style="margin-top:14px">
          <div class="sheet-block-h">Historia y personalidad</div>
          <div class="sheet-block-b">
            <div class="text-block">
              <?php if (!empty($bio['concepto'])): ?><p><strong>Concepto:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['concepto'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['historia_pasado'])): ?><p><strong>Pasado:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['historia_pasado'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['motivacion'])): ?><p><strong>Motivación:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['motivacion'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['relaciones'])): ?><p><strong>Relaciones:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['relaciones'])); ?></p><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- BARRA DE ACCIONES (solo si está en revisión) -->
  <?php if ($pj['estado'] === 'revision'): ?>
  <div class="actions-bar">
    <form method="post" action="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo $pid; ?>">
      <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
      <div class="actions-bar-in">
        <div>
          <div class="ab-label">Aprobar ficha</div>
          <button type="submit" name="action" value="approve" class="btn btn-hot btn-sm">Aprobar</button>
        </div>
        <div>
          <div class="ab-label">Rechazar ficha</div>
          <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Rechazar</button>
        </div>
        <div style="flex:1;min-width:250px">
          <div class="ab-label">Moderar (enviar cambios solicitados por MD)</div>
          <textarea name="mensaje_staff" placeholder="Describe los cambios necesarios..."></textarea>
        </div>
        <button type="submit" name="action" value="moderate" class="btn btn-ghost btn-sm">Enviar moderación</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
