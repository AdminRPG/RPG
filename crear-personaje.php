<?php
/**
 * I-Forge · Forjar personaje (wizard de creación)
 * Página de front-end MyBB (dirección "Foundry Brutalism").
 *
 * Wizard de un único envío (sin borradores intermedios) que sigue los
 * 7 pasos de one-piece-eternal-sistemas/01-creacion-de-personaje.md:
 * raza, concepto, stats, virtudes/defectos, facción, equipo, historia.
 *
 * Al enviar: valida TODO en servidor contra inc/iforge_rol_data.php
 * (nunca confía en lo que calculó el JS), inserta en mybb_rol_personajes
 * con estado=revision y abre un trámite en mybb_rol_tramites para que el
 * staff lo apruebe desde "Mi expediente".
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/iforge_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

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

$RAZAS      = iforge_rol_razas();
$VIRTUDES   = iforge_rol_virtudes();
$DEFECTOS   = iforge_rol_defectos();
$FACCIONES  = iforge_rol_facciones();
$ARMAS      = iforge_rol_armas();
$STATS      = iforge_rol_stats();
$STAT_KEYS  = iforge_rol_stat_keys();
$PC_BASE    = iforge_rol_pc_iniciales();
$BERRIES_BASE = iforge_rol_berries_iniciales();

// ─────────────────────────────────────────────────────────────
// Slots disponibles
// ─────────────────────────────────────────────────────────────
$slots = 1;
$usados = 0;
if ($loggedin && $db->table_exists('rol_cuentas')) {
    $sq = $db->simple_select('rol_cuentas', 'slots', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($sq)) {
        $slots = (int)$db->fetch_field($sq, 'slots');
    }
}
if ($loggedin && $db->table_exists('rol_personajes')) {
    $uq = $db->simple_select('rol_personajes', 'COUNT(*) AS c', "uid = {$uid} AND estado != 'rechazado'");
    $urow = $db->fetch_array($uq);
    $usados = (int)($urow['c'] ?? 0);
}
$hay_hueco = $usados < $slots;

// ── Detección de edición de ficha moderada ──
$editando_pid = (int)($mybb->get_input('editar', MyBB::INPUT_INT));
$editando = null;
if ($editando_pid > 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $eq = $db->simple_select('rol_personajes', '*', "pid = {$editando_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($eq)) {
        $editando = $db->fetch_array($eq);
        if ($db->table_exists('rol_mensajes')) {
            $db->update_query('rol_mensajes', array('leido' => 1), "destino_pid = {$editando_pid} AND asunto LIKE 'Moderación:%'");
        }
    }
}

// ─────────────────────────────────────────────────────────────
// POST: validar y crear
// ─────────────────────────────────────────────────────────────
$errores = array();
$ok = false;
$old = $_POST;

function iforge_rol_clean($s, $max = 4000)
{
    $s = trim((string)$s);
    if (function_exists('mb_substr')) {
        $s = mb_substr($s, 0, $max, 'UTF-8');
    } else {
        $s = substr($s, 0, $max);
    }
    return $s;
}

if ($loggedin && $mybb->request_method === 'post' && $hay_hueco) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $errores[] = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } else {
        // ---- Raza ----
        $raza1 = $mybb->get_input('raza_principal');
        $hibrido = $mybb->get_input('es_hibrido', MyBB::INPUT_INT) ? true : false;
        $raza2 = $hibrido ? $mybb->get_input('raza_secundaria') : '';

        if (!isset($RAZAS[$raza1])) {
            $errores[] = 'Elige una raza principal válida.';
        }
        if ($hibrido && (!isset($RAZAS[$raza2]) || $raza2 === $raza1)) {
            $errores[] = 'Elige una raza secundaria distinta para el híbrido.';
        }

        // ---- Concepto ----
        $nombre = iforge_rol_clean($mybb->get_input('nombre'), 120);
        $apodo = iforge_rol_clean($mybb->get_input('apodo'), 60);
        $edad = iforge_rol_clean($mybb->get_input('edad'), 20);
        $genero = iforge_rol_clean($mybb->get_input('genero'), 40);
        $tiene_d = $mybb->get_input('tiene_d', MyBB::INPUT_INT) ? true : false;
        $concepto = iforge_rol_clean($mybb->get_input('concepto'), 600);

        if ($nombre === '' || function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') < 3 : strlen($nombre) < 3) {
            $errores[] = 'El nombre del personaje debe tener al menos 3 caracteres.';
        }
        if ($concepto === '') {
            $errores[] = 'Describe brevemente el concepto de tu personaje.';
        }
        if ($nombre !== '' && $db->table_exists('rol_personajes')) {
            $dupe = $db->simple_select('rol_personajes', 'pid', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
            if ($db->num_rows($dupe)) {
                $errores[] = 'Ya existe un personaje con ese nombre.';
            }
        }

        // ---- Stats: recalcular en servidor, nunca confiar en el cliente ----
        $stats_base = array_fill_keys($STAT_KEYS, 1);
        $stats_raciales = $stats_base;
        if (isset($RAZAS[$raza1])) {
            foreach ($RAZAS[$raza1]['mod'] as $k => $v) {
                $stats_raciales[$k] = ($stats_raciales[$k] ?? 1) + $v;
            }
            if (!$hibrido) {
                foreach ($RAZAS[$raza1]['mod_secundaria'] as $k => $v) {
                    $stats_raciales[$k] = ($stats_raciales[$k] ?? 1) + $v;
                }
            } elseif (isset($RAZAS[$raza2])) {
                foreach ($RAZAS[$raza2]['mod'] as $k => $v) {
                    $stats_raciales[$k] = ($stats_raciales[$k] ?? 1) + $v;
                }
            }
        }

        $max_bumps = 1;
        if (isset($RAZAS[$raza1]) && !empty($RAZAS[$raza1]['extra_stat_bump'])) {
            $max_bumps = 2;
        }
        if ($hibrido && isset($RAZAS[$raza2]) && !empty($RAZAS[$raza2]['extra_stat_bump'])) {
            $max_bumps = 2;
        }

        $bumps = $mybb->get_input('stat_bump', MyBB::INPUT_ARRAY);
        if (!is_array($bumps)) {
            $bumps = array();
        }
        $bumps = array_values(array_unique(array_intersect($bumps, $STAT_KEYS)));
        if (count($bumps) < 1) {
            $errores[] = 'Sube al menos una estadística en la creación.';
        }
        if (count($bumps) > $max_bumps) {
            $errores[] = 'Tu raza solo permite subir ' . $max_bumps . ' estadística(s) en la creación.';
        }

        $stats_efectivas = $stats_raciales;
        foreach ($bumps as $b) {
            $stats_efectivas[$b] = ($stats_efectivas[$b] ?? 1) + 1;
        }
        $suma = array_sum($stats_efectivas);
        $rango = iforge_rol_rank_from_sum($suma);

        // ---- Virtudes y Defectos ----
        $virtudes_in = $mybb->get_input('virtudes', MyBB::INPUT_ARRAY);
        $defectos_in = $mybb->get_input('defectos', MyBB::INPUT_ARRAY);
        if (!is_array($virtudes_in)) $virtudes_in = array();
        if (!is_array($defectos_in)) $defectos_in = array();

        $pc_gastado = 0;
        $virtudes_sel = array();
        foreach ($virtudes_in as $vid) {
            $v = iforge_rol_find_virtud($vid);
            if ($v === null) continue;
            $spec = !empty($v['spec']) ? iforge_rol_clean($mybb->get_input('virtud_spec_' . $vid), 200) : '';
            if (!empty($v['spec']) && $spec === '') {
                $errores[] = 'La virtud "' . $v['nombre'] . '" requiere que especifiques un detalle.';
            }
            $pc_gastado += (int)$v['coste'];
            $virtudes_sel[$vid] = array('nombre' => $v['nombre'], 'coste' => (int)$v['coste'], 'spec' => $spec);
        }
        // Prerrequisitos Adinerado 1→2→3
        if (isset($virtudes_sel['V-RIQ-02']) && !isset($virtudes_sel['V-RIQ-01'])) {
            $errores[] = 'Adinerado 2 requiere tener Adinerado 1.';
        }
        if (isset($virtudes_sel['V-RIQ-03']) && !isset($virtudes_sel['V-RIQ-02'])) {
            $errores[] = 'Adinerado 3 requiere tener Adinerado 2.';
        }

        $pc_devuelto = 0;
        $defectos_sel = array();
        foreach ($defectos_in as $did) {
            $d = iforge_rol_find_defecto($did);
            if ($d === null) continue;
            $spec = !empty($d['spec']) ? iforge_rol_clean($mybb->get_input('defecto_spec_' . $did), 200) : '';
            if (!empty($d['spec']) && $spec === '') {
                $errores[] = 'El defecto "' . $d['nombre'] . '" requiere que especifiques un detalle.';
            }
            $pc_devuelto += (int)$d['devuelve'];
            $defectos_sel[$did] = array('nombre' => $d['nombre'], 'devuelve' => (int)$d['devuelve'], 'spec' => $spec);
        }

        $pc_balance = $PC_BASE - $pc_gastado + $pc_devuelto;
        if ($pc_balance < 0) {
            $errores[] = 'Te has pasado de Puntos de Creación (PC). Ajusta virtudes/defectos.';
        }

        // ---- Facción ----
        $faccion = $mybb->get_input('faccion');
        if (!isset($FACCIONES[$faccion])) {
            $errores[] = 'Elige una facción inicial válida.';
        }

        // ---- Equipo ----
        $arma = $mybb->get_input('arma');
        $objeto_personal = iforge_rol_clean($mybb->get_input('objeto_personal'), 200);
        if (!isset($ARMAS[$arma])) {
            $errores[] = 'Elige un arma inicial válida.';
        }
        if ($objeto_personal === '') {
            $errores[] = 'Describe tu objeto personal inicial.';
        }
        $berries = $BERRIES_BASE;
        if (isset($virtudes_sel['V-RIQ-01'])) $berries += 1000000;
        if (isset($virtudes_sel['V-RIQ-02'])) $berries += 3000000;
        if (isset($virtudes_sel['V-RIQ-03'])) $berries += 10000000;

        // ---- Historia ----
        $historia_pasado = iforge_rol_clean($mybb->get_input('historia_pasado'), 6000);
        $historia_motivacion = iforge_rol_clean($mybb->get_input('historia_motivacion'), 3000);
        $historia_relaciones = iforge_rol_clean($mybb->get_input('historia_relaciones'), 3000);
        $min_len = function_exists('mb_strlen') ? mb_strlen($historia_pasado, 'UTF-8') : strlen($historia_pasado);
        if ($min_len < 80) {
            $errores[] = 'Cuenta el pasado de tu personaje con algo más de detalle (mínimo ~80 caracteres).';
        }

        // ---- Insertar si todo OK ----
        if (empty($errores) && $db->table_exists('rol_personajes')) {
            $slug = my_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
            $slug = trim($slug, '-');

            $datos = array(
                'raza_principal' => $raza1,
                'raza_secundaria' => $hibrido ? $raza2 : null,
                'hibrido' => $hibrido,
                'apodo' => $apodo,
                'edad' => $edad,
                'genero' => $genero,
                'tiene_d' => $tiene_d,
                'stats_base' => $stats_base,
                'stats_raciales' => $stats_raciales,
                'stats_efectivas' => $stats_efectivas,
                'stat_bumps' => $bumps,
                'rango_suma' => $suma,
                'virtudes' => $virtudes_sel,
                'defectos' => $defectos_sel,
                'pc_gastado' => $pc_gastado,
                'pc_devuelto' => $pc_devuelto,
                'pc_balance' => $pc_balance,
                'faccion' => $faccion,
            );
            $inventario = array(
                'arma' => $arma,
                'objeto_personal' => $objeto_personal,
            );
            $economia = array('berries' => $berries);
            $bio = array(
                'concepto' => $concepto,
                'pasado' => $historia_pasado,
                'motivacion' => $historia_motivacion,
                'relaciones' => $historia_relaciones,
            );

            $pid = $db->insert_query('rol_personajes', array(
                'uid' => $uid,
                'nombre' => $db->escape_string($nombre),
                'slug' => $db->escape_string($slug),
                'estado' => 'revision',
                'activo' => 0,
                'rango' => $rango,
                'nivel' => 1,
                'avatar' => '',
                'datos' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
                'inventario' => $db->escape_string(json_encode($inventario, JSON_UNESCAPED_UNICODE)),
                'economia' => $db->escape_string(json_encode($economia, JSON_UNESCAPED_UNICODE)),
                'bio' => $db->escape_string(json_encode($bio, JSON_UNESCAPED_UNICODE)),
                'dateline' => TIME_NOW,
                'lastedit' => TIME_NOW,
            ));

            if ($pid && $db->table_exists('rol_tramites')) {
                $db->insert_query('rol_tramites', array(
                    'uid' => $uid,
                    'pid' => (int)$pid,
                    'tipo' => 'crear_personaje',
                    'estado' => 'pendiente',
                    'datos' => $db->escape_string(json_encode(array('nombre' => $nombre, 'faccion' => $faccion), JSON_UNESCAPED_UNICODE)),
                    'dateline' => TIME_NOW,
                    'lastedit' => TIME_NOW,
                ));
            }

            $ok = true;
        }
    }
}

if ($ok) {
    header('Location: ' . $mybb->settings['bburl'] . '/personajes.php?forjado=1');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Crear personaje</title>
<?php echo iforge_rol_head_base(); ?>
<style>
/* Estilos de esta página — base global (:root, body, fondo) en docs/themes/iforge.css */
.wrap{max-width:1100px;margin:0 auto;padding:0 18px}

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
.btn-hot:disabled{background:var(--rivet);color:var(--paper-dim);cursor:not-allowed;transform:none;box-shadow:none}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:7px 13px;font-size:.7rem}

