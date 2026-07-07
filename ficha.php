<?php
/**
 * I-Forge · Ficha de personaje ("Placa forjada")
 * ----------------------------------------------
 * Muestra el expediente real de un personaje (mybb_rol_personajes), leyendo
 * los datos guardados por el wizard crear-personaje.php. Dirección visual
 * "Foundry Brutalism", coherente con personajes.php.
 *
 * Acceso:
 *   ficha.php?pid=N   → ficha del personaje N
 *   ficha.php         → ficha del personaje ACTIVO del usuario autenticado
 *
 * Visibilidad:
 *   - Los expedientes APROBADOS son públicos.
 *   - El dueño ve siempre los suyos (aunque estén en revisión/rechazados).
 *   - El staff ve cualquiera.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'ficha.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/iforge_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Nivel de staff (expuesto por el plugin, con respaldo directo).
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

// Iniciales para el botón de usuario (navbar).
$display_name = (string) ($mybb->user['iforge_display_name'] ?? ($mybb->user['username'] ?? ''));
$display_name_e = htmlspecialchars_uni($display_name);

// ── Resolver el personaje a mostrar ──
$pid = $mybb->get_input('pid', MyBB::INPUT_INT);
if ($pid < 1 && $loggedin) {
    // Personaje activo del usuario.
    if ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $pid = (int) $db->fetch_field($cq, 'personaje_activo');
        }
    }
}

$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

// ── Control de acceso ──
$puede_ver = false;
if ($pj) {
    if ($pj['estado'] === 'aprobado') {
        $puede_ver = true;
    } elseif ($loggedin && ((int) $pj['uid'] === $uid || $staff_level >= 1)) {
        $puede_ver = true;
    }
}
$es_propietario = $pj && $loggedin && (int) $pj['uid'] === $uid;

// ── Datos de rol ──
$RANK_SCALE  = iforge_rol_rank_scale();      // letra => num
$RANK_BY_NUM = array_flip($RANK_SCALE);      // num => letra
$STAT_GROUPS = iforge_rol_stats();
$RAZAS       = iforge_rol_razas();
$FACCIONES   = iforge_rol_facciones();
$ARMAS       = iforge_rol_armas();

function iforge_heat_var(string $rango): string
{
    $map = array(
        'F' => '--h1', 'E' => '--h1', 'D' => '--h2', 'C' => '--h3', 'B' => '--h4',
        'A' => '--h5', 'S' => '--h6', 'SS' => '--h7', 'M' => '--h8', 'M+' => '--h9',
    );
    return $map[strtoupper(trim($rango))] ?? '--h1';
}
function iforge_heat_val(int $v): string
{
    if ($v < 1) $v = 1;
    if ($v > 9) $v = 9;
    return '--h' . $v;
}
/** Formato corto para cifras grandes de berries (4.850.000 → "4.9M"). */
function iforge_short_money(int $n): string
{
    $abs = abs($n);
    if ($abs >= 1000000) {
        return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    }
    if ($abs >= 1000) {
        return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    }
    return number_format($n, 0, ',', '.');
}

// Decodifica los JSON del personaje.
$datos      = $pj ? (json_decode((string) $pj['datos'], true) ?: array()) : array();
$inventario = $pj ? (json_decode((string) $pj['inventario'], true) ?: array()) : array();
$economia   = $pj ? (json_decode((string) $pj['economia'], true) ?: array()) : array();
$bio        = $pj ? (json_decode((string) $pj['bio'], true) ?: array()) : array();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; <?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Ficha'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#1b1d22; --iron-plate:#24272e; --iron-hi:#31353d; --iron-edge:#0d0e11;
  --rivet:#565b64;
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

