<?php
/**
 * I-Forge · Personajes ("Mi expediente" / roster)
 * Página de front-end MyBB (dirección "Foundry Brutalism").
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

// ── Nivel de staff (lo expone el plugin iforge_rol; con respaldo directo) ──
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['iforge_staff_level'])) {
        $staff_level = (int) $mybb->user['iforge_staff_level'];
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
$display_name   = (string) ($mybb->user['iforge_display_name'] ?? ($mybb->user['username'] ?? ''));
$display_name_e = htmlspecialchars_uni($display_name);

// ── Mapa de rango → variable de calor ──
function iforge_heat_var(string $rango): string
{
    $map = array(
        'F' => '--h1', 'E' => '--h1', 'D' => '--h2', 'C' => '--h3', 'B' => '--h4',
        'A' => '--h5', 'S' => '--h6', 'SS' => '--h7', 'SSS' => '--h8', 'M+' => '--h9',
    );
    $rango = strtoupper(trim($rango));
    return $map[$rango] ?? '--h1';
}

function iforge_estado_label(string $estado): array
{
    switch ($estado) {
        case 'aprobado':  return array('Aprobado', 'var(--patina-hi)');
        case 'revision':  return array('En revisi&oacute;n', 'var(--h6)');
        case 'rechazado': return array('Rechazado', 'var(--crack)');
        default:          return array('Borrador', 'var(--rivet)');
    }
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
        // Verifica que el personaje pertenece al usuario y está aprobado.
        $vq = $db->simple_select(
            'rol_personajes',
            'pid, estado, nombre',
            "pid = {$set_pid} AND uid = {$uid}",
            array('limit' => 1)
        );
        if ($db->num_rows($vq)) {
            $prow = $db->fetch_array($vq);
            if ($prow['estado'] === 'aprobado') {
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
            } else {
                $flash = 'Solo puedes activar un personaje aprobado.';
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
// Carga de personajes del usuario
// ─────────────────────────────────────────────────────────────
$personajes = array();
if ($loggedin && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select(
        'rol_personajes',
        'pid, nombre, slug, estado, activo, rango, nivel, avatar',
        "uid = {$uid}",
        array('order_by' => 'activo', 'order_dir' => 'desc')
    );
    while ($row = $db->fetch_array($q)) {
        $personajes[] = $row;
    }
}
$tiene_personajes = count($personajes) > 0;

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
    if ($pj['estado'] !== 'rechazado') {
        $usados_slots++;
    }
}
$hay_hueco = $usados_slots < $slots;

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
<?php echo iforge_rol_head_base(); ?>
<style>
/* Estilos de esta página — base global (:root, body, fondo) en docs/themes/iforge.css */

/* ---------------- BREADCRUMB ---------------- */
.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1300px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

/* ---------------- BOTONES ---------------- */
.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:12px 20px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{background:var(--ember-hi);color:var(--iron);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:7px 13px;font-size:.7rem}

