<?php
/**
 * Catálogo OP adicional: identidades, familias de arma, virtudes v2, facciones.
 * Se carga DESPUÉS de las definiciones base en ope_rol_data.php solo si
 * necesitamos sobrescribir — en la práctica este archivo se incluye desde
 * ope_rol_data.php al final de las secciones de catálogo (antes de técnicas).
 *
 * NO incluir directamente desde páginas: entra vía ope_rol_data.php.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** 7 Identidades Eternal (Nucleo-Sistema.md). */
function ope_rol_identidades()
{
    return array(
        'coloso' => array(
            'nombre' => 'Coloso',
            'arbol' => 'identidad-coloso',
            'recurso' => 'Mole',
            'rol' => 'Burst / tank ofensivo',
            'resumen' => 'La fuerza es una verdad que no necesita demostrarse dos veces. Golpes que aplastan y resistencia bruta.',
        ),
        'duelista' => array(
            'nombre' => 'Duelista',
            'arbol' => 'identidad-duelista',
            'recurso' => 'Apertura',
            'rol' => 'Tempo / contragolpe',
            'resumen' => 'El combate es un intercambio de errores. Precisión, tempo y castigo del fallo rival.',
        ),
        'verdugo' => array(
            'nombre' => 'Verdugo',
            'arbol' => 'identidad-verdugo',
            'recurso' => 'Dominio',
            'rol' => 'Control / sometimiento',
            'resumen' => 'No gana quien pega más fuerte: gana quien decide cuándo termina la pelea.',
        ),
        'fantasma' => array(
            'nombre' => 'Fantasma',
            'arbol' => 'identidad-fantasma',
            'recurso' => 'Impulso',
            'rol' => 'Movilidad / evasión',
            'resumen' => 'Si no pueden alcanzarte, no pueden vencerte. Golpear y desaparecer.',
        ),
        'centinela' => array(
            'nombre' => 'Centinela',
            'arbol' => 'identidad-centinela',
            'recurso' => 'Resistencia',
            'rol' => 'Tank / protección',
            'resumen' => 'Hay líneas que no se cruzan mientras tú estés de pie. Muro viviente.',
        ),
        'cazador' => array(
            'nombre' => 'Cazador',
            'arbol' => 'identidad-cazador',
            'recurso' => 'Rastro',
            'rol' => 'Desgaste / marcaje',
            'resumen' => 'La presa no necesita caer de un golpe: solo cansarse antes que tú.',
        ),
        'detonador' => array(
            'nombre' => 'Detonador',
            'arbol' => 'identidad-detonador',
            'recurso' => 'Carga',
            'rol' => 'Todo o nada',
            'resumen' => 'Todo lo que eres cabe en un solo instante decisivo. Riesgo/recompensa extremo.',
        ),
    );
}

/** 5 Familias de Arma Eternal. */
function ope_rol_familias_arma()
{
    return array(
        'filo' => array(
            'nombre' => 'Filo',
            'arbol' => 'arma-filo',
            'efecto' => 'Sangrado acumulativo',
            'resumen' => 'Cortes, katanas, dagas: daño que sangra y se acumula.',
            'armas' => array('espada', 'hacha', 'daga', 'ropera', 'arma_pesada_corte'),
        ),
        'contundente' => array(
            'nombre' => 'Contundente',
            'arbol' => 'arma-contundente',
            'efecto' => 'Rotura de guardia / aturdimiento',
            'resumen' => 'Mazas, martillos, bastones: romper guardias y posiciones.',
            'armas' => array('maza', 'baston', 'arma_pesada_impacto', 'escudo'),
        ),
        'alcance' => array(
            'nombre' => 'Alcance',
            'arbol' => 'arma-alcance',
            'efecto' => 'Control espacial',
            'resumen' => 'Lanzas, alabardas, látigos: controlar el espacio.',
            'armas' => array('lanza', 'latigo'),
        ),
        'distancia' => array(
            'nombre' => 'Distancia',
            'arbol' => 'arma-distancia',
            'efecto' => 'Daño a distancia / posicionamiento',
            'resumen' => 'Arcos, armas de fuego, arrojadizas: pegar desde lejos.',
            'armas' => array('arco', 'arma_fuego', 'arrojadiza'),
        ),
        'cuerpo' => array(
            'nombre' => 'Cuerpo',
            'arbol' => 'arma-cuerpo',
            'efecto' => 'Combos a alcance cero',
            'resumen' => 'Puños y estilos marciales: ritmo, combos, cero distancia.',
            'armas' => array('punio', 'punio_hierro'),
        ),
    );
}
