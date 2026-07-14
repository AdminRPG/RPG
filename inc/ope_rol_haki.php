<?php
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Devuelve los 3 tipos de Haki con sus descripciones.
 */
function ope_haki_tipos() {
    return array(
        'busoshoku' => array(
            'nombre' => 'Busoshoku (Armadura)',
            'desc' => 'Endurecimiento corporal. Permite dañar Logias y reforzar ataques.',
        ),
        'kenbunshoku' => array(
            'nombre' => 'Kenbunshoku (Observación)',
            'desc' => 'Percepción extrasensorial. Anticipa ataques enemigos.',
        ),
        'haoshoku' => array(
            'nombre' => 'Haoshoku (Conquistador)',
            'desc' => 'Rey supremo. Derriba a los débiles con tu sola presencia.',
        ),
    );
}

/**
 * Niveles de maestría con costes PP y requisitos de nivel.
 */
function ope_haki_niveles() {
    return array(
        1 => array('nombre' => 'Básico',    'coste_pp' => 15,  'requiere_nivel' => 5),
        2 => array('nombre' => 'Intermedio', 'coste_pp' => 40,  'requiere_nivel' => 15),
        3 => array('nombre' => 'Avanzado',   'coste_pp' => 100, 'requiere_nivel' => 30),
        4 => array('nombre' => 'Supremo',    'coste_pp' => 250, 'requiere_nivel' => 50),
    );
}

/**
 * Obtiene el estado de Haki de un personaje.
 * Devuelve array con los 3 tipos => ['nivel' => int, 'pp_gastado' => int].
 */
function ope_haki_get($pid) {
    global $db;
    $pid = (int)$pid;
    $result = array(
        'busoshoku'   => array('nivel' => 0, 'pp_gastado' => 0),
        'kenbunshoku' => array('nivel' => 0, 'pp_gastado' => 0),
        'haoshoku'    => array('nivel' => 0, 'pp_gastado' => 0),
    );
    if ($pid < 1 || !$db->table_exists('rol_haki')) return $result;
    $q = $db->simple_select('rol_haki', 'tipo, nivel, pp_gastado', "pid = {$pid}");
    while ($r = $db->fetch_array($q)) {
        $result[$r['tipo']] = array(
            'nivel' => (int)$r['nivel'],
            'pp_gastado' => (int)$r['pp_gastado'],
        );
    }
    return $result;
}

/**
 * Intenta subir un nivel de Haki. Gasta PP, valida requisitos.
 * @return string Mensaje de resultado (vacío = éxito).
 */
function ope_haki_subir($pid, $tipo) {
    global $db;
    $pid = (int)$pid;
    $tipos = ope_haki_tipos();
    if (!isset($tipos[$tipo])) return 'Tipo de Haki no válido.';
    $niveles = ope_haki_niveles();

    // Obtener estado actual
    $haki = ope_haki_get($pid);
    $nivel_actual = $haki[$tipo]['nivel'];
    if ($nivel_actual >= 4) return 'Ya has alcanzado el nivel Supremo.';

    $siguiente = $nivel_actual + 1;
    $info = $niveles[$siguiente];
    $coste = $info['coste_pp'];

    // Haoshoku solo se sube con PL (no PP)
    if ($tipo === 'haoshoku' && $siguiente >= 2) {
        return 'Haoshoku solo se puede mejorar con Puntos de Leyenda (PL).';
    }

    // Validar nivel del personaje
    $q = $db->simple_select('rol_personajes', 'nivel', "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) return 'Personaje no encontrado.';
    $pj_nivel = (int)$db->fetch_field($q, 'nivel');
    if ($pj_nivel < $info['requiere_nivel']) {
        return "Necesitas nivel {$info['requiere_nivel']} (tienes {$pj_nivel}).";
    }

    // Gastar PP y guardar Haki atómicamente
    $db->write_query('START TRANSACTION');
    try {
        $ok = ope_pp_spend($pid, $coste, 'gasto_haki', "{$tipos[$tipo]['nombre']} Nivel {$siguiente}");
        if (!$ok) {
            $db->write_query('ROLLBACK');
            return "No tienes suficientes PP. Necesitas {$coste}.";
        }

        $exist = $db->simple_select('rol_haki', 'id', "pid = {$pid} AND tipo = '{$db->escape_string($tipo)}'", array('limit' => 1));
        if ($db->num_rows($exist)) {
            $db->update_query('rol_haki', array(
                'nivel' => $siguiente,
                'pp_gastado' => $haki[$tipo]['pp_gastado'] + $coste,
                'unlocked_at' => $nivel_actual === 0 ? date('Y-m-d H:i:s') : null,
            ), "pid = {$pid} AND tipo = '{$db->escape_string($tipo)}'");
        } else {
            $db->insert_query('rol_haki', array(
                'pid' => $pid,
                'tipo' => $db->escape_string($tipo),
                'nivel' => $siguiente,
                'pp_gastado' => $coste,
                'unlocked_at' => date('Y-m-d H:i:s'),
            ));
        }

        $db->write_query('COMMIT');
    } catch (Exception $e) {
        $db->write_query('ROLLBACK');
        return 'Error interno al procesar la mejora.';
    }

    return ''; // éxito
}

/**
 * Tirada de Haoshoku. Se ejecuta al alcanzar nivel 25.
 * Requiere d100 >= 70 para desbloquear.
 * @return array ['exito' => bool, 'tirada' => int, 'mensaje' => string]
 */
function ope_haki_haoshoku_tirada($pid) {
    $haki = ope_haki_get($pid);
    if ($haki['haoshoku']['nivel'] > 0) {
        return array('exito' => true, 'tirada' => 0, 'mensaje' => 'Ya posees Haoshoku.');
    }
    $tirada = rand(1, 100);
    if ($tirada >= 70) {
        global $db;
        $db->insert_query('rol_haki', array(
            'pid' => $pid,
            'tipo' => 'haoshoku',
            'nivel' => 1,
            'pp_gastado' => 0,
            'unlocked_at' => date('Y-m-d H:i:s'),
        ));
        return array('exito' => true, 'tirada' => $tirada, 'mensaje' => "¡Has despertado el Haoshoku! (Tirada: {$tirada})");
    }
    return array('exito' => false, 'tirada' => $tirada, 'mensaje' => "No has despertado el Haoshoku. (Tirada: {$tirada}, necesitas 70+)");
}
