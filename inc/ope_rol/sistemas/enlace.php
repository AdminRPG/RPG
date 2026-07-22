<?php
/**
 * One Piece: Eternal · Sistema de Enlace (One Piece: Eternal)
 * -----------------------------------------------------
 * Gestiona el nivel de Enlace (progresión), la criatura activa
 * y las invocaciones (summon) del personaje.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Escala de Nivel de Enlace, usos requeridos y costes PP.
 * Según docs/02-ENLACES/SISTEMA-DE-ENLACE.md.
 */
function ope_enlace_niveles()
{
    return array(
        1 => array('nombre' => 'Enlace Nivel 1', 'coste_pp' => 0,    'usos_requeridos' => 0,   'usos_max' => 1),
        2 => array('nombre' => 'Enlace Nivel 2', 'coste_pp' => 200,  'usos_requeridos' => 20,  'usos_max' => 2),
        3 => array('nombre' => 'Enlace Nivel 3', 'coste_pp' => 400,  'usos_requeridos' => 40,  'usos_max' => 2),
        4 => array('nombre' => 'Enlace Nivel 4', 'coste_pp' => 700,  'usos_requeridos' => 70,  'usos_max' => 3),
        5 => array('nombre' => 'Enlace Nivel 5', 'coste_pp' => 1000, 'usos_requeridos' => 110, 'usos_max' => 3),
        6 => array('nombre' => 'Enlace Nivel 6', 'coste_pp' => 1500, 'usos_requeridos' => 160, 'usos_max' => 4),
        'primal' => array('nombre' => 'Pacto Primal', 'coste_pp' => 300, 'usos_requeridos' => 999, 'usos_max' => 4)
    );
}

/**
 * Obtiene el Enlace de un personaje.
 * Si no existe, inicializa robustamente leyendo la criatura que eligió en creación.
 */
function ope_enlace_get($pid)
{
    global $db;
    $pid = (int)$pid;
    $default = array(
        'pid' => $pid,
        'criatura' => '',
        'nivel' => 1,
        'usos' => 0,
        'pp_gastado' => 0
    );

    if ($pid < 1 || !$db->table_exists('rol_enlace')) {
        return $default;
    }

    $q = $db->simple_select('rol_enlace', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $row = $db->fetch_array($q);
        return array(
            'pid' => $pid,
            'criatura' => $row['criatura'],
            'nivel' => (int)$row['nivel'],
            'usos' => (int)$row['usos'],
            'pp_gastado' => (int)$row['pp_gastado']
        );
    }

    // Robust Fallback: si no hay fila de enlace pero el PJ existe,
    // extraemos la criatura elegida en la creación (rol_personajes.datos)
    if ($db->table_exists('rol_personajes')) {
        $qp = $db->simple_select('rol_personajes', 'datos', "pid = {$pid}", array('limit' => 1));
        if ($db->num_rows($qp)) {
            $prow = $db->fetch_array($qp);
            $pdatos = json_decode($prow['datos'], true);
            if (isset($pdatos['enlace'])) {
                $criatura = $pdatos['enlace'];
                $insert_data = array(
                    'pid' => $pid,
                    'criatura' => $db->escape_string($criatura),
                    'nivel' => 1,
                    'usos' => 0,
                    'pp_gastado' => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                );
                $db->insert_query('rol_enlace', $insert_data);
                return array(
                    'pid' => $pid,
                    'criatura' => $criatura,
                    'nivel' => 1,
                    'usos' => 0,
                    'pp_gastado' => 0
                );
            }
        }
    }

    return $default;
}

/**
 * Intenta subir el nivel de Enlace del personaje.
 * @return string Mensaje de error (vacío si tiene éxito).
 */
function ope_enlace_subir($pid)
{
    global $db;
    $pid = (int)$pid;
    $current = ope_enlace_get($pid);

    if (empty($current['criatura'])) {
        return 'El personaje no tiene ninguna criatura enlazada.';
    }

    $nivel_actual = $current['nivel'];
    if ($nivel_actual >= 6) {
        return 'Ya has alcanzado el Nivel 6 de Enlace. El Pacto Primal requiere tramas del staff.';
    }

    $siguiente = $nivel_actual + 1;
    $niveles = ope_enlace_niveles();
    $info = $niveles[$siguiente];

    if ($current['usos'] < $info['usos_requeridos']) {
        return "Necesitas invocar tu Enlace {$info['usos_requeridos']} veces (tienes {$current['usos']}).";
    }

    $coste = $info['coste_pp'];

    $db->write_query('START TRANSACTION');
    try {
        require_once MYBB_ROOT . 'inc/ope_rol/core/system.php';
        $ok = ope_pp_spend($pid, $coste, 'gasto_enlace', "Subida Enlace a Nivel {$siguiente}");
        if (!$ok) {
            $db->write_query('ROLLBACK');
            return "No tienes suficientes PP. Necesitas {$coste} PP.";
        }

        $db->update_query('rol_enlace', array(
            'nivel' => $siguiente,
            'pp_gastado' => $current['pp_gastado'] + $coste,
            'updated_at' => date('Y-m-d H:i:s')
        ), "pid = {$pid}");

        $db->write_query('COMMIT');
    } catch (Exception $e) {
        $db->write_query('ROLLBACK');
        return 'Error interno de base de datos al subir nivel de Enlace.';
    }

    return ''; // éxito
}

/**
 * Cambia la criatura enlazada del personaje.
 * @return string Mensaje de error (vacío si tiene éxito).
 */
function ope_enlace_cambiar($pid, $nueva_criatura, $es_ascenso = false)
{
    global $db;
    $pid = (int)$pid;
    $current = ope_enlace_get($pid);

    $coste = $es_ascenso ? 150 : 100;

    $db->write_query('START TRANSACTION');
    try {
        require_once MYBB_ROOT . 'inc/ope_rol/core/system.php';
        $ok = ope_pp_spend($pid, $coste, 'cambio_enlace', "Cambiar criatura a {$nueva_criatura}");
        if (!$ok) {
            $db->write_query('ROLLBACK');
            return "No tienes suficientes PP. Necesitas {$coste} PP.";
        }

        $db->update_query('rol_enlace', array(
            'criatura' => $db->escape_string($nueva_criatura),
            'updated_at' => date('Y-m-d H:i:s')
        ), "pid = {$pid}");

        $db->write_query('COMMIT');
    } catch (Exception $e) {
        $db->write_query('ROLLBACK');
        return 'Error interno de base de datos al cambiar la criatura.';
    }

    return ''; // éxito
}