/* NAVBAR */
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
.iforge-user-name{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--paper);background:var(--iron-plate);border:2px solid #000;padding:7px 12px;cursor:pointer;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.iforge-user-name:hover{border-color:var(--ember)}
.iforge-dropdown{display:none;position:absolute;right:0;top:44px;background:var(--iron-plate);border:2px solid #000;min-width:200px;z-index:100}
.iforge-dropdown.open{display:block}
.iforge-dropdown-item{display:block;padding:10px 14px;font-family:var(--mono);font-size:.68rem;color:var(--paper-dim);border-bottom:1px solid var(--iron-edge)}
.iforge-dropdown-item:last-child{border-bottom:none}
.iforge-dropdown-item:hover{background:var(--iron-hi);color:var(--paper)}
.iforge-dropdown-divider{border:none;border-top:1px solid var(--iron-edge);margin:0}
.iforge-btn-ghost.iforge-btn-sm{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:7px 12px;border:2px solid var(--rivet);color:var(--paper);background:transparent}
.iforge-btn-ghost.iforge-btn-sm:hover{color:var(--iron);background:var(--paper);border-color:#000}
@media(max-width:640px){.iforge-nav-links{display:none}}

/* BREADCRUMB */
.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1300px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

/* BOTONES */
.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:12px 20px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{background:var(--ember-hi);color:var(--iron);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:7px 13px;font-size:.7rem}

/* PLACAS */
.plate{border:2px solid #000;background:var(--iron-plate);margin-bottom:12px}
.plate-h{background:var(--iron-edge);padding:9px 13px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:2px solid #000}
.plate-h .t{font-family:var(--disp);font-weight:800;font-size:1.1rem;text-transform:uppercase;color:var(--paper);letter-spacing:.5px}
.plate-h .c{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.plate-b{padding:13px}

/* FOOTER */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
.foot-links{display:flex;gap:16px;flex-wrap:wrap}
.foot-links a{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.foot-links a:hover{color:var(--ember-hi)}
.foot-c{font-family:var(--mono);font-size:.62rem;color:var(--ash)}

/* LAYOUT FICHA */
.forge{display:grid;grid-template-columns:320px 1fr;gap:22px;align-items:start;padding-top:16px}
@media(max-width:960px){.forge{grid-template-columns:1fr;gap:14px}}
.pcol{position:sticky;top:66px}
@media(max-width:960px){.pcol{position:static}}
.forge-portrait{position:relative;border:3px solid #000;background:var(--iron-edge);overflow:hidden}
.fp-frame{position:relative;aspect-ratio:4/5;overflow:hidden;display:flex;align-items:center;justify-content:center}
.fp-glow{position:absolute;inset:0;background:radial-gradient(85% 60% at 50% 34%,rgba(239,139,30,.42),rgba(224,100,31,.12) 45%,transparent 70%)}
.fp-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:top center}
.fp-initial{position:relative;font-family:var(--disp);font-weight:900;font-size:7rem;color:var(--iron-hi);text-shadow:0 3px 0 #000;z-index:2}
.fp-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:22px 22px;pointer-events:none}
.fp-temper{position:absolute;left:0;top:0;bottom:0;width:9px;background:linear-gradient(to top,var(--h1),var(--h3),var(--h5),var(--h7),var(--h9));border-right:2px solid #000;z-index:3}
.fp-rank{position:absolute;top:0;right:0;z-index:4;font-family:var(--disp);font-weight:900;font-size:1.5rem;color:var(--iron);padding:4px 12px;border-left:2px solid #000;border-bottom:2px solid #000}
.fp-lv{position:absolute;z-index:4;left:9px;top:0;font-family:var(--mono);font-size:.58rem;font-weight:700;color:var(--iron);background:var(--h6);padding:3px 8px;border-right:2px solid #000;border-bottom:2px solid #000}
.fp-nameplate{position:absolute;left:0;right:0;bottom:0;z-index:4;background:linear-gradient(to top,var(--iron-edge),rgba(13,14,17,.86) 70%,transparent);padding:26px 12px 12px}
.fp-nameplate b{display:block;font-family:var(--disp);font-weight:900;font-size:1.9rem;line-height:.9;text-transform:uppercase;color:var(--paper);text-shadow:0 2px 0 #000}
.fp-nameplate span{font-family:var(--mono);font-size:.62rem;text-transform:uppercase;letter-spacing:.5px;color:var(--h6)}
.pcol .under{border:2px solid #000;border-top:none;background:var(--iron-plate);padding:12px}
.pcol .acts{display:flex;gap:7px}
.pcol .acts .btn{flex:1;text-align:center;padding:9px 6px;font-size:.66rem}
.estado-chip{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 9px;border:2px solid #000;color:var(--iron);display:inline-block;margin-bottom:10px}

/* IDENTIDAD */
.idbanner{margin-bottom:14px}
.eyebrow{font-family:var(--mono);font-size:.66rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--ember-hi);display:flex;flex-wrap:wrap;gap:6px 12px;margin-bottom:8px}
.eyebrow .sep{color:var(--rivet)}
.idbanner h1{font-family:var(--disp);font-weight:900;font-size:clamp(2.6rem,7vw,4.6rem);line-height:.85;letter-spacing:-1px;text-transform:uppercase;color:var(--paper);text-shadow:0 2px 0 #000}
.idbanner .desig{font-size:.92rem;color:var(--paper-dim);margin-top:10px}
.idtags{display:flex;flex-wrap:wrap;gap:6px;margin-top:14px}
.tag{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:4px 10px;border:2px solid #000}
.tag.rank{background:var(--ember);color:var(--iron)}
.tag.line{background:var(--iron);color:var(--paper);border-color:var(--rivet)}
.tag.act{background:var(--patina);color:var(--iron)}

/* CRISOL */
.pgroup{margin-bottom:14px}.pgroup:last-child{margin-bottom:0}
.pgroup-h{display:flex;align-items:center;gap:9px;margin-bottom:6px}
.pgroup-h .n{font-family:var(--disp);font-weight:800;font-size:1.05rem;text-transform:uppercase;letter-spacing:.5px}
.pgroup-h .bar{flex:1;height:2px;background:var(--rivet)}
.pgroup-h .avg{font-family:var(--mono);font-size:.6rem;color:var(--paper-dim)}
.stat{display:grid;grid-template-columns:44px 1fr 30px 34px;gap:9px;align-items:center;padding:5px 0}
.stat .ab{font-family:var(--mono);font-size:.66rem;font-weight:700;color:var(--paper-dim)}
.stat .track{height:18px;background:var(--iron);border:2px solid #000;position:relative;overflow:hidden}
.stat .fill{height:100%;transition:width .9s cubic-bezier(.2,.8,.2,1)}
.stat .nm{position:absolute;left:7px;top:50%;transform:translateY(-50%);font-family:var(--body);font-size:.68rem;font-weight:600;color:var(--paper);mix-blend-mode:difference;white-space:nowrap}
.stat .rk{font-family:var(--disp);font-weight:900;font-size:1.15rem;text-align:center;line-height:1}
.stat .vl{font-family:var(--mono);font-size:.72rem;font-weight:700;color:var(--paper);text-align:right}

/* GRID de 2 columnas para paneles */
.cols{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}
@media(max-width:820px){.cols{grid-template-columns:1fr}}

/* filas simples */
.mrow{display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid var(--iron-edge);font-size:.82rem}
.mrow:last-child{border-bottom:none}
.mrow .l{font-family:var(--mono);font-size:.64rem;text-transform:uppercase;letter-spacing:.5px;color:var(--paper-dim)}
.mrow .v{font-weight:600;color:var(--paper);text-align:right}

/* rasgos (virtudes/defectos) */
.trait{display:flex;gap:9px;padding:8px 0;border-bottom:1px solid var(--iron-edge)}
.trait:last-child{border-bottom:none}
.trait .d{width:9px;height:9px;margin-top:5px;flex:0 0 auto}
.trait .d.v{background:var(--patina-hi)}.trait .d.x{background:var(--crack)}
.trait .b{font-weight:600;color:var(--paper);font-size:.82rem}
.trait small{display:block;color:var(--paper-dim);font-size:.74rem;line-height:1.45;margin-top:2px}
.trait .id{margin-left:auto;font-family:var(--mono);font-size:.58rem;font-weight:700;color:var(--iron);background:var(--h6);padding:2px 6px;height:fit-content;white-space:nowrap}
.trait .id.x{background:var(--crack);color:var(--paper)}

/* economía */
.coin{display:flex;align-items:center;gap:9px;background:var(--iron);border:2px solid #000;padding:9px 12px;font-family:var(--mono);font-size:.8rem}
.coin .dot{width:14px;height:14px;border:2px solid #000;flex:0 0 auto;background:var(--h6)}
.coin b{margin-left:auto;font-family:var(--disp);font-weight:800;font-size:1.2rem;color:var(--h6)}

/* prosa bio */
.prose p{margin-bottom:11px;font-size:.92rem;color:var(--paper);line-height:1.65;white-space:pre-line}.prose p:last-child{margin-bottom:0}
.prose .lead{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--h6);margin-bottom:6px}

/* estado vacío */
.pj-empty{border:2px dashed var(--rivet);background:var(--iron-plate);padding:40px 22px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:14px;margin-top:16px}
.pj-empty .big{font-family:var(--disp);font-weight:800;font-size:1.9rem;text-transform:uppercase;color:var(--paper);line-height:1}
.pj-empty p{font-family:var(--mono);font-size:.76rem;color:var(--paper-dim);line-height:1.6;max-width:54ch}
.pj-empty .acts{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:4px}

/* MEDALLONES sobre el retrato */
.fp-vitals{position:absolute;z-index:5;right:8px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:7px}
.fp-med{width:56px;height:56px;border-radius:50%;background:var(--iron);border:3px solid #000;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:inset 0 0 12px rgba(0,0,0,.6)}
.fp-med .v{font-family:var(--disp);font-weight:900;font-size:1.05rem;line-height:.9;color:var(--h6)}
.fp-med .l{font-family:var(--mono);font-size:.44rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim);margin-top:1px}

/* RESUMEN (temperatura de forja + medias por pilar) */
.summary{display:grid;grid-template-columns:auto repeat(3,1fr);gap:8px;margin-top:14px}
.sum-temp{border:2px solid #000;background:var(--iron);padding:8px 14px;display:flex;flex-direction:column;justify-content:center}
.sum-temp .l{font-family:var(--mono);font-size:.54rem;text-transform:uppercase;color:var(--paper-dim)}
.sum-temp .big{font-family:var(--disp);font-weight:900;font-size:2.1rem;line-height:.9}
.sum-cell{border:2px solid #000;background:var(--iron-plate);padding:7px 9px;position:relative}
.sum-cell::after{content:"";position:absolute;top:4px;right:4px;width:4px;height:4px;background:var(--rivet)}
.sum-cell .l{font-family:var(--mono);font-size:.54rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.sum-cell .v{font-family:var(--disp);font-weight:800;font-size:1.35rem;line-height:1;color:var(--h6)}
.sum-cell .f{font-family:var(--mono);font-size:.5rem;color:var(--ash)}
@media(max-width:560px){.summary{grid-template-columns:1fr 1fr}}

/* ESCALA DE CALOR (rangos reales F..M+) */
.heatscale{display:flex;gap:2px;margin:14px 0}
.hs{flex:1;text-align:center;font-family:var(--mono);font-size:.56rem;font-weight:700;color:var(--iron);padding:5px 2px;border:2px solid #000;opacity:.55}
.hs b{display:block;font-family:var(--disp);font-weight:900;font-size:.82rem;line-height:1.1}
.hs.on{opacity:1;outline:2px solid var(--paper);outline-offset:-4px}

/* TABS */
.tabs{display:flex;flex-wrap:wrap;gap:2px;border-bottom:2px solid #000;margin:18px 0 14px}
.tab{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:9px 16px;background:var(--iron-plate);color:var(--paper-dim);border:2px solid #000;border-bottom:none;cursor:pointer;position:relative;top:2px}
.tab:hover{color:var(--paper)}
.tab[aria-selected="true"]{background:var(--ember);color:var(--iron);top:0}
.panel{display:none}
.panel.on{display:block}

/* CRISOL: layout expositor + temperatura de forja */
.expo{display:grid;grid-template-columns:1fr 260px;gap:12px;align-items:start}
@media(max-width:1080px){.expo{grid-template-columns:1fr}}
.forge-temp{margin-top:12px;border:2px solid #000;background:var(--iron);padding:10px 12px;display:flex;align-items:center;gap:12px}
.forge-temp .lbl{font-family:var(--mono);font-size:.62rem;text-transform:uppercase;color:var(--paper-dim)}
.forge-temp .big{font-family:var(--disp);font-weight:900;font-size:2rem;line-height:1}
.forge-temp .meter{flex:1;height:12px;border:2px solid #000;background:var(--iron-edge);overflow:hidden}
.forge-temp .meter i{display:block;height:100%;background:linear-gradient(90deg,var(--h1),var(--h3),var(--h5),var(--h7),var(--h9))}

/* CRÓNICA */
.bio{display:grid;grid-template-columns:220px 1fr;gap:12px}
@media(max-width:820px){.bio{grid-template-columns:1fr}}
.subtabs{display:flex;flex-wrap:wrap;border:2px solid #000;margin-bottom:11px;width:fit-content}
.subtab{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;padding:6px 12px;background:var(--iron-plate);color:var(--paper-dim);cursor:pointer;border-left:2px solid #000}
.subtab:first-child{border-left:none}
.subtab[aria-selected="true"]{background:var(--ember);color:var(--iron)}
.tl{display:flex;flex-direction:column}
.tl-i{position:relative;padding:5px 0 5px 15px;font-size:.78rem;border-left:2px solid var(--rivet)}
.tl-i:last-child{border-left-color:transparent}
.tl-i::before{content:"";position:absolute;left:-5px;top:8px;width:8px;height:8px;background:var(--h5);border:2px solid #000}
.tl-i b{display:block;font-family:var(--mono);color:var(--h6);font-size:.62rem}
.tl-i span{color:var(--paper-dim)}

/* COMBATE: pasivas raciales */
.of{display:flex;align-items:flex-start;gap:9px;background:var(--iron);border:2px solid #000;padding:9px 11px;margin-bottom:7px}
.of .ico{width:32px;height:32px;flex:0 0 auto;background:var(--iron-plate);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.of .ico svg{width:16px;height:16px;stroke:var(--h6);fill:none;stroke-width:2}
.of .body{flex:1;min-width:0}
.of .n{font-family:var(--body);font-weight:700;font-size:.84rem;color:var(--paper)}
.of .n small{display:block;font-family:var(--mono);font-size:.68rem;font-weight:400;color:var(--paper-dim);margin-top:3px;line-height:1.4}
.of .lv{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--iron);background:var(--h6);padding:2px 7px;white-space:nowrap;height:fit-content}

/* EQUIPO */
.slot{display:flex;align-items:center;gap:11px;background:var(--iron);border:2px solid #000;padding:11px 13px;margin-bottom:8px}
.slot .ic{width:34px;height:34px;flex:0 0 auto;background:var(--iron-plate);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.slot .ic svg{width:17px;height:17px;stroke:var(--h6);fill:none;stroke-width:2}
.slot .b{font-family:var(--mono);font-size:.62rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.slot .s{color:var(--paper);font-weight:600;font-size:.86rem}
.slot.empty{border-style:dashed}.slot.empty .s{color:var(--ash);font-style:italic;font-weight:400}.slot.empty .ic svg{stroke:var(--ash)}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Fragua</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b><?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Ficha'; ?></b>
  </div>
</div>

<div class="wrap">
<?php if (!$pj || !$puede_ver):
    // ── Estado vacío / sin permiso ──
?>
  <div class="pj-empty">
    <div class="big"><?php echo $pj ? 'Expediente no disponible' : 'Expediente no encontrado'; ?></div>
    <p>
<?php if (!$pj): ?>
      No hay ning&uacute;n personaje con ese identificador. Puede que a&uacute;n no lo hayas forjado o que el enlace sea incorrecto.
<?php else: ?>
      Este expediente est&aacute; en revisi&oacute;n o no es p&uacute;blico. Solo su due&ntilde;o y el staff pueden consultarlo.
<?php endif; ?>
    </p>
    <div class="acts">
      <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Mis personajes</a>
      <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver a la fragua</a>
    </div>
  </div>
<?php else:
    // ── Datos derivados para el render ──
    $nombre_e   = htmlspecialchars_uni($pj['nombre']);
    $rango      = (string) $pj['rango'];
    $rango_e    = htmlspecialchars_uni($rango);
    $heat_rank  = iforge_heat_var($rango);
    $nivel      = (int) $pj['nivel'];
    $avatar     = trim((string) $pj['avatar']);
    $av_initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));

    $raza1_key = $datos['raza_principal'] ?? '';
    $raza2_key = $datos['raza_secundaria'] ?? '';
    $hibrido   = !empty($datos['hibrido']);
    $raza1_lbl = isset($RAZAS[$raza1_key]) ? $RAZAS[$raza1_key]['nombre'] : ucfirst((string) $raza1_key);
    $raza2_lbl = ($raza2_key && isset($RAZAS[$raza2_key])) ? $RAZAS[$raza2_key]['nombre'] : '';
    $raza_full = $hibrido && $raza2_lbl !== '' ? ($raza1_lbl . ' / ' . $raza2_lbl) : $raza1_lbl;

    $faccion_key = $datos['faccion'] ?? '';
    $faccion_lbl = isset($FACCIONES[$faccion_key]) ? $FACCIONES[$faccion_key]['nombre'] : ucfirst((string) $faccion_key);

    $edad   = $datos['edad'] ?? '';
    $genero = $datos['genero'] ?? '';
    $apodo  = $datos['apodo'] ?? '';

    $stats_ef = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
    $suma     = (int) ($datos['rango_suma'] ?? array_sum($stats_ef));

    $virtudes = is_array($datos['virtudes'] ?? null) ? $datos['virtudes'] : array();
    $defectos = is_array($datos['defectos'] ?? null) ? $datos['defectos'] : array();
    $pc_bal   = (int) ($datos['pc_balance'] ?? 0);
    $pc_gas   = (int) ($datos['pc_gastado'] ?? 0);
    $pc_dev   = (int) ($datos['pc_devuelto'] ?? 0);

    $arma_key = $inventario['arma'] ?? '';
    $arma_lbl = isset($ARMAS[$arma_key]) ? $ARMAS[$arma_key]['nombre'] : ucfirst((string) $arma_key);
    $arma_det = isset($ARMAS[$arma_key]) ? $ARMAS[$arma_key]['detalle'] : '';
    $objeto   = $inventario['objeto_personal'] ?? '';
    $berries  = (int) ($economia['berries'] ?? 0);

    // Medias por pilar (Cuerpo/Mente/Espíritu) y temperatura de forja global,
    // reutilizadas tanto en la barra de resumen como en la pestaña Crisol.
    $group_calc = array();
    $all_vals   = array();
    foreach ($STAT_GROUPS as $gkey => $grupo) {
        $vals = array();
        foreach ($grupo['stats'] as $ab => $nm) {
            $v = (int) ($stats_ef[$ab] ?? 1);
            $vals[] = $v;
            $all_vals[] = $v;
        }
        $g_avg = count($vals) ? array_sum($vals) / count($vals) : 1;
        $group_calc[$gkey] = array(
            'avg'    => $g_avg,
            'letter' => $RANK_BY_NUM[(int) round($g_avg)] ?? 'F',
            'heat'   => iforge_heat_val((int) round($g_avg)),
        );
    }
    $forge_avg    = count($all_vals) ? array_sum($all_vals) / count($all_vals) : 1;
    $forge_letter = $RANK_BY_NUM[(int) round($forge_avg)] ?? 'F';
    $forge_heat   = iforge_heat_val((int) round($forge_avg));

    // Pasivas raciales: la primaria de la raza principal siempre se aplica;
    // la secundaria de esa misma raza SOLO si el personaje es puro (no
    // híbrido). Si es híbrido, se suma la primaria de la raza secundaria
    // (regla usada por el propio wizard de creación: "un híbrido obtiene
    // SOLO las pasivas primarias de ambas razas, ninguna secundaria").
    $pasivas = array();
    if ($raza1_key !== '' && isset($RAZAS[$raza1_key])) {
        $r1 = $RAZAS[$raza1_key];
        $pasivas[] = array('tag' => 'Primaria · ' . $raza1_lbl, 'nombre' => $r1['primaria_nombre'], 'desc' => $r1['primaria_desc']);
        if ($hibrido) {
            if ($raza2_key !== '' && isset($RAZAS[$raza2_key])) {
                $r2 = $RAZAS[$raza2_key];
                $pasivas[] = array('tag' => 'Primaria · ' . $raza2_lbl, 'nombre' => $r2['primaria_nombre'], 'desc' => $r2['primaria_desc']);
            }
        } else {
            $pasivas[] = array('tag' => 'Secundaria · ' . $raza1_lbl, 'nombre' => $r1['secundaria_nombre'], 'desc' => $r1['secundaria_desc']);
        }
    }

    // Rasgos: virtudes y defectos combinados en una sola lista (.trait).
    $rasgos = array();
    foreach ($virtudes as $vid => $v) {
        $vdef = iforge_rol_find_virtud($vid);
        $rasgos[] = array(
            'tipo'  => 'v',
            'nombre' => $v['nombre'] ?? $vid,
            'spec'   => trim((string) ($v['spec'] ?? '')),
            'desc'   => $vdef ? $vdef['desc'] : '',
            'badge'  => ((int) ($v['coste'] ?? 0)) . ' PC',
        );
    }
    foreach ($defectos as $did => $d) {
        $ddef = iforge_rol_find_defecto($did);
        $rasgos[] = array(
            'tipo'  => 'x',
            'nombre' => $d['nombre'] ?? $did,
            'spec'   => trim((string) ($d['spec'] ?? '')),
            'desc'   => $ddef ? $ddef['desc'] : '',
            'badge'  => '+' . ((int) ($d['devuelve'] ?? 0)),
        );
    }

    // Cronología: solo eventos reales (forjado + última edición si difiere).
    $timeline = array();
    $timeline[] = array('t' => 'Forjado', 'd' => my_date('d M Y', (int) $pj['dateline']));
    $lastedit_ts = (int) ($pj['lastedit'] ?? 0);
    if ($lastedit_ts > 0 && $lastedit_ts !== (int) $pj['dateline']) {
        $timeline[] = array('t' => '&Uacute;ltima edici&oacute;n', 'd' => my_date('d M Y', $lastedit_ts));
    }

    // Crónica: solo se muestran los subtabs con contenido real.
    $bio_map = array(
        'concepto'   => 'Concepto',
        'pasado'     => 'Pasado',
        'motivacion' => 'Motivaci&oacute;n',
        'relaciones' => 'Relaciones',
    );
    $bio_sections = array();
    foreach ($bio_map as $bkey => $blabel) {
        $btext = trim((string) ($bio[$bkey] ?? ''));
        if ($btext !== '') {
            $bio_sections[$bkey] = array('label' => $blabel, 'text' => $btext);
        }
    }

    list($est_lbl, $est_col) = (function ($e) {
        switch ($e) {
            case 'aprobado':  return array('Aprobado', 'var(--patina)');
            case 'revision':  return array('En revisi&oacute;n', 'var(--h6)');
            case 'rechazado': return array('Rechazado', 'var(--crack)');
            default:          return array('Borrador', 'var(--rivet)');
        }
    })((string) $pj['estado']);
?>

<div class="forge">
  <!-- COLUMNA RETRATO -->
  <div class="pcol">
    <div class="forge-portrait">
      <div class="fp-frame">
        <div class="fp-glow" aria-hidden="true"></div>
<?php if ($avatar !== ''): ?>
        <img class="fp-img" src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="<?php echo $nombre_e; ?>">
<?php else: ?>
        <span class="fp-initial"><?php echo htmlspecialchars_uni($av_initial); ?></span>
<?php endif; ?>
        <div class="fp-grid" aria-hidden="true"></div>
        <span class="fp-temper" style="background:var(<?php echo $heat_rank; ?>)" aria-hidden="true"></span>
        <span class="fp-lv">Nivel <?php echo $nivel; ?></span>
        <span class="fp-rank" style="background:var(<?php echo $heat_rank; ?>)"><?php echo $rango_e; ?></span>
        <div class="fp-vitals" aria-hidden="true">
          <div class="fp-med"><span class="v"><?php echo $rango_e; ?></span><span class="l">Rango</span></div>
          <div class="fp-med"><span class="v"><?php echo $nivel; ?></span><span class="l">Nivel</span></div>
          <div class="fp-med"><span class="v"><?php echo $pc_bal; ?></span><span class="l">PC</span></div>
          <div class="fp-med"><span class="v"><?php echo htmlspecialchars_uni(iforge_short_money($berries)); ?></span><span class="l">Berries</span></div>
        </div>
        <div class="fp-nameplate">
          <b><?php echo $nombre_e; ?></b>
          <span><?php echo htmlspecialchars_uni($raza_full); ?><?php echo $faccion_lbl !== '' ? ' &middot; ' . htmlspecialchars_uni($faccion_lbl) : ''; ?></span>
        </div>
      </div>
    </div>
    <div class="under">
      <span class="estado-chip" style="background:<?php echo $est_col; ?>;<?php echo $est_col === 'var(--crack)' ? 'color:var(--paper)' : ''; ?>"><?php echo $est_lbl; ?></span>
<?php if ($es_propietario): ?>
      <div class="acts">
        <a class="btn btn-hot" href="<?php echo $bburl; ?>/personajes.php">Mis personajes</a>
      </div>
<?php endif; ?>
    </div>
  </div>

  <!-- COLUMNA CONTENIDO -->
  <div>
    <div class="idbanner">
      <div class="eyebrow">
        <span>Expediente N.&ordm; <?php echo str_pad((string) $pj['pid'], 5, '0', STR_PAD_LEFT); ?></span>
        <span class="sep">&middot;</span>
        <span>Forjado <?php echo my_date('d M Y', (int) $pj['dateline']); ?></span>
      </div>
      <h1><?php echo $nombre_e; ?></h1>
      <p class="desig">
        <?php echo htmlspecialchars_uni($raza_full); ?>
<?php if ($apodo !== ''): ?> &middot; &laquo;<?php echo htmlspecialchars_uni($apodo); ?>&raquo;<?php endif; ?>
<?php if ($genero !== ''): ?> &middot; <?php echo htmlspecialchars_uni(ucfirst((string) $genero)); ?><?php endif; ?>
<?php if ($edad !== ''): ?> &middot; <?php echo htmlspecialchars_uni((string) $edad); ?> a&ntilde;os<?php endif; ?>
      </p>
      <div class="idtags">
        <span class="tag rank">Rango <?php echo $rango_e; ?> &middot; Nivel <?php echo $nivel; ?></span>
<?php if ($faccion_lbl !== ''): ?>
        <span class="tag line"><?php echo htmlspecialchars_uni($faccion_lbl); ?></span>
<?php endif; ?>
<?php if ((int) $pj['activo'] === 1): ?>
        <span class="tag act">&#9670; Personaje activo</span>
<?php endif; ?>
      </div>
      <div class="summary">
        <div class="sum-temp"><span class="l">Temp. forja</span><span class="big" style="color:var(<?php echo $forge_heat; ?>)"><?php echo htmlspecialchars_uni($forge_letter); ?></span></div>
<?php foreach ($STAT_GROUPS as $gkey => $grupo): $gc = $group_calc[$gkey]; ?>
        <div class="sum-cell">
          <div class="l"><?php echo htmlspecialchars_uni($grupo['label']); ?></div>
          <div class="v" style="color:var(<?php echo $gc['heat']; ?>)"><?php echo htmlspecialchars_uni($gc['letter']); ?></div>
          <div class="f">media <?php echo number_format($gc['avg'], 2); ?></div>
        </div>
<?php endforeach; ?>
      </div>
    </div>

    <div class="heatscale" aria-label="Escala de calor equivalente al rango, F a M+">
<?php foreach ($RANK_SCALE as $letra => $num):
        $hs_on = (strcasecmp($rango, $letra) === 0) ? ' on' : '';
        $hs_heat = iforge_heat_var($letra);
?>
      <div class="hs<?php echo $hs_on; ?>" style="background:var(<?php echo $hs_heat; ?>)"><b><?php echo htmlspecialchars_uni($letra); ?></b><?php echo $num; ?></div>
<?php endforeach; ?>
    </div>

    <div class="tabs" role="tablist" aria-label="Secciones del expediente">
      <button type="button" class="tab" role="tab" aria-selected="true" data-tab="crisol">Crisol</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="cronica">Cr&oacute;nica</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="combate">Combate</button>
      <button type="button" class="tab" role="tab" aria-selected="false" data-tab="equipo">Equipo</button>
    </div>

    <!-- CRISOL -->
    <section class="panel on" id="tab-crisol" role="tabpanel">
      <div class="expo">
        <div class="plate">
          <div class="plate-h"><span class="t">El Crisol</span><span class="c">stats efectivas &middot; suma <?php echo $suma; ?></span></div>
          <div class="plate-b">
<?php foreach ($STAT_GROUPS as $gkey => $grupo):
            $rows = $grupo['stats'];
            $gc   = $group_calc[$gkey];
?>
            <div class="pgroup">
              <div class="pgroup-h">
                <span class="n" style="color:var(<?php echo $gc['heat']; ?>)"><?php echo htmlspecialchars_uni($grupo['label']); ?></span>
                <span class="bar"></span>
                <span class="avg">media <?php echo number_format($gc['avg'], 2); ?></span>
              </div>
<?php foreach ($rows as $ab => $nm):
              $v = (int) ($stats_ef[$ab] ?? 1);
              $letter = $RANK_BY_NUM[$v] ?? 'F';
              $heat = iforge_heat_val($v);
              $pct = max(6, min(100, ($v / 10) * 100));
?>
              <div class="stat">
                <span class="ab"><?php echo htmlspecialchars_uni($ab); ?></span>
                <div class="track"><div class="fill" style="width:<?php echo $pct; ?>%;background:var(<?php echo $heat; ?>)"></div><span class="nm"><?php echo htmlspecialchars_uni($nm); ?></span></div>
                <span class="rk" style="color:var(<?php echo $heat; ?>)"><?php echo htmlspecialchars_uni($letter); ?></span>
                <span class="vl"><?php echo $v; ?></span>
              </div>
<?php endforeach; ?>
            </div>
<?php endforeach; ?>
            <div class="forge-temp">
              <div><div class="lbl">Temperatura de forja</div><div class="big" style="color:var(<?php echo $forge_heat; ?>)"><?php echo htmlspecialchars_uni($forge_letter); ?></div></div>
              <div class="meter"><i style="width:<?php echo max(6, min(100, ($forge_avg / 10) * 100)); ?>%"></i></div>
              <div class="lbl"><?php echo number_format($forge_avg, 2); ?> / 10</div>
            </div>
          </div>
        </div>
        <div>
          <div class="plate">
            <div class="plate-h"><span class="t">Bolsa</span></div>
            <div class="plate-b">
              <div class="coin"><span class="dot"></span>Berries<b><?php echo number_format($berries, 0, ',', '.'); ?></b></div>
              <div class="mrow" style="margin-top:10px"><span class="l">Balance de PC</span><span class="v" style="color:var(--h6)"><?php echo $pc_bal; ?> sin gastar</span></div>
            </div>
          </div>
<?php if ($pc_bal > 0): ?>
          <div class="plate">
            <div class="plate-h"><span class="t">Sin repartir</span></div>
            <div class="plate-b">
              <div class="mrow"><span class="l">Puntos de creaci&oacute;n</span><span class="v" style="font-family:var(--mono);color:var(--h6)"><?php echo $pc_bal; ?></span></div>
            </div>
          </div>
<?php endif; ?>
        </div>
      </div>
    </section>

    <!-- CRÓNICA -->
    <section class="panel" id="tab-cronica" role="tabpanel">
      <div class="bio">
        <aside>
          <div class="plate">
            <div class="plate-h"><span class="t">R&aacute;pido</span></div>
            <div class="plate-b">
<?php if ($edad !== ''): ?>
              <div class="mrow"><span class="l">Edad</span><span class="v"><?php echo htmlspecialchars_uni((string) $edad); ?></span></div>
<?php endif; ?>
<?php if ($genero !== ''): ?>
              <div class="mrow"><span class="l">G&eacute;nero</span><span class="v"><?php echo htmlspecialchars_uni(ucfirst((string) $genero)); ?></span></div>
<?php endif; ?>
              <div class="mrow"><span class="l">Raza</span><span class="v"><?php echo htmlspecialchars_uni($raza_full); ?></span></div>
              <div class="mrow"><span class="l">Estado</span><span class="v" style="color:<?php echo $est_col; ?>"><?php echo $est_lbl; ?></span></div>
            </div>
          </div>
        </aside>
        <div>
<?php if (empty($bio_sections)): ?>
          <div class="plate"><div class="plate-b prose"><p class="mono" style="color:var(--paper-dim)">Sin cr&oacute;nica registrada todav&iacute;a.</p></div></div>
<?php else:
            $bio_first = true;
?>
          <div class="subtabs" role="tablist" aria-label="Secciones de la cr&oacute;nica">
<?php foreach ($bio_sections as $bkey => $bsec): ?>
            <button type="button" class="subtab" role="tab" aria-selected="<?php echo $bio_first ? 'true' : 'false'; ?>" data-bio="<?php echo htmlspecialchars_uni($bkey); ?>"><?php echo $bsec['label']; ?></button>
<?php $bio_first = false; endforeach; ?>
          </div>
<?php
            $bio_first = true;
            foreach ($bio_sections as $bkey => $bsec):
?>
          <div class="plate"><div class="plate-b prose" data-bio-c="<?php echo htmlspecialchars_uni($bkey); ?>"<?php echo $bio_first ? '' : ' hidden'; ?>>
            <p><?php echo nl2br(htmlspecialchars_uni($bsec['text'])); ?></p>
          </div></div>
<?php
                $bio_first = false;
            endforeach;
          endif;
?>

          <div class="plate">
            <div class="plate-h"><span class="t">Rasgos</span><span class="c">// <?php echo $pc_gas; ?> PC &middot; +<?php echo $pc_dev; ?> PC</span></div>
            <div class="plate-b">
<?php if (empty($rasgos)): ?>
              <p class="mono" style="font-size:.76rem;color:var(--paper-dim)">Sin rasgos registrados.</p>
<?php else: foreach ($rasgos as $rasgo): ?>
              <div class="trait">
                <span class="d <?php echo $rasgo['tipo']; ?>"></span>
                <div>
                  <span class="b"><?php echo htmlspecialchars_uni($rasgo['nombre']); ?><?php echo $rasgo['spec'] !== '' ? ' &mdash; <em style="color:var(--paper-dim);font-style:italic">' . htmlspecialchars_uni($rasgo['spec']) . '</em>' : ''; ?></span>
<?php if ($rasgo['desc'] !== ''): ?><small><?php echo htmlspecialchars_uni($rasgo['desc']); ?></small><?php endif; ?>
                </div>
                <span class="id<?php echo $rasgo['tipo'] === 'x' ? ' x' : ''; ?>"><?php echo htmlspecialchars_uni($rasgo['badge']); ?></span>
              </div>
<?php endforeach; endif; ?>
            </div>
          </div>

          <div class="plate">
            <div class="plate-h"><span class="t">L&iacute;nea de tiempo</span></div>
            <div class="plate-b"><div class="tl">
<?php foreach ($timeline as $ev): ?>
              <div class="tl-i"><b><?php echo $ev['t']; ?></b><span><?php echo $ev['d']; ?></span></div>
<?php endforeach; ?>
            </div></div>
          </div>
        </div>
      </div>
    </section>

    <!-- COMBATE -->
    <section class="panel" id="tab-combate" role="tabpanel">
      <div class="plate">
        <div class="plate-h"><span class="t">Pasivas raciales</span></div>
        <div class="plate-b">
<?php if (empty($pasivas)): ?>
          <p class="mono" style="font-size:.76rem;color:var(--paper-dim)">Sin raza asignada.</p>
<?php else: foreach ($pasivas as $pas): ?>
          <div class="of">
            <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M13 2 3 14h7l-1 8 11-14h-7z"/></svg></span>
            <div class="body">
              <div class="n"><?php echo htmlspecialchars_uni($pas['nombre']); ?><small><?php echo htmlspecialchars_uni($pas['desc']); ?></small></div>
            </div>
            <span class="lv"><?php echo htmlspecialchars_uni($pas['tag']); ?></span>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>
      <div class="plate">
        <div class="plate-h"><span class="t">Arma equipada</span></div>
        <div class="plate-b">
          <div class="mrow"><span class="l">Arma</span><span class="v"><?php echo htmlspecialchars_uni($arma_lbl); ?></span></div>
<?php if ($arma_det !== ''): ?>
          <div class="mrow"><span class="l">Detalle</span><span class="v" style="font-family:var(--mono);font-size:.72rem"><?php echo htmlspecialchars_uni($arma_det); ?></span></div>
<?php endif; ?>
        </div>
      </div>
    </section>

    <!-- EQUIPO -->
    <section class="panel" id="tab-equipo" role="tabpanel">
      <div class="plate">
        <div class="plate-h"><span class="t">Equipo</span></div>
        <div class="plate-b">
          <div class="slot">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14.5 17.5L3 6V3h3l11.5 11.5"/><path d="M13 19l6-6"/></svg></span>
            <div><div class="b">Arma</div><span class="s"><?php echo htmlspecialchars_uni($arma_lbl); ?></span></div>
          </div>
<?php if (trim((string) $objeto) !== ''): ?>
          <div class="slot">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18"/></svg></span>
            <div><div class="b">Objeto personal</div><span class="s"><?php echo htmlspecialchars_uni($objeto); ?></span></div>
          </div>
<?php else: ?>
          <div class="slot empty">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18"/></svg></span>
            <div><div class="b">Objeto personal</div><span class="s">Sin equipar</span></div>
          </div>
<?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</div>
<?php endif; ?>
</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">I-Forge</div>
    <div class="foot-links"><a href="<?php echo $bburl; ?>/index.php">Fragua</a><a href="<?php echo $bburl; ?>/personajes.php">Personaje</a><a href="<?php echo $bburl; ?>/tramites.php">Tr&aacute;mites</a><a href="<?php echo $bburl; ?>/guias.php">Gu&iacute;as</a></div>
    <div class="foot-c">Direcci&oacute;n "foundry brutalism"</div>
  </div>
</footer>

<script>
document.querySelectorAll('.tab').forEach(function (t) {
  t.addEventListener('click', function () {
    document.querySelectorAll('.tab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('on'); });
    t.setAttribute('aria-selected', 'true');
    var panel = document.getElementById('tab-' + t.dataset.tab);
    if (panel) panel.classList.add('on');
  });
});
document.querySelectorAll('.subtab').forEach(function (s) {
  s.addEventListener('click', function () {
    document.querySelectorAll('.subtab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    s.setAttribute('aria-selected', 'true');
    document.querySelectorAll('[data-bio-c]').forEach(function (c) { c.hidden = (c.dataset.bioC !== s.dataset.bio); });
  });
});
</script>

</body>
</html>
