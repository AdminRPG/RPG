<?php
/**
 * Script para equipar a todos los NPCs (Sigrun, Rolf, Halvar, Ysolde, Draven)
 * con sus respectivas Akuma no Mi en rol_pj_fruta, sus Nodos Eternal en rol_pj_eternal,
 * y sus Rasgos/Factor Linaje en el JSON de datos de rol_personajes.
 */

define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$npcs_data = array(
    // 1. Sigrun D. Basterra (PID 6)
    6 => array(
        'arbol_identidad' => 'identidad-coloso',
        'nodos_identidad' => array('coloso-peso-t1', 'coloso-peso-t2', 'coloso-peso-t3', 'coloso-peso-t4', 'coloso-pinaculo-peso'),
        'arbol_arma'      => 'arma-cuerpo',
        'nodos_arma'       => array('cuerpo-impacto-marcial-t1', 'cuerpo-impacto-marcial-t2', 'cuerpo-impacto-marcial-t3', 'cuerpo-impacto-marcial-t4', 'cuerpo-pinaculo-impacto-marcial'),
        'fruta_id'        => 34, // Zushi Zushi no Mi
        'fruta_slug'      => 'fruta.zushi_zushi',
        'fruta_nombre'    => 'Zushi Zushi no Mi',
        'fruta_sec'       => 'TEM',
        'factor_linaje'   => array(
            'buccaneers' => array('nombre' => 'Voluntad Buccaneer', 'spec' => 'Fuerza y resistencia colosal (+6 RES, -2 CAR)', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Zushi Zushi no Mi (Gravedad)', 'spec' => 'Paramecia Tier V. Aplasta con gravedad, invierte superficies, atrae meteoros. Potencia TEM+VOL.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'coloso'     => array('nombre' => 'Coloso — Peso Absoluto', 'spec' => 'Acumula Mole y remata con daño multiplicado sin tope.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cuerpo'     => array('nombre' => 'Puño de Hierro — Puño de Dios', 'spec' => 'Golpe concentrado que penetra toda defensa como acción normal.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'hao'        => array('nombre' => 'Haki del Conquistador (Rey)', 'spec' => 'Dobla la voluntad de ejércitos enteros.', 'valor' => 0, 'tipo' => 'dote_innata')
        )
    ),

    // 2. Rolf D. Basterra (PID 7)
    7 => array(
        'arbol_identidad' => 'identidad-duelista',
        'nodos_identidad' => array('duelista-precision-t1', 'duelista-precision-t2', 'duelista-precision-t3', 'duelista-precision-t4', 'duelista-pinaculo-precision'),
        'arbol_arma'      => 'arma-filo',
        'nodos_arma'       => array('filo-apertura-t1', 'filo-apertura-t2', 'filo-apertura-t3', 'filo-apertura-t4', 'filo-pinaculo-apertura'),
        'fruta_id'        => null,
        'fruta_slug'      => null,
        'fruta_nombre'    => null,
        'fruta_sec'       => null,
        'factor_linaje'   => array(
            'humanos'    => array('nombre' => 'Adaptabilidad Humana', 'spec' => 'Improvisar y resistir ante la adversidad.', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'haki_puro'  => array('nombre' => 'Haki Puro (Sin Fruta)', 'spec' => 'Conquistó el Grand Line sin Akuma no Mi. Presciencia y Hao de rey.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'duelista'   => array('nombre' => 'Duelista — Punto Mortal', 'spec' => 'Cortes que ignoran la mitigación física; no se esquivan ni bloquean.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'filo'       => array('nombre' => 'Filo — Mil Cortes', 'spec' => 'Sangrado imparable que se transfiere al ejecutar.', 'valor' => 0, 'tipo' => 'dote_innata')
        )
    ),

    // 3. Halvar (PID 8)
    8 => array(
        'arbol_identidad' => 'identidad-centinela',
        'nodos_identidad' => array('centinela-bastion-t1', 'centinela-bastion-t2', 'centinela-bastion-t3', 'centinela-bastion-t4', 'centinela-pinaculo-bastion'),
        'arbol_arma'      => 'arma-alcance',
        'nodos_arma'       => array('alcance-control-t1', 'alcance-control-t2', 'alcance-control-t3', 'alcance-control-t4', 'alcance-pinaculo-control'),
        'fruta_id'        => 16, // Hie Hie no Mi
        'fruta_slug'      => 'fruta.hie_hie',
        'fruta_nombre'    => 'Hie Hie no Mi',
        'fruta_sec'       => 'VOL',
        'factor_linaje'   => array(
            'humanos'    => array('nombre' => 'Adaptabilidad Humana', 'spec' => 'Improvisar y resistir ante la adversidad.', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Hie Hie no Mi (Hielo)', 'spec' => 'Logia Tier IV. Congela mares (Era de Hielo), lanzas de escarcha, congelación biológica.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'centinela'  => array('nombre' => 'Centinela — Bastión', 'spec' => 'Muro inamovible; ancla y protege la zona.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'alcance'    => array('nombre' => 'Alcance — Control', 'spec' => 'Engancha, ata y enraíza al enemigo a distancia.', 'valor' => 0, 'tipo' => 'dote_innata')
        )
    ),

    // 4. Ysolde (PID 9)
    9 => array(
        'arbol_identidad' => 'identidad-cazador',
        'nodos_identidad' => array('cazador-marcaje-t1', 'cazador-marcaje-t2', 'cazador-marcaje-t3', 'cazador-marcaje-t4', 'cazador-pinaculo-marcaje'),
        'arbol_arma'      => 'arma-distancia',
        'nodos_arma'       => array('distancia-precision-t1', 'distancia-precision-t2', 'distancia-precision-t3', 'distancia-precision-t4', 'distancia-pinaculo-precision'),
        'fruta_id'        => null,
        'fruta_slug'      => null,
        'fruta_nombre'    => null,
        'fruta_sec'       => null,
        'factor_linaje'   => array(
            'minks'      => array('nombre' => 'Latido Salvaje + Electro', 'spec' => 'Descarga eléctrica en sus ataques (+4 AGI, +4 FUE, -4 VOL).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'sulong'     => array('nombre' => 'Sulong (Luna Llena)', 'spec' => 'Transformación letal que dispara sus capacidades bajo luna llena.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cazador'    => array('nombre' => 'Cazador — Marcaje', 'spec' => 'Acumula Rastro sobre la presa y remata más fuerte cuanto más la persigue.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'distancia'  => array('nombre' => 'Distancia — Precisión', 'spec' => 'Un tiro, una bala: marca y explota debilidades a kilómetros.', 'valor' => 0, 'tipo' => 'dote_innata')
        )
    ),

    // 5. Draven (PID 10)
    10 => array(
        'arbol_identidad' => 'identidad-verdugo',
        'nodos_identidad' => array('verdugo-sentencia-t1', 'verdugo-sentencia-t2', 'verdugo-sentencia-t3', 'verdugo-sentencia-t4', 'verdugo-pinaculo-sentencia'),
        'arbol_arma'      => 'arma-contundente',
        'nodos_arma'       => array('contundente-impacto-t1', 'contundente-impacto-t2', 'contundente-impacto-t3', 'contundente-impacto-t4', 'contundente-pinaculo-impacto'),
        'fruta_id'        => null,
        'fruta_slug'      => null,
        'fruta_nombre'    => null,
        'fruta_sec'       => null,
        'factor_linaje'   => array(
            'gyojins'    => array('nombre' => 'Piel de Abismo + Hijo del Mar', 'spec' => 'Piel acorazada e inmunidad acuática (+6 FUE, -2 PER).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'karate'     => array('nombre' => 'Karate Gyojin', 'spec' => 'Bajo el agua sus golpes ganan alcance y potencia (chorros de agua a presión).', 'valor' => 0, 'tipo' => 'dote_innata'),
            'verdugo'    => array('nombre' => 'Verdugo — Sentencia', 'spec' => 'Acumula Dominio sobre el controlado y lo remata sin vuelta atrás.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'contundente'=> array('nombre' => 'Contundente — Impacto', 'spec' => 'Rotura de guardia y aturdimiento con maza pesada.', 'valor' => 0, 'tipo' => 'dote_innata')
        )
    )
);

echo "=== ACTUALIZANDO NPCS EN BD ===\n";

foreach ($npcs_data as $pid => $cfg) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        echo "PID {$pid} no encontrado en rol_personajes.\n";
        continue;
    }

    $pj = $db->fetch_array($q);
    echo "Procesando PID {$pid}: {$pj['nombre']}...\n";

    // 1. Actualizar JSON datos
    $datos = json_decode((string) $pj['datos'], true) ?: array();
    $datos['arbol_identidad'] = $cfg['arbol_identidad'];
    $datos['arbol_arma'] = $cfg['arbol_arma'];
    $datos['arbol_identidad_nodos'] = $cfg['nodos_identidad'];
    $datos['arbol_arma_nodos'] = $cfg['nodos_arma'];
    $datos['fruta_slug'] = $cfg['fruta_slug'];
    $datos['fruta_nombre'] = $cfg['fruta_nombre'];
    $datos['factor_linaje'] = $cfg['factor_linaje'];

    $db->update_query('rol_personajes', array(
        'datos' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
        'lastedit' => TIME_NOW
    ), "pid = {$pid}");

    // 2. Actualizar rol_pj_eternal
    if ($db->table_exists('rol_pj_eternal')) {
        $db->delete_query('rol_pj_eternal', "pid = {$pid}");
        
        foreach ($cfg['nodos_identidad'] as $nid) {
            $db->insert_query('rol_pj_eternal', array(
                'pid' => $pid,
                'arbol' => $cfg['arbol_identidad'],
                'nodo_id' => $nid,
                'dateline' => TIME_NOW
            ));
        }

        foreach ($cfg['nodos_arma'] as $nid) {
            $db->insert_query('rol_pj_eternal', array(
                'pid' => $pid,
                'arbol' => $cfg['arbol_arma'],
                'nodo_id' => $nid,
                'dateline' => TIME_NOW
            ));
        }
        echo "  - Nodos Eternal insertados en rol_pj_eternal.\n";
    }

    // 3. Actualizar rol_pj_fruta
    if ($db->table_exists('rol_pj_fruta')) {
        $db->delete_query('rol_pj_fruta', "pid = {$pid}");

        if ($cfg['fruta_id'] !== null) {
            $db->insert_query('rol_pj_fruta', array(
                'pid' => $pid,
                'fruta_id' => $cfg['fruta_id'],
                'nivel' => 3, // Despertar / Nivel Máximo para NPCs Almirantes
                'cu' => 120,
                'pp_gastado' => 0,
                'origen' => 'inicial',
                'potencia_sec' => $cfg['fruta_sec'] ?: 'INT',
                'fecha_despertar' => TIME_NOW,
                'dateline' => TIME_NOW,
                'lastedit' => TIME_NOW
            ));

            // Marcar fruta como ocupada en rol_akuma si existe ocupada_pid
            if ($db->field_exists('ocupada_pid', 'rol_akuma')) {
                $db->update_query('rol_akuma', array('ocupada_pid' => $pid), "id = {$cfg['fruta_id']}");
            }
            echo "  - Akuma no Mi (ID {$cfg['fruta_id']}) asignada en rol_pj_fruta.\n";
        } else {
            echo "  - Sin fruta asignada (Haki puro / estilo físico).\n";
        }
    }
}

echo "\n¡POBLACIÓN COMPLETADA CON ÉXITO!\n";