/* ---------------- PLACAS ---------------- */
.plate{border:2px solid #000;background:var(--iron-plate);margin-bottom:12px}
.plate-h{background:var(--iron-edge);padding:9px 13px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:2px solid #000}
.plate-h .t{font-family:var(--disp);font-weight:800;font-size:1.1rem;text-transform:uppercase;color:var(--paper);letter-spacing:.5px}
.plate-h .c{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.plate-b{padding:16px}
.shead{display:flex;align-items:baseline;gap:14px;margin:8px 0 14px}
.shead h1,.shead h2{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

/* ---------------- FLASH ---------------- */
.flash{border:2px solid #000;padding:11px 14px;margin-bottom:16px;font-family:var(--mono);font-size:.74rem;font-weight:700;letter-spacing:.3px}
.flash.warn{background:var(--h6);color:var(--iron)}
.flash ul{margin:6px 0 0 18px}

/* ---------------- FOOTER ---------------- */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
.foot-links{display:flex;gap:16px;flex-wrap:wrap}
.foot-links a{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.foot-links a:hover{color:var(--ember-hi)}
.foot-c{font-family:var(--mono);font-size:.62rem;color:var(--ash)}

/* ---------------- EMPTY / NOPERM ---------------- */
.pj-empty{border:2px dashed var(--rivet);background:var(--iron-plate);padding:40px 22px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:14px}
.pj-empty .anvil{width:72px;height:72px;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.pj-empty .anvil svg{width:38px;height:38px;stroke:var(--h6);fill:none;stroke-width:2}
.pj-empty .big{font-family:var(--disp);font-weight:800;font-size:1.9rem;text-transform:uppercase;color:var(--paper);line-height:1}
.pj-empty p{font-family:var(--mono);font-size:.76rem;color:var(--paper-dim);line-height:1.6;max-width:54ch}
.pj-empty .acts{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:4px}

/* ============================================================
   WIZARD
   ============================================================ */
.wiz-progress{display:flex;gap:4px;margin-bottom:18px;overflow-x:auto;padding-bottom:4px}
.wiz-step-dot{flex:1 0 auto;min-width:88px;text-align:center;font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--paper-dim);padding:8px 6px;border:2px solid var(--rivet);background:var(--iron-plate);white-space:nowrap}
.wiz-step-dot .n{display:block;font-family:var(--disp);font-size:1.1rem;font-weight:800;color:var(--paper-dim)}
.wiz-step-dot.done{border-color:var(--patina);color:var(--patina-hi)}
.wiz-step-dot.done .n{color:var(--patina-hi)}
.wiz-step-dot.on{border-color:var(--ember);background:var(--ember);color:var(--iron)}
.wiz-step-dot.on .n{color:var(--iron)}

.wiz-step{display:none}
.wiz-step.on{display:block}
.field{margin-bottom:16px}
.field label.flabel{display:block;font-family:var(--mono);font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--paper-dim);margin-bottom:6px}
.field input[type=text],.field input[type=number],.field select,.field textarea{width:100%;background:var(--iron);border:2px solid var(--rivet);color:var(--paper);font-family:var(--body);font-size:.9rem;padding:10px 12px}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--ember)}
.field textarea{resize:vertical;min-height:90px;line-height:1.5}
.field .hint{font-family:var(--mono);font-size:.62rem;color:var(--ash);margin-top:5px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:640px){.grid2{grid-template-columns:1fr}}

/* raza cards */
.race-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px}
.race-card{position:relative;border:2px solid var(--rivet);background:var(--iron-plate);padding:13px;cursor:pointer;transition:border-color .12s,transform .12s}
.race-card:hover{border-color:var(--ember-hi);transform:translate(-2px,-2px)}
.race-card input{position:absolute;opacity:0;pointer-events:none}
.race-card input:checked ~ .rc-body{}
.race-card:has(input:checked){border-color:var(--ember);background:var(--iron-hi);box-shadow:3px 3px 0 #000}
.rc-name{font-family:var(--disp);font-weight:800;font-size:1.15rem;text-transform:uppercase;color:var(--paper);margin-bottom:3px}
.rc-resumen{font-size:.74rem;color:var(--paper-dim);line-height:1.4;margin-bottom:8px}
.rc-pas{font-family:var(--mono);font-size:.62rem;line-height:1.5;color:var(--ash);border-top:1px solid var(--iron-edge);padding-top:7px;margin-top:7px}
.rc-pas b{color:var(--h6)}

/* stats table */
.stats-pillar{margin-bottom:14px}
.stats-pillar-h{font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--h6);margin-bottom:6px;border-bottom:1px solid var(--iron-edge);padding-bottom:4px}
.stat-row{display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--iron-edge)}
.stat-row:last-child{border-bottom:none}
.stat-name{flex:0 0 160px;font-size:.82rem;color:var(--paper)}
.stat-name .sig{font-family:var(--mono);color:var(--paper-dim);font-size:.68rem}
.stat-val{flex:0 0 40px;text-align:center;font-family:var(--disp);font-weight:800;font-size:1.1rem}
.stat-val.neg{color:var(--crack)}
.stat-val.pos{color:var(--patina-hi)}
.stat-eff{flex:0 0 46px;text-align:center;font-family:var(--disp);font-weight:900;font-size:1.3rem;color:var(--h6)}
.stat-bump{flex:0 0 auto;display:flex;align-items:center;gap:5px;font-family:var(--mono);font-size:.6rem;color:var(--paper-dim);text-transform:uppercase}
.wiz-sum-bar{display:flex;flex-wrap:wrap;gap:14px;align-items:center;background:var(--iron-edge);border:2px solid #000;padding:10px 14px;margin:14px 0;font-family:var(--mono);font-size:.7rem;text-transform:uppercase;letter-spacing:.4px}
.wiz-sum-bar b{color:var(--h6);font-family:var(--disp);font-size:1.1rem}

/* virtudes / defectos */
.pc-bar{position:sticky;top:60px;z-index:5;display:flex;align-items:center;gap:14px;background:var(--iron-edge);border:2px solid #000;padding:10px 14px;margin-bottom:14px;font-family:var(--mono);font-size:.72rem;text-transform:uppercase;flex-wrap:wrap}
.pc-bar .pc-num{font-family:var(--disp);font-size:1.4rem;font-weight:900;color:var(--h6)}
.pc-bar.bad .pc-num{color:var(--crack)}
.cat-group{border:2px solid var(--rivet);margin-bottom:10px;background:var(--iron-plate)}
.cat-h{background:var(--iron-edge);padding:8px 12px;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--paper-dim);cursor:pointer;display:flex;justify-content:space-between}
.cat-body{padding:4px 12px 8px}
.item-row{display:flex;gap:10px;align-items:flex-start;padding:8px 0;border-bottom:1px solid var(--iron-edge)}
.item-row:last-child{border-bottom:none}
.item-row input[type=checkbox]{margin-top:3px;flex:0 0 auto}
.item-txt{flex:1;min-width:0}
.item-name{font-size:.84rem;font-weight:600;color:var(--paper)}
.item-name .badge{font-family:var(--mono);font-size:.6rem;font-weight:700;padding:1px 7px;border:1px solid #000;margin-left:6px}
.badge.cost{background:var(--crack);color:var(--paper)}
.badge.back{background:var(--patina);color:var(--iron)}
.item-desc{font-size:.74rem;color:var(--paper-dim);line-height:1.4;margin-top:2px}
.item-spec{display:none;margin-top:6px}
.item-spec.show{display:block}
.item-spec input{width:100%;background:var(--iron);border:1px solid var(--rivet);color:var(--paper);font-size:.78rem;padding:6px 8px}

/* facción cards */
.fac-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
.fac-card{position:relative;border:2px solid var(--rivet);background:var(--iron-plate);padding:13px;cursor:pointer}
.fac-card input{position:absolute;opacity:0;pointer-events:none}
.fac-card:has(input:checked){border-color:var(--ember);background:var(--iron-hi);box-shadow:3px 3px 0 #000}
.fac-name{font-family:var(--disp);font-weight:800;font-size:1.2rem;text-transform:uppercase;color:var(--paper)}
.fac-desc{font-size:.78rem;color:var(--paper-dim);margin:4px 0 8px}
.fac-adv{font-family:var(--mono);font-size:.62rem;color:var(--h6);line-height:1.4}

/* resumen */
.sum-block{border:2px solid var(--rivet);background:var(--iron-edge);padding:12px 14px;margin-bottom:10px}
.sum-block h4{font-family:var(--mono);font-size:.66rem;text-transform:uppercase;letter-spacing:.6px;color:var(--h6);margin-bottom:8px}
.sum-block .line{font-size:.82rem;color:var(--paper);margin-bottom:3px}
.sum-block .line b{color:var(--paper-dim);font-weight:400;font-family:var(--mono);font-size:.68rem;text-transform:uppercase;margin-right:6px}

.wiz-nav{display:flex;justify-content:space-between;gap:10px;margin-top:20px}
.wiz-err{font-family:var(--mono);font-size:.68rem;color:var(--crack);margin-top:8px;min-height:1em}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b>Crear</b>
  </div>
</div>

<div class="wrap">

  <section>
    <div class="shead">
      <h1>Crear personaje</h1>
      <span class="code">// one piece eternal</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$loggedin): ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Acceso requerido</span><span class="c">// acceso</span></div>
    <div class="plate-b">
      <div class="pj-empty">
        <span class="anvil"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
        <div class="big">Accede para crear un personaje</div>
        <p>Necesitas una cuenta en el foro para crear una ficha.</p>
        <div class="acts">
          <a href="<?php echo $bburl; ?>/member.php?action=register" class="btn btn-hot">Reg&iacute;strate</a>
          <a href="<?php echo $bburl; ?>/member.php?action=login" class="btn btn-ghost">Acceder</a>
        </div>
      </div>
    </div>
  </div>
<?php elseif (!$hay_hueco && !$editando): ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Sin huecos disponibles</span><span class="c">// <?php echo $usados; ?>/<?php echo $slots; ?></span></div>
    <div class="plate-b">
      <div class="pj-empty">
        <span class="anvil"><svg viewBox="0 0 24 24"><path d="M3 20h18"/><path d="M6 20v-5h5v5"/><path d="M4 15l8-4 4 3"/><path d="M14 11l3-6 4 2-2 5"/><circle cx="9" cy="7" r="2.4"/></svg></span>
        <div class="big">Ya usas todos tus huecos de personaje</div>
        <p>Tu cuenta tiene <?php echo $slots; ?> hueco(s) de personaje y ya los ocupas todos (<?php echo $usados; ?>). Solicita un hueco adicional en trámites o gestiona tus fichas actuales.</p>
        <div class="acts">
          <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Ver mi expediente</a>
          <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost">Trámites</a>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>

<?php if (!empty($errores)): ?>
  <div class="flash warn">No se pudo crear el personaje:
    <ul><?php foreach ($errores as $e) echo '<li>' . htmlspecialchars_uni($e) . '</li>'; ?></ul>
  </div>
<?php endif; ?>

<?php if ($editando): ?>
  <div style="margin-bottom:14px;padding:12px 16px;border:2px solid var(--h6);background:var(--iron-plate);display:flex;align-items:center;justify-content:space-between;gap:12px">
    <span style="font-family:var(--mono);font-size:.68rem;color:var(--paper-dim)">Est&aacute;s editando la ficha de <b style="color:var(--paper)"><?php echo htmlspecialchars_uni($editando['nombre']); ?></b>. Los cambios se enviar&aacute;n a revisi&oacute;n de nuevo.</span>
    <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-ghost btn-sm">Cancelar</a>
  </div>
<?php endif; ?>

  <p class="mono" style="font-size:.78rem;color:var(--paper-dim);max-width:76ch;margin-bottom:16px">
    Sigue los <b style="color:var(--paper)">7 pasos</b> del foro: raza, concepto, estadísticas, virtudes/defectos, facción, equipo e historia. Rellena todo en una sola sesión — al enviar, tu ficha entra en <b style="color:var(--h6)">revisión</b> del staff.
  </p>

  <div class="wiz-progress" id="wizProgress"></div>

  <form method="post" action="<?php echo $bburl; ?>/crear-personaje.php" id="wizForm">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">

    <!-- PASO 1: RAZA -->
    <div class="wiz-step on" data-step="1">
      <div class="plate">
        <div class="plate-h"><span class="t">1. Raza</span><span class="c">// pura o híbrida</span></div>
        <div class="plate-b">
          <div class="race-grid" id="raceGrid">
<?php foreach ($RAZAS as $rid => $r):
    $mod = json_encode($r['mod'], JSON_UNESCAPED_UNICODE);
    $modsec = json_encode($r['mod_secundaria'], JSON_UNESCAPED_UNICODE);
?>
            <label class="race-card">
              <input type="radio" name="raza_principal" value="<?php echo $rid; ?>" data-mod='<?php echo htmlspecialchars_uni($mod); ?>' data-modsec='<?php echo htmlspecialchars_uni($modsec); ?>' data-extra-bump="<?php echo !empty($r['extra_stat_bump']) ? '1' : '0'; ?>" data-nombre="<?php echo htmlspecialchars_uni($r['nombre']); ?>" required>
              <div class="rc-body">
                <div class="rc-name"><?php echo htmlspecialchars_uni($r['nombre']); ?></div>
                <div class="rc-resumen"><?php echo htmlspecialchars_uni($r['resumen']); ?></div>
                <div class="rc-pas"><b>Primaria</b> — <?php echo htmlspecialchars_uni($r['primaria_nombre']); ?>: <?php echo htmlspecialchars_uni($r['primaria_desc']); ?><br><b>Secundaria</b> — <?php echo htmlspecialchars_uni($r['secundaria_nombre']); ?>: <?php echo htmlspecialchars_uni($r['secundaria_desc']); ?></div>
              </div>
            </label>
<?php endforeach; ?>
          </div>

          <div class="field" style="margin-top:16px">
            <label class="flabel"><input type="checkbox" id="esHibrido" name="es_hibrido" value="1"> ¿Es un híbrido de dos razas?</label>
            <p class="hint">Un híbrido obtiene SOLO las pasivas primarias de ambas razas (ninguna secundaria).</p>
          </div>
          <div class="field" id="razaSecundariaWrap" style="display:none">
            <label class="flabel">Raza secundaria</label>
            <select name="raza_secundaria" id="razaSecundaria">
              <option value="">— elige —</option>
<?php foreach ($RAZAS as $rid => $r):
    $mod = json_encode($r['mod'], JSON_UNESCAPED_UNICODE);
?>
              <option value="<?php echo $rid; ?>" data-mod='<?php echo htmlspecialchars_uni($mod); ?>' data-extra-bump="<?php echo !empty($r['extra_stat_bump']) ? '1' : '0'; ?>"><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
<?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- PASO 2: CONCEPTO -->
    <div class="wiz-step" data-step="2">
      <div class="plate">
        <div class="plate-h"><span class="t">2. Nombre y concepto</span><span class="c">// quién es</span></div>
        <div class="plate-b">
          <div class="grid2">
            <div class="field"><label class="flabel">Nombre del personaje *</label><input type="text" name="nombre" maxlength="120" required value="<?php echo htmlspecialchars_uni($old['nombre'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Apodo (opcional)</label><input type="text" name="apodo" maxlength="60" value="<?php echo htmlspecialchars_uni($old['apodo'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Edad</label><input type="text" name="edad" maxlength="20" value="<?php echo htmlspecialchars_uni($old['edad'] ?? ''); ?>"></div>
            <div class="field"><label class="flabel">Género</label><input type="text" name="genero" maxlength="40" value="<?php echo htmlspecialchars_uni($old['genero'] ?? ''); ?>"></div>
          </div>
          <div class="field"><label class="flabel"><input type="checkbox" name="tiene_d" value="1"<?php echo !empty($old['tiene_d']) ? ' checked' : ''; ?>> ¿Tiene una "D." en su nombre?</label></div>
          <div class="field"><label class="flabel">Concepto / aspecto *</label><textarea name="concepto" required maxlength="600" placeholder="Quién es, qué aspecto tiene, qué lo mueve..."><?php echo htmlspecialchars_uni($old['concepto'] ?? ''); ?></textarea></div>
        </div>
      </div>
    </div>

    <!-- PASO 3: STATS -->
    <div class="wiz-step" data-step="3">
      <div class="plate">
        <div class="plate-h"><span class="t">3. Estadísticas</span><span class="c">// F(1) a M+(10)</span></div>
        <div class="plate-b">
          <p class="mono" style="font-size:.72rem;color:var(--paper-dim);margin-bottom:10px">Todas empiezan en <b style="color:var(--paper)">F</b>. Las pasivas raciales ya modifican el valor efectivo. Después puedes subir <b id="maxBumpsLabel" style="color:var(--h6)">1 estadística</b> un rango más.</p>
          <div id="statsContainer"></div>
          <div class="wiz-sum-bar"><span>Suma total: <b id="statSum">0</b></span><span>Rango del personaje: <b id="statRank">F</b></span></div>
        </div>
      </div>
    </div>

    <!-- PASO 4: VIRTUDES Y DEFECTOS -->
    <div class="wiz-step" data-step="4">
      <div class="plate">
        <div class="plate-h"><span class="t">4. Virtudes y defectos</span><span class="c">// 6 PC iniciales</span></div>
        <div class="plate-b">
          <div class="pc-bar" id="pcBar">PC disponibles: <span class="pc-num" id="pcNum">6</span> <span class="mono" style="font-size:.62rem;color:var(--ash)">(6 base − coste virtudes + devuelto por defectos)</span></div>

          <div class="cat-h" style="margin-bottom:6px;border:2px solid var(--rivet)">VIRTUDES</div>
<?php foreach ($VIRTUDES as $cat => $items): ?>
          <div class="cat-group">
            <div class="cat-h" data-toggle><span><?php echo htmlspecialchars_uni($cat); ?></span><span>▾</span></div>
            <div class="cat-body">
<?php foreach ($items as $vid => $v): $checked = !empty($old['virtudes']) && in_array($vid, (array)$old['virtudes'], true); ?>
              <div class="item-row">
                <input type="checkbox" name="virtudes[]" value="<?php echo $vid; ?>" id="chk_<?php echo $vid; ?>" data-coste="<?php echo (int)$v['coste']; ?>"<?php echo !empty($v['spec']) ? ' data-spec="1"' : ''; ?><?php echo $checked ? ' checked' : ''; ?>>
                <div class="item-txt">
                  <label for="chk_<?php echo $vid; ?>" class="item-name"><?php echo htmlspecialchars_uni($v['nombre']); ?> <span class="badge cost">-<?php echo (int)$v['coste']; ?> PC</span></label>
                  <div class="item-desc"><?php echo htmlspecialchars_uni($v['desc']); ?></div>
<?php if (!empty($v['spec'])): ?>
                  <div class="item-spec<?php echo $checked ? ' show' : ''; ?>"><input type="text" name="virtud_spec_<?php echo $vid; ?>" maxlength="200" placeholder="Especifica..." value="<?php echo htmlspecialchars_uni($old['virtud_spec_' . $vid] ?? ''); ?>"></div>
<?php endif; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>
<?php endforeach; ?>

          <div class="cat-h" style="margin:16px 0 6px;border:2px solid var(--rivet)">DEFECTOS</div>
<?php foreach ($DEFECTOS as $cat => $items): ?>
          <div class="cat-group">
            <div class="cat-h" data-toggle><span><?php echo htmlspecialchars_uni($cat); ?></span><span>▾</span></div>
            <div class="cat-body">
<?php foreach ($items as $did => $d): $checked = !empty($old['defectos']) && in_array($did, (array)$old['defectos'], true); ?>
              <div class="item-row">
                <input type="checkbox" name="defectos[]" value="<?php echo $did; ?>" id="chk_<?php echo $did; ?>" data-devuelve="<?php echo (int)$d['devuelve']; ?>"<?php echo !empty($d['spec']) ? ' data-spec="1"' : ''; ?><?php echo $checked ? ' checked' : ''; ?>>
                <div class="item-txt">
                  <label for="chk_<?php echo $did; ?>" class="item-name"><?php echo htmlspecialchars_uni($d['nombre']); ?> <span class="badge back">+<?php echo (int)$d['devuelve']; ?> PC</span></label>
                  <div class="item-desc"><?php echo htmlspecialchars_uni($d['desc']); ?></div>
<?php if (!empty($d['spec'])): ?>
                  <div class="item-spec<?php echo $checked ? ' show' : ''; ?>"><input type="text" name="defecto_spec_<?php echo $did; ?>" maxlength="200" placeholder="Especifica..." value="<?php echo htmlspecialchars_uni($old['defecto_spec_' . $did] ?? ''); ?>"></div>
<?php endif; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- PASO 5: FACCIÓN -->
    <div class="wiz-step" data-step="5">
      <div class="plate">
        <div class="plate-h"><span class="t">5. Facción inicial</span><span class="c">// punto de partida</span></div>
        <div class="plate-b">
          <div class="fac-grid">
<?php foreach ($FACCIONES as $fid => $f): ?>
            <label class="fac-card">
              <input type="radio" name="faccion" value="<?php echo $fid; ?>" required>
              <div class="fac-name"><?php echo htmlspecialchars_uni($f['nombre']); ?></div>
              <div class="fac-desc"><?php echo htmlspecialchars_uni($f['desc']); ?></div>
              <div class="fac-adv"><?php echo htmlspecialchars_uni($f['ventaja']); ?></div>
            </label>
<?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- PASO 6: EQUIPO -->
    <div class="wiz-step" data-step="6">
      <div class="plate">
        <div class="plate-h"><span class="t">6. Equipo inicial</span><span class="c">// lo que llevas al empezar</span></div>
        <div class="plate-b">
          <div class="field">
            <label class="flabel">Arma básica *</label>
            <select name="arma" id="armaSel" required>
              <option value="">— elige —</option>
<?php foreach ($ARMAS as $aid => $a): ?>
              <option value="<?php echo $aid; ?>"><?php echo htmlspecialchars_uni($a['nombre']); ?> — <?php echo htmlspecialchars_uni($a['detalle']); ?></option>
<?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label class="flabel">Objeto personal *</label><input type="text" name="objeto_personal" maxlength="200" required placeholder="Un objeto con significado, sujeto a aprobación del staff" value="<?php echo htmlspecialchars_uni($old['objeto_personal'] ?? ''); ?>"></div>
          <div class="wiz-sum-bar">Berries iniciales: <b id="berriesOut">50.000</b></div>
          <p class="hint">Ropa y pertenencias básicas incluidas. Sin Fruta del Diablo ni Haki al inicio (se obtienen en juego).</p>
        </div>
      </div>
    </div>

    <!-- PASO 7: HISTORIA -->
    <div class="wiz-step" data-step="7">
      <div class="plate">
        <div class="plate-h"><span class="t">7. Historia</span><span class="c">// pasado, motivación, relaciones</span></div>
        <div class="plate-b">
          <div class="field"><label class="flabel">Pasado *</label><textarea name="historia_pasado" required style="min-height:160px" placeholder="De dónde viene, qué le ha pasado antes de empezar a rolear..."><?php echo htmlspecialchars_uni($old['historia_pasado'] ?? ''); ?></textarea></div>
          <div class="field"><label class="flabel">Motivación</label><textarea name="historia_motivacion" placeholder="Qué busca, qué lo empuja a moverse por el mundo..."><?php echo htmlspecialchars_uni($old['historia_motivacion'] ?? ''); ?></textarea></div>
          <div class="field"><label class="flabel">Relaciones</label><textarea name="historia_relaciones" placeholder="Vínculos, familia, tripulación, enemigos..."><?php echo htmlspecialchars_uni($old['historia_relaciones'] ?? ''); ?></textarea></div>
        </div>
      </div>
    </div>

    <!-- PASO 8: RESUMEN -->
    <div class="wiz-step" data-step="8">
      <div class="plate">
        <div class="plate-h"><span class="t">8. Revisión final</span><span class="c">// antes de enviar</span></div>
        <div class="plate-b" id="wizSummary"></div>
      </div>
    </div>

    <div class="wiz-nav">
      <button type="button" class="btn btn-ghost" id="wizPrev">&larr; Anterior</button>
      <div class="wiz-err" id="wizErr"></div>
      <button type="button" class="btn btn-hot" id="wizNext">Siguiente &rarr;</button>
      <button type="submit" class="btn btn-hot" id="wizSubmit" style="display:none">Enviar a revisión</button>
    </div>
  </form>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<?php if ($loggedin && $hay_hueco): ?>
<script>
(function(){
  var STAT_LABELS = <?php echo json_encode($STATS, JSON_UNESCAPED_UNICODE); ?>;
  var RANK_BREAKS = [[66,'M+'],[56,'M'],[47,'SS'],[39,'S'],[32,'A'],[26,'B'],[21,'C'],[17,'D'],[14,'E'],[0,'F']];
  var STEP_NAMES = ['Raza','Concepto','Stats','Virtudes','Facción','Equipo','Historia','Resumen'];
  var steps = Array.prototype.slice.call(document.querySelectorAll('.wiz-step'));
  var cur = 1;
  var form = document.getElementById('wizForm');

  // ---- Progreso ----
  var progWrap = document.getElementById('wizProgress');
  STEP_NAMES.forEach(function(name, i){
    var d = document.createElement('div');
    d.className = 'wiz-step-dot';
    d.innerHTML = '<span class="n">' + (i+1) + '</span>' + name;
    progWrap.appendChild(d);
  });

  function renderProgress(){
    var dots = progWrap.children;
    for (var i = 0; i < dots.length; i++){
      dots[i].classList.toggle('on', i+1 === cur);
      dots[i].classList.toggle('done', i+1 < cur);
    }
  }

  function showStep(n){
    steps.forEach(function(s){ s.classList.toggle('on', parseInt(s.dataset.step,10) === n); });
    document.getElementById('wizPrev').style.visibility = (n === 1) ? 'hidden' : 'visible';
    document.getElementById('wizNext').style.display = (n === steps.length) ? 'none' : 'inline-block';
    document.getElementById('wizSubmit').style.display = (n === steps.length) ? 'inline-block' : 'none';
    document.getElementById('wizErr').textContent = '';
    renderProgress();
    if (n === 3) renderStats();
    if (n === steps.length) renderSummary();
    window.scrollTo({top: form.offsetTop - 70, behavior:'smooth'});
  }

  function validateStep(n){
    if (n === 1){
      if (!document.querySelector('input[name=raza_principal]:checked')) return 'Elige una raza principal.';
      if (document.getElementById('esHibrido').checked && !document.getElementById('razaSecundaria').value) return 'Elige la raza secundaria del híbrido.';
    }
    if (n === 2){
      var nombre = form.querySelector('[name=nombre]').value.trim();
      var concepto = form.querySelector('[name=concepto]').value.trim();
      if (nombre.length < 3) return 'Escribe un nombre de al menos 3 caracteres.';
      if (!concepto) return 'Describe el concepto de tu personaje.';
    }
    if (n === 3){
      var bumps = form.querySelectorAll('input[name="stat_bump[]"]:checked');
      if (bumps.length < 1) return 'Sube al menos una estadística.';
    }
    if (n === 4){
      var bar = document.getElementById('pcBar');
      if (bar.classList.contains('bad')) return 'Te has pasado de Puntos de Creación (PC). Ajusta tu selección.';
      var missingSpec = false;
      form.querySelectorAll('input[data-spec="1"]:checked').forEach(function(chk){
        var wrap = chk.closest('.item-row').querySelector('.item-spec input');
        if (wrap && !wrap.value.trim()) missingSpec = true;
      });
      if (missingSpec) return 'Rellena el campo de detalle de las virtudes/defectos marcadas.';
    }
    if (n === 5){
      if (!document.querySelector('input[name=faccion]:checked')) return 'Elige una facción inicial.';
    }
    if (n === 6){
      if (!document.getElementById('armaSel').value) return 'Elige un arma básica.';
      if (!form.querySelector('[name=objeto_personal]').value.trim()) return 'Describe tu objeto personal.';
    }
    if (n === 7){
      if (form.querySelector('[name=historia_pasado]').value.trim().length < 80) return 'Cuenta el pasado de tu personaje con algo más de detalle (mínimo ~80 caracteres).';
    }
    return '';
  }

  document.getElementById('wizNext').addEventListener('click', function(){
    var err = validateStep(cur);
    if (err){ document.getElementById('wizErr').textContent = err; return; }
    cur = Math.min(cur + 1, steps.length);
    showStep(cur);
  });
  document.getElementById('wizPrev').addEventListener('click', function(){
    cur = Math.max(cur - 1, 1);
    showStep(cur);
  });

  // ---- Híbrido toggle ----
  var hibChk = document.getElementById('esHibrido');
  var razaSecWrap = document.getElementById('razaSecundariaWrap');
  hibChk.addEventListener('change', function(){
    razaSecWrap.style.display = hibChk.checked ? 'block' : 'none';
    if (!hibChk.checked) document.getElementById('razaSecundaria').value = '';
  });

  // ---- Stats en vivo ----
  function getRazaData(){
    var r1 = document.querySelector('input[name=raza_principal]:checked');
    var hib = hibChk.checked;
    var r2opt = hib ? document.getElementById('razaSecundaria').selectedOptions[0] : null;
    var mod1 = r1 ? JSON.parse(r1.dataset.mod || '{}') : {};
    var modsec1 = (r1 && !hib) ? JSON.parse(r1.dataset.modsec || '{}') : {};
    var mod2 = (r2opt && r2opt.value) ? JSON.parse(r2opt.dataset.mod || '{}') : {};
    var extraBump = (r1 && r1.dataset.extraBump === '1') || (r2opt && r2opt.value && r2opt.dataset.extraBump === '1');
    return {mod1: mod1, modsec1: modsec1, mod2: mod2, extraBump: extraBump};
  }

  function renderStats(){
    var container = document.getElementById('statsContainer');
    var rd = getRazaData();
    var maxBumps = rd.extraBump ? 2 : 1;
    document.getElementById('maxBumpsLabel').textContent = maxBumps + ' estadística' + (maxBumps > 1 ? 's' : '');

    var prevBumps = {};
    container.querySelectorAll('input[name="stat_bump[]"]:checked').forEach(function(c){ prevBumps[c.value] = true; });

    container.innerHTML = '';
    var totalSum = 0;
    Object.keys(STAT_LABELS).forEach(function(pk){
      var pillar = STAT_LABELS[pk];
      var pdiv = document.createElement('div');
      pdiv.className = 'stats-pillar';
      var h = document.createElement('div');
      h.className = 'stats-pillar-h';
      h.textContent = pillar.label;
      pdiv.appendChild(h);

      Object.keys(pillar.stats).forEach(function(sig){
        var base = 1;
        var racial = base + (rd.mod1[sig]||0) + (rd.modsec1[sig]||0) + (rd.mod2[sig]||0);
        var row = document.createElement('div');
        row.className = 'stat-row';
        var delta = racial - base;
        var deltaTxt = delta === 0 ? '=' : (delta > 0 ? '+' + delta : String(delta));
        var deltaCls = delta > 0 ? 'pos' : (delta < 0 ? 'neg' : '');
        row.innerHTML =
          '<span class="stat-name">' + pillar.stats[sig] + ' <span class="sig">' + sig + '</span></span>' +
          '<span class="stat-val ' + deltaCls + '">' + deltaTxt + '</span>' +
          '<span class="stat-eff" data-eff="' + sig + '">' + racial + '</span>' +
          '<label class="stat-bump"><input type="checkbox" name="stat_bump[]" value="' + sig + '"' + (prevBumps[sig] ? ' checked' : '') + '> +1</label>';
        pdiv.appendChild(row);
      });
      container.appendChild(pdiv);
    });

    // Enforce max bumps + recompute effective values & sum
    var bumpBoxes = container.querySelectorAll('input[name="stat_bump[]"]');
    function recompute(){
      var checked = container.querySelectorAll('input[name="stat_bump[]"]:checked');
      bumpBoxes.forEach(function(b){ b.disabled = (checked.length >= maxBumps && !b.checked); });
      var sum = 0;
      container.querySelectorAll('[data-eff]').forEach(function(el){
        var sig = el.dataset.eff;
        var eff = parseInt(el.textContent, 10);
        var box = container.querySelector('input[name="stat_bump[]"][value="' + sig + '"]');
        // recompute base eff from racial only (stored originally), then add bump
        var raw = 1 + (rd.mod1[sig]||0) + (rd.modsec1[sig]||0) + (rd.mod2[sig]||0);
        var withBump = raw + (box && box.checked ? 1 : 0);
        el.textContent = withBump;
        sum += withBump;
      });
      document.getElementById('statSum').textContent = sum;
      var rank = 'F';
      for (var i=0;i<RANK_BREAKS.length;i++){ if (sum >= RANK_BREAKS[i][0]){ rank = RANK_BREAKS[i][1]; break; } }
      document.getElementById('statRank').textContent = rank;
    }
    bumpBoxes.forEach(function(b){ b.addEventListener('change', recompute); });
    recompute();
  }

  document.querySelectorAll('input[name=raza_principal]').forEach(function(r){ r.addEventListener('change', renderStats); });
  document.getElementById('razaSecundaria').addEventListener('change', renderStats);

  // ---- PC bar (virtudes/defectos) ----
  var PC_BASE = <?php echo (int)$PC_BASE; ?>;
  function recomputePc(){
    var gastado = 0, devuelto = 0;
    document.querySelectorAll('input[data-coste]:checked').forEach(function(c){ gastado += parseInt(c.dataset.coste,10)||0; });
    document.querySelectorAll('input[data-devuelve]:checked').forEach(function(c){ devuelto += parseInt(c.dataset.devuelve,10)||0; });
    var balance = PC_BASE - gastado + devuelto;
    document.getElementById('pcNum').textContent = balance;
    document.getElementById('pcBar').classList.toggle('bad', balance < 0);

    // Adinerado prerequisite chain
    var r1c = document.getElementById('chk_V-RIQ-01');
    var r2c = document.getElementById('chk_V-RIQ-02');
    var r3c = document.getElementById('chk_V-RIQ-03');
    if (r2c) r2c.disabled = !(r1c && r1c.checked) && !r2c.checked;
    if (r3c) r3c.disabled = !(r2c && r2c.checked) && !r3c.checked;

    // berries live
    var berries = 50000;
    if (r1c && r1c.checked) berries += 1000000;
    if (r2c && r2c.checked) berries += 3000000;
    if (r3c && r3c.checked) berries += 10000000;
    var out = document.getElementById('berriesOut');
    if (out) out.textContent = berries.toLocaleString('es-ES');
  }
  document.querySelectorAll('input[data-coste],input[data-devuelve]').forEach(function(c){
    c.addEventListener('change', function(){
      if (c.dataset.spec === '1'){
        var specBox = c.closest('.item-row').querySelector('.item-spec');
        if (specBox) specBox.classList.toggle('show', c.checked);
      }
      recomputePc();
    });
  });
  recomputePc();

  // ---- Categorías colapsables ----
  document.querySelectorAll('[data-toggle]').forEach(function(h){
    h.addEventListener('click', function(){
      var body = h.parentElement.querySelector('.cat-body');
      body.style.display = (body.style.display === 'none') ? 'block' : 'none';
    });
  });

  // ---- Resumen final ----
  function renderSummary(){
    var out = document.getElementById('wizSummary');
    var r1 = document.querySelector('input[name=raza_principal]:checked');
    var r2opt = hibChk.checked ? document.getElementById('razaSecundaria').selectedOptions[0] : null;
    var razaTxt = r1 ? r1.dataset.nombre : '—';
    if (r2opt && r2opt.value) razaTxt += ' × ' + r2opt.textContent + ' (híbrido)';

    var nombre = form.querySelector('[name=nombre]').value || '—';
    var faccionEl = document.querySelector('input[name=faccion]:checked');
    var faccionTxt = faccionEl ? faccionEl.closest('.fac-card').querySelector('.fac-name').textContent : '—';
    var armaTxt = document.getElementById('armaSel').selectedOptions[0] ? document.getElementById('armaSel').selectedOptions[0].textContent : '—';

    var virtudesNames = [];
    document.querySelectorAll('input[data-coste]:checked').forEach(function(c){ virtudesNames.push(c.closest('.item-row').querySelector('.item-name').firstChild.textContent.trim()); });
    var defectosNames = [];
    document.querySelectorAll('input[data-devuelve]:checked').forEach(function(c){ defectosNames.push(c.closest('.item-row').querySelector('.item-name').firstChild.textContent.trim()); });

    out.innerHTML =
      '<div class="sum-block"><h4>Identidad</h4>' +
      '<div class="line"><b>Nombre</b>' + nombre + '</div>' +
      '<div class="line"><b>Raza</b>' + razaTxt + '</div>' +
      '<div class="line"><b>Facción</b>' + faccionTxt + '</div></div>' +
      '<div class="sum-block"><h4>Estadísticas</h4>' +
      '<div class="line"><b>Suma total</b>' + document.getElementById('statSum').textContent + ' — Rango ' + document.getElementById('statRank').textContent + '</div></div>' +
      '<div class="sum-block"><h4>Virtudes (' + virtudesNames.length + ')</h4><div class="line">' + (virtudesNames.join(', ') || 'Ninguna') + '</div></div>' +
      '<div class="sum-block"><h4>Defectos (' + defectosNames.length + ')</h4><div class="line">' + (defectosNames.join(', ') || 'Ninguno') + '</div></div>' +
      '<div class="sum-block"><h4>Equipo</h4><div class="line"><b>Arma</b>' + armaTxt + '</div><div class="line"><b>Berries</b>' + document.getElementById('berriesOut').textContent + '</div></div>';
  }

  showStep(1);
})();
</script>
<?php endif; ?>

</body>
</html>