/* ---------------- PLACAS ---------------- */
.plate{border:2px solid #000;background:var(--iron-plate);margin-bottom:12px}
.plate-h{background:var(--iron-edge);padding:9px 13px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:2px solid #000}
.plate-h .t{font-family:var(--disp);font-weight:800;font-size:1.1rem;text-transform:uppercase;color:var(--paper);letter-spacing:.5px}
.plate-h .c{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.plate-b{padding:13px}
.shead{display:flex;align-items:baseline;gap:14px;margin:8px 0 14px}
.shead h1,.shead h2{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

/* ---------------- FLASH ---------------- */
.flash{border:2px solid #000;padding:11px 14px;margin-bottom:16px;font-family:var(--mono);font-size:.74rem;font-weight:700;letter-spacing:.3px}
.flash.ok{background:var(--patina);color:var(--iron)}
.flash.warn{background:var(--h6);color:var(--iron)}

/* ---------------- FOOTER ---------------- */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
.foot-links{display:flex;gap:16px;flex-wrap:wrap}
.foot-links a{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.foot-links a:hover{color:var(--ember-hi)}
.foot-c{font-family:var(--mono);font-size:.62rem;color:var(--ash)}

/* ---------------- REVEAL ---------------- */
.reveal{opacity:0;transform:translateY(14px);transition:opacity .5s,transform .5s}
.reveal.vis{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}.reveal{opacity:1;transform:none}}

/* ---------------- LOCAL: roster ---------------- */
.pj-intro{font-size:.92rem;color:var(--paper-dim);max-width:70ch;margin:-6px 0 18px;line-height:1.55}
.pj-intro b{color:var(--paper);font-weight:600}
.pj-bar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px}
.pj-count{font-family:var(--mono);font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--paper-dim)}
.pj-count b{color:var(--h6)}
.pj-spacer{flex:1}
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.pjcard{border:2px solid #000;background:var(--iron-plate);display:flex;flex-direction:column;transition:transform .16s,box-shadow .16s;position:relative}
.pjcard:hover{transform:translate(-3px,-3px);box-shadow:6px 6px 0 #000}
.pjcard.active{border-color:var(--ember)}
.pjcard.active::after{content:"ACTIVO";position:absolute;top:0;right:0;background:var(--ember);color:var(--iron);font-family:var(--mono);font-size:.56rem;font-weight:700;letter-spacing:.5px;padding:3px 8px;border-left:2px solid #000;border-bottom:2px solid #000}
.pjcard-top{padding:16px;border-bottom:2px solid #000;display:flex;align-items:center;gap:13px;background:linear-gradient(150deg,var(--iron-hi),var(--iron-edge))}
.pjcard-av{width:56px;height:56px;flex:0 0 auto;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-weight:900;font-size:1.5rem;color:var(--ember-hi);overflow:hidden;position:relative}
.pjcard-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.pjcard-name{display:inline-block;font-family:var(--disp);font-weight:800;font-size:1.45rem;text-transform:uppercase;line-height:.98;color:var(--paper)}
a.pjcard-name:hover{color:var(--ember-hi)}
.pjcard-sub{font-family:var(--mono);font-size:.6rem;font-weight:700;color:var(--paper-dim);text-transform:uppercase;letter-spacing:.5px;margin-top:4px}
.heat-badge{font-family:var(--disp);font-weight:900;font-size:1rem;color:var(--iron);padding:2px 9px;border:2px solid #000;display:inline-block;line-height:1}
.pjcard-body{padding:14px 16px;flex:1;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.pjcard-chip{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;padding:3px 9px;border:2px solid #000;color:var(--iron)}
.pjcard-chip.line{background:var(--iron);color:var(--paper);border-color:var(--rivet)}
.pjcard-foot{padding:12px 16px;border-top:2px solid #000;display:flex;align-items:center;justify-content:space-between;gap:10px}
.pjcard-foot form{margin:0}
.is-active-note{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ember-hi)}

/* ---------------- EMPTY ---------------- */
.pj-empty{border:2px dashed var(--rivet);background:var(--iron-plate);padding:40px 22px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:14px}
.pj-empty .anvil{width:72px;height:72px;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.pj-empty .anvil svg{width:38px;height:38px;stroke:var(--h6);fill:none;stroke-width:2}
.pj-empty .big{font-family:var(--disp);font-weight:800;font-size:1.9rem;text-transform:uppercase;color:var(--paper);line-height:1}
.pj-empty p{font-family:var(--mono);font-size:.76rem;color:var(--paper-dim);line-height:1.6;max-width:54ch}
.pj-empty .acts{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:4px}
.pj-who{font-family:var(--mono);font-size:.66rem;color:var(--ash);text-transform:uppercase;letter-spacing:.5px}
.pj-who b{color:var(--h6)}
.pjcard-chip{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 11px;border:2px solid #000;display:inline-block}
.pjcard-chip[style*="--h6"]{animation:pulse-revision 2s ease-in-out infinite}
@keyframes pulse-revision{0%,100%{box-shadow:0 0 0 0 rgba(255,203,147,.4)}50%{box-shadow:0 0 0 6px rgba(255,203,147,0)}}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Personaje</b>
  </div>
</div>

<div class="wrap">

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
<?php if ($loggedin && $tiene_personajes): ?>
    <div class="pj-bar">
      <span class="pj-count"><b><?php echo $usados_slots; ?>/<?php echo $slots; ?></b> hueco(s) usados &middot; sesi&oacute;n: <b><?php echo $username; ?></b></span>
      <span class="pj-spacer"></span>
<?php if ($hay_hueco): ?>
      <a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot btn-sm">Crear personaje</a>
<?php else: ?>
      <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost btn-sm">Pedir m&aacute;s huecos</a>
<?php endif; ?>
    </div>

    <div class="cards">
<?php foreach ($personajes as $pj):
        $es_activo   = ((int) $pj['activo']) === 1;
        $rango       = (string) $pj['rango'];
        $rango_e     = htmlspecialchars_uni($rango);
        $heat        = iforge_heat_var($rango);
        list($est_lbl, $est_col) = iforge_estado_label((string) $pj['estado']);
        $nombre_e    = htmlspecialchars_uni($pj['nombre']);
        $nivel       = (int) $pj['nivel'];
        $avatar      = trim((string) $pj['avatar']);
        $av_initial  = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));
?>
      <article class="pjcard<?php echo $es_activo ? ' active' : ''; ?>">
        <div class="pjcard-top">
          <span class="pjcard-av">
<?php if ($avatar !== ''): ?>
            <img src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="">
<?php else: ?>
            <?php echo htmlspecialchars_uni($av_initial); ?>
<?php endif; ?>
          </span>
          <div>
            <a class="pjcard-name" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>"><?php echo $nombre_e; ?></a>
            <div class="pjcard-sub">Nivel <?php echo $nivel; ?></div>
          </div>
        </div>
        <div class="pjcard-body">
          <span class="heat-badge" style="background:var(<?php echo $heat; ?>)"><?php echo $rango_e; ?></span>
          <span class="pjcard-chip" style="background:<?php echo $est_col; ?>"><?php echo $est_lbl; ?></span>
        </div>
        <div class="pjcard-foot">
          <a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">Ver ficha</a>
<?php if ($es_activo): ?>
          <span class="is-active-note">&#9670; Activo</span>
<?php elseif ($pj['estado'] === 'aprobado'): ?>
          <form method="post" action="<?php echo $bburl; ?>/personajes.php">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="set_active">
            <input type="hidden" name="pid" value="<?php echo (int) $pj['pid']; ?>">
            <button type="submit" class="btn btn-hot btn-sm">Activar</button>
          </form>
<?php else: ?>
          <span class="pjcard-sub">Pendiente</span>
<?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>
    </div>

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
