<?php
/**
 * One Piece: 7 Seas · Narradores y Misiones (zona staff — Anexo A.3, F5.2)
 * ------------------------------------------------------------------------
 * Panel «Narradores y Misiones» (5.20/cap. 21): tablón CRUD de la ficha de
 * 6 bloques (secretos solo staff/narradores), narradores con cupo de 2
 * simultáneas, auto-narradas en curso con tramos y oráculos, histórico de
 * cierres y sucesos de misión en borrador. Acceso: staff + narradores
 * habilitados (21.2); las acciones de escritura solo staff.
 * Scope CSS: body.ope-pg-misiones-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'misiones-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff_o_narrador') || !ope7_es_staff_o_narrador($uid)) {
    error('No tienes permisos para acceder al panel de narradores y misiones.');
}
$es_staff = ope7_es_staff($uid);

$flash = '';
if ($mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'))) {
    $gaccion = (string) $mybb->get_input('gaccion');
    if (!$es_staff) {
        $flash = 'Solo el staff puede escribir en este panel (los narradores solo consultan).';
    } elseif ($gaccion === 'crear_mision') {
        // Ficha de 6 bloques desde el formulario del tablón (borrador).
        $escenas_raw = trim((string) $mybb->get_input('escenas'));
        $actos = preg_split('/\r?\n\s*(?:Acto\s*\d\s*:?|acto\s*\d\s*:?|—|--)/u', $escenas_raw);
        $actos = array_values(array_filter(array_map(function ($a) { return trim($a, " \t:·•-\r\n"); }, $actos)));
        while (count($actos) < 3) {
            $actos[] = '';
        }
        $recompensas = json_decode((string) $mybb->get_input('recompensas'), true);
        if (!is_array($recompensas)) {
            $recompensas = array();
        }
        $requisitos = json_decode((string) $mybb->get_input('requisitos'), true);
        if (!is_array($requisitos)) {
            $requisitos = array();
        }
        $identidad = array(
            'nombre'    => trim((string) $mybb->get_input('nombre')),
            'categoria' => (string) $mybb->get_input('categoria'),
            'origen'    => trim((string) $mybb->get_input('origen')),
            'dificultad'=> trim((string) $mybb->get_input('dificultad')),
            'duracion'  => max(1, min(12, (int) $mybb->get_input('duracion_rondas', 1))),
        );
        $secretos = array('texto' => trim((string) $mybb->get_input('secretos')));
        $ficha = array(
            'identidad'     => $identidad,
            'condiciones'   => array(
                'victoria' => trim((string) $mybb->get_input('cond_victoria')),
                'fracaso'  => trim((string) $mybb->get_input('cond_fracaso')),
            ),
            'escenas'       => array('acto1' => $actos[0], 'acto2' => $actos[1], 'acto3' => $actos[2]),
            'recompensas'   => $recompensas,
            'requisitos'    => $requisitos,
            'secretos_json' => $secretos,
        );
        $val = ope7_mision_ficha_valida($ficha);
        if (!$val['ok']) {
            $flash = 'Ficha incompleta: ' . $val['msg'];
        } else {
            $isla_id = (int) $mybb->get_input('isla_id', 1);
            $db->insert_query('ope_misiones', array(
                'categoria'      => in_array($identidad['categoria'], ope7_mision_categorias(), true) ? $identidad['categoria'] : 'reino_isla',
                'origen'         => $identidad['origen'],
                'isla_id'        => $isla_id > 0 ? $isla_id : null,
                'dificultad'     => $identidad['dificultad'],
                'duracion_rondas'=> $identidad['duracion'],
                'identidad'      => json_encode($identidad, JSON_UNESCAPED_UNICODE),
                'condiciones'    => json_encode($ficha['condiciones'], JSON_UNESCAPED_UNICODE),
                'escenas'        => json_encode($ficha['escenas'], JSON_UNESCAPED_UNICODE),
                'recompensas'    => json_encode($recompensas, JSON_UNESCAPED_UNICODE),
                'requisitos'     => json_encode($requisitos, JSON_UNESCAPED_UNICODE),
                'secretos_json'  => json_encode($secretos, JSON_UNESCAPED_UNICODE),
                'estado'         => 'borrador',
                'en_tablon'      => 0,
            ));
            $flash = 'Misión guardada como borrador: ' . $val['msg'] . ' Publícala cuando la tengas lista.';
        }
    } elseif ($gaccion === 'publicar_mision') {
        $mid = (int) $mybb->get_input('mision_id', 1);
        $m = $mid > 0 ? ope7_mision_get($mid) : null;
        if (!$m) {
            $flash = 'Misión no encontrada.';
        } else {
            $val = ope7_mision_ficha_valida($m);
            if (!$val['ok']) {
                $flash = 'No se puede publicar: ' . $val['msg'];
            } else {
                $db->update_query('ope_misiones', array('estado' => 'publicada', 'en_tablon' => 1), "id = {$mid}");
                $flash = 'Misión #' . $mid . ' publicada en el tablón (' . $val['msg'] . ').';
            }
        }
    } elseif ($gaccion === 'archivar_mision') {
        $mid = (int) $mybb->get_input('mision_id', 1);
        $db->update_query('ope_misiones', array('estado' => 'archivada', 'en_tablon' => 0), "id = {$mid}");
        $flash = 'Misión #' . $mid . ' archivada (fuera del tablón).';
    } elseif ($gaccion === 'cerrar_mision') {
        // Crea el trámite 55 (staff-only, mismo motor): el staff firma el cierre.
        $mid = (int) $mybb->get_input('mision_id', 1);
        $m = $mid > 0 ? ope7_mision_get($mid) : null;
        if (!$m) {
            $flash = 'Misión no encontrada.';
        } else {
            $r = ope7_tramite_crear($uid, (int) ($m['solicitante_id'] ?? 0), 55, 'Cierre de misión desde el panel',
                array('mision_id' => $mid, 'mision' => (string) ($m['identidad']['nombre'] ?? '#' . $mid)));
            $flash = $r['ok'] ? 'Trámite 55 creado: verifica condiciones y firma el cierre en la bandeja.' : $r['msg'];
        }
    } elseif ($gaccion === 'publicar_suceso') {
        $sid = (int) $mybb->get_input('suceso_id', 1);
        $flash = ope7_mision_suceso_publicar($sid) ? 'Suceso publicado: ya forma parte del Mundo Vivo.' : 'No se pudo publicar el suceso.';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Narradores y Misiones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-misiones-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Narradores y Misiones</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_misiones_panel_html(); ?>
</div>
<?php include 'inc/footer_custom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ob = new IntersectionObserver(function (en) {
        en.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('revealed'); ob.unobserve(e.target); } });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(function (el) { ob.observe(el); });
});
</script>
</body>
</html>