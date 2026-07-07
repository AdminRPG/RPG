<?php
/**
 * I-Forge · Guías
 * Página de front-end MyBB (dirección "Foundry Brutalism").
 * Landing de reglamento & sistema. Estado vacío honesto: sin reglas inventadas.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'guias.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Nivel de staff (plugin iforge_rol, con respaldo directo)
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['iforge_staff_level'])) {
        $staff_level = (int)$mybb->user['iforge_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $staff_level = (int)$db->fetch_field($cq, 'staff_level');
        }
    }
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Guías</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ============================================================
   I-FORGE · SISTEMA COMPARTIDO — "FOUNDRY BRUTALISM"
   Materiales: IRON (estructura) + CONCRETE (lectura) + escala de CALOR.
   Tipos: Big Shoulders Display · Space Mono · Archivo
   ============================================================ */
:root{
  --iron:#1b1d22; --iron-plate:#24272e; --iron-hi:#31353d; --iron-edge:#0d0e11;
  --rivet:#565b64;
  --concrete:#d7d3c6; --concrete-2:#cbc6b6; --concrete-line:#b3ad9c;
  --ink:#161512; --ink-2:#4a463d; --ash:#7f7a6d; --paper:#e9e6dd; --paper-dim:#a9a599;
  --ember:#e0641f; --ember-hi:#f2842f; --patina:#5f8a6a; --patina-hi:#7aa886; --crack:#c14a29;
  --h1:#6b6f78; --h2:#9a6b4e; --h3:#c14a29; --h4:#e0641f; --h5:#ef8b1e;
  --h6:#f4b02f; --h7:#f8cf4f; --h8:#fbe488; --h9:#fdf4cf;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
a{color:var(--ember-hi);text-decoration:none}
a:hover{color:var(--h6)}
::selection{background:var(--ember);color:var(--iron)}
img,svg{display:block}
:focus-visible{outline:3px solid var(--ember-hi);outline-offset:2px}
.wrap{max-width:1300px;margin:0 auto;padding:0 18px}
.mono{font-family:var(--mono)}

/* ---------------- NAVBAR (iforge-*, idéntica al tema) ---------------- */
#iforge-navbar{position:fixed;inset:0 0 auto 0;height:52px;z-index:1000;background:var(--iron-edge);border-bottom:2px solid #000}
.iforge-nav{max-width:1300px;margin:0 auto;height:100%;padding:0 18px;display:flex;align-items:center;justify-content:space-between;gap:14px}
.iforge-nav-logo{font-family:var(--disp);font-weight:900;font-size:1.45rem;letter-spacing:1px;color:var(--paper);text-transform:uppercase;line-height:1;display:flex;align-items:center;gap:9px}
.iforge-nav-logo::before{content:"";width:11px;height:11px;background:var(--ember);box-shadow:0 0 10px var(--ember);flex:0 0 auto}
.iforge-nav-logo:hover{color:#fff}
.iforge-nav-links{display:flex;gap:2px}
.iforge-nav-link{font-family:var(--mono);font-size:.72rem;font-weight:700;color:var(--paper-dim);text-transform:uppercase;letter-spacing:1px;padding:7px 11px;border:1px solid transparent}
.iforge-nav-link:hover,.iforge-nav-link.on{color:var(--iron);background:var(--ember);border-color:#000}
.iforge-nav-right{display:flex;align-items:center;gap:10px}
.iforge-nav-cta{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--iron);background:var(--paper);padding:7px 12px;border:2px solid #000;transition:transform .12s,box-shadow .12s}
.iforge-nav-cta:hover{transform:translate(-1px,-1px);color:var(--iron);box-shadow:2px 2px 0 #000}
.iforge-user-menu{position:relative}
.iforge-user-btn{width:34px;height:34px;background:var(--iron-plate);border:2px solid #000;display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-weight:800;font-size:.85rem;color:var(--ember-hi);cursor:pointer}
.iforge-user-btn:hover{border-color:var(--ember)}
.iforge-dropdown{display:none;position:absolute;right:0;top:44px;background:var(--iron-plate);border:2px solid #000;min-width:200px;z-index:100}
.iforge-dropdown.open{display:block}
.iforge-dropdown-item{display:block;padding:10px 14px;font-family:var(--mono);font-size:.68rem;color:var(--paper-dim);border-bottom:1px solid var(--iron-edge)}
.iforge-dropdown-item:last-child{border-bottom:none}
.iforge-dropdown-item:hover{background:var(--iron-hi);color:var(--paper)}
.iforge-dropdown-divider{border:none;border-top:1px solid var(--iron-edge);margin:0}
.iforge-btn-ghost.iforge-btn-sm{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:7px 12px;border:2px solid var(--rivet);color:var(--paper);background:transparent}
.iforge-btn-ghost.iforge-btn-sm:hover{color:var(--iron);background:var(--paper);border-color:#000}
@media(max-width:640px){.iforge-nav-links{display:none}}

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

/* ---------------- TARJETAS ---------------- */
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
.card{border:2px solid #000;background:var(--iron-plate);display:flex;flex-direction:column;transition:transform .16s,box-shadow .16s}
.card:hover{transform:translate(-3px,-3px);box-shadow:6px 6px 0 #000}
.card-top{position:relative;padding:16px;border-bottom:2px solid #000;display:flex;align-items:center;gap:13px;background:linear-gradient(150deg,var(--iron-hi),var(--iron-edge))}
.card-ic{width:52px;height:52px;flex:0 0 auto;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.card-ic svg{width:26px;height:26px;stroke:var(--h6);fill:none;stroke-width:2}
.card-title{font-family:var(--disp);font-weight:800;font-size:1.5rem;text-transform:uppercase;line-height:.95;color:var(--paper)}
.card-code{font-family:var(--mono);font-size:.6rem;font-weight:700;color:var(--ember-hi);text-transform:uppercase;letter-spacing:1px;margin-top:3px}
.card-body{padding:14px 16px;flex:1;font-size:.86rem;color:var(--paper-dim);line-height:1.5}
.card-foot{padding:12px 16px;border-top:2px solid #000;display:flex;align-items:center;justify-content:space-between;gap:10px}
.card-meta{font-family:var(--mono);font-size:.62rem;color:var(--ash);text-transform:uppercase}
.card-meta b{color:var(--h6)}

/* ---------------- FOOTER ---------------- */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
.foot-links{display:flex;gap:16px;flex-wrap:wrap}
.foot-links a{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.foot-links a:hover{color:var(--ember-hi)}
.foot-c{font-family:var(--mono);font-size:.62rem;color:var(--ash)}

/* ---------------- UTILIDADES ---------------- */
.empty-note{font-family:var(--mono);font-size:.72rem;color:var(--paper-dim);text-align:center;padding:16px}
.foot-note{text-align:center;font-family:var(--mono);font-size:.62rem;color:var(--ash);margin-top:8px}
.reveal{opacity:0;transform:translateY(14px);transition:opacity .5s,transform .5s}
.reveal.vis{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}.reveal{opacity:1;transform:none}}

/* ---------------- LOCAL (solo var(--tokens)) ---------------- */
.guia-intro{font-size:.92rem;color:var(--paper-dim);max-width:70ch;margin:-6px 0 18px;line-height:1.55}
.guia-intro b{color:var(--paper);font-weight:600}
.empty-plate{border:2px dashed var(--rivet);background:var(--iron-plate);padding:34px 18px;text-align:center}
.empty-plate .big{font-family:var(--disp);font-weight:800;font-size:1.6rem;text-transform:uppercase;color:var(--paper);line-height:1;margin-bottom:8px}
.empty-plate p{font-family:var(--mono);font-size:.74rem;color:var(--paper-dim);line-height:1.6;max-width:52ch;margin:0 auto}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Fragua</a>
    <span class="sep">›</span>
    <b>Guías</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Guías</h1>
      <span class="code">// reglamento &amp; sistema</span>
      <span class="rule"></span>
    </div>
    <p class="guia-intro">Aquí se recopilarán las <b>guías oficiales</b> del taller: reglamento, sistema de stats, escala de calor E→M+ y creación de ficha paso a paso. Todo lo que necesitas para forjar y jugar.</p>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Índice de guías</span>
        <span class="c">// en preparación</span>
      </div>
      <div class="plate-b">
        <div class="empty-plate">
          <div class="big">Aún no hay guías publicadas</div>
          <p>Las guías se publicarán aquí en cuanto el taller las tenga listas. Mientras tanto, puedes registrarte y empezar a forjar tu personaje.</p>
        </div>
        <p class="empty-note">Las guías se publicarán aquí.</p>
      </div>
    </div>
  </section>

</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">I-Forge</div>
    <div class="foot-links"><a href="<?php echo $bburl; ?>/index.php">Fragua</a><a href="<?php echo $bburl; ?>/personajes.php">Personaje</a><a href="<?php echo $bburl; ?>/tramites.php">Tr&aacute;mites</a><a href="<?php echo $bburl; ?>/guias.php">Guías</a></div>
    <div class="foot-c">Dirección "foundry brutalism"</div>
  </div>
</footer>

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
