<?php
if (!defined('IN_MYBB')) { die('Direct initialization of this file is not allowed.'); }

/**
 * Catálogos de creación de PJ — One Piece: Eternal.
 * Fuente: ARMAS.md, FACCIONES.md, FACTOR-LINAJE.md (I-Forge-Sistema).
 * Catálogo canónico de creación de PJ; se carga desde ope_rol_data.php.
 */

function ope_rol_armas()
{
    return array(
        'espada' => array(
            'nombre' => 'Espada / Katana',
            'familia' => 'filo',
            'escala' => array('FUE'),
            'tecnica' => 'Tajo Firme',
            'efecto' => 'Daño equilibrado.',
        ),
        'hacha' => array(
            'nombre' => 'Hacha',
            'familia' => 'filo',
            'escala' => array('FUE'),
            'tecnica' => 'Golpe Demoledor',
            'efecto' => 'Daño alto; ignora parte de Escudado.',
        ),
        'daga' => array(
            'nombre' => 'Daga',
            'familia' => 'filo',
            'escala' => array('AGI'),
            'tecnica' => 'Corte Rápido',
            'efecto' => 'Bajo coste; Sangrado leve.',
        ),
        'ropera' => array(
            'nombre' => 'Ropera / Estoque',
            'familia' => 'filo',
            'escala' => array('AGI'),
            'tecnica' => 'Finta y Estocada',
            'efecto' => 'Daño + puede aplicar Marcado.',
        ),
        'arma_pesada_corte' => array(
            'nombre' => 'Arma pesada (corte)',
            'familia' => 'filo',
            'escala' => array('FUE'),
            'tecnica' => 'Impacto Cortante',
            'efecto' => 'Daño alto lento.',
        ),
        'maza' => array(
            'nombre' => 'Maza / Martillo / Garrote',
            'familia' => 'contundente',
            'escala' => array('FUE'),
            'tecnica' => 'Mazazo Firme',
            'efecto' => 'Rotura de guardia.',
        ),
        'baston' => array(
            'nombre' => 'Bastón de combate',
            'familia' => 'contundente',
            'escala' => array('FUE'),
            'tecnica' => 'Golpe con Impulso',
            'efecto' => 'Daño equilibrado.',
        ),
        'arma_pesada_impacto' => array(
            'nombre' => 'Arma pesada (impacto)',
            'familia' => 'contundente',
            'escala' => array('FUE'),
            'tecnica' => 'Impacto Sísmico',
            'efecto' => 'Daño alto; puede Ralentizar.',
        ),
        'escudo' => array(
            'nombre' => 'Escudo (como arma)',
            'familia' => 'contundente',
            'escala' => array('RES'),
            'tecnica' => 'Embate de Escudo',
            'efecto' => 'Daño bajo + auto-Escudado.',
        ),
        'lanza' => array(
            'nombre' => 'Lanza / Alabarda',
            'familia' => 'alcance',
            'escala' => array('FUE'),
            'tecnica' => 'Estocada Perforante',
            'efecto' => 'Alcance medio; ignora 25% de mitigación.',
        ),
        'latigo' => array(
            'nombre' => 'Látigo / Cadena',
            'familia' => 'alcance',
            'escala' => array('AGI'),
            'tecnica' => 'Latigazo Envolvente',
            'efecto' => 'Puede Enraizar.',
        ),
        'arco' => array(
            'nombre' => 'Arco',
            'familia' => 'distancia',
            'escala' => array('PER'),
            'tecnica' => 'Disparo Certero',
            'efecto' => 'Daño a distancia.',
        ),
        'arma_fuego' => array(
            'nombre' => 'Arma de fuego',
            'familia' => 'distancia',
            'escala' => array('PER'),
            'tecnica' => 'Tiro Perforante',
            'efecto' => 'Distancia; ignora 25% de mitigación.',
        ),
        'arrojadiza' => array(
            'nombre' => 'Arrojadiza',
            'familia' => 'distancia',
            'escala' => array('PER'),
            'tecnica' => 'Lanzamiento Múltiple',
            'efecto' => 'Hasta 2 objetivos.',
        ),
        'punio' => array(
            'nombre' => 'Puño / Marcial',
            'familia' => 'cuerpo',
            'escala' => array('AGI'),
            'tecnica' => 'Ráfaga de Golpes',
            'efecto' => 'Impactos pequeños; puede Potenciar.',
        ),
        'punio_hierro' => array(
            'nombre' => 'Puño de Hierro',
            'familia' => 'cuerpo',
            'escala' => array('FUE'),
            'tecnica' => 'Golpe de Yunque',
            'efecto' => 'Daño alto por golpe, cadencia baja; ignora parte de Escudado.',
        ),
    );
}

function ope_rol_facciones()
{
    return array(
        'piratas' => array(
            'nombre' => 'Piratas',
            'desc' => 'Buscadores de libertad, riquezas, poder o nakamas. Operan al margen de la ley del Gobierno Mundial y surcan los mares persiguiendo el One Piece o sus propios sueños.',
            'ventaja' => 'Acceso a refugios de banda y, con RF, a mercados negros y rutas de contrabando en el Grand Line.',
        ),
        'marines' => array(
            'nombre' => 'Marines',
            'desc' => 'Fuerza militar marítima del Gobierno Mundial. Hacen cumplir la ley y defienden la Justicia frente a piratería y revolución.',
            'ventaja' => 'Acceso a cuarteles de la Marina y, con RF, patrullas y descuentos en almacenes oficiales.',
        ),
        'revolucionarios' => array(
            'nombre' => 'Revolucionarios',
            'desc' => 'Ejército clandestino que conspira para derrocar la tiranía del Gobierno Mundial y liberar reinos oprimidos por el Tributo Celestial.',
            'ventaja' => 'Acceso a casas seguras y células ocultas; con RF, contrabando médico e inteligencia de la Marina.',
        ),
        'gobierno-mundial' => array(
            'nombre' => 'Gobierno Mundial',
            'desc' => 'Élite política y de espionaje (Cipher Pol, Nobles Mundiales, Gorosei) que gobierna en la sombra y silencia la verdad.',
            'ventaja' => 'Acceso a inteligencia básica de CP y, con RF, artilugios gubernamentales y autoridad en zonas aliadas.',
        ),
        'cazarrecompensas' => array(
            'nombre' => 'Cazarrecompensas',
            'desc' => 'Cazadores de fortuna y mercenarios que persiguen piratas y rebeldes por los Berries de sus carteles. Colaboran con la Marina sin obedecerla.',
            'ventaja' => 'Registro en el sindicato y acceso a tablones de carteles; con RF, rutas de piratas novatos y armas de Kairoseki menores.',
        ),
        'civiles' => array(
            'nombre' => 'Civiles',
            'desc' => 'Comerciantes, científicos, médicos, artesanos y ciudadanos que sobreviven en una era de guerras y piratería.',
            'ventaja' => 'Sin enemigos declarados al inicio; con RF, créditos, precios reducidos y acceso a gremios de oficio.',
        ),
    );
}

/**
 * @deprecated Sustituida por ope_rol_rasgos_generales() (Factor Linaje).
 * Shim de compatibilidad: aplana el catálogo nuevo a la forma legacy
 * (nombre/valor/categoria/efecto/spec). El wizard y la ficha ya usan
 * directamente el catálogo del Factor Linaje (inc/ope_rol_catalogo_linaje.php).
 */
function ope_rol_virtudes()
{
    $out = array();
    if (!function_exists('ope_rol_rasgos_generales')) {
        return $out;
    }
    foreach (ope_rol_rasgos_generales() as $categoria => $items) {
        foreach ($items as $id => $r) {
            $out[$id] = array(
                'nombre'    => $r['nombre'],
                'valor'     => (int) $r['pl'],
                'categoria' => $categoria,
                'requiere'  => 'Nada',
                'efecto'    => $r['efecto'],
                'spec'      => !empty($r['spec']),
            );
        }
    }
    return $out;
}

/**
 * @deprecated Sustituida por ope_rol_fl_defectos() (Factor Linaje).
 * Shim de compatibilidad: mapea el catálogo nuevo a la forma legacy.
 */
function ope_rol_defectos()
{
    $out = array();
    if (!function_exists('ope_rol_fl_defectos')) {
        return $out;
    }
    foreach (ope_rol_fl_defectos() as $id => $d) {
        $out[$id] = array(
            'nombre'    => $d['nombre'],
            'valor'     => (int) $d['pl'],
            'categoria' => $d['categoria'] ?? 'General',
            'requiere'  => 'Nada',
            'efecto'    => $d['efecto'],
            'spec'      => !empty($d['spec']),
        );
    }
    return $out;
}

function ope_rol_packs_equipo()
{
    return array(
        'combatiente' => array(
            'nombre' => 'Pack del Combatiente',
            'resumen' => 'Para quien resuelve las cosas con las manos (o un arma).',
            'contenido' => array(
                '1 arma Tier 1 de la clase escogida',
                'Armadura ligera de viaje (protección básica)',
                'Vestimenta básica de marinero',
                'Raciones para 5 días',
                '2.000 Berries iniciales',
            ),
        ),
        'explorador' => array(
            'nombre' => 'Pack del Explorador',
            'resumen' => 'Para quien vive para el mar y el próximo horizonte.',
            'contenido' => array(
                'Mapas básicos del mar de inicio (East Blue u otro Blue)',
                'Kit de navegación (brújula, catalejo)',
                'Cuerda de abordaje (20 m)',
                'Vestimenta básica de marinero',
                'Raciones para 5 días',
                '2.000 Berries iniciales',
            ),
        ),
        'erudito' => array(
            'nombre' => 'Pack del Erudito',
            'resumen' => 'Para quien cura, investiga o estudia.',
            'contenido' => array(
                'Kit médico básico (vendajes, antiséptico, suturas)',
                'Diario de notas, tintero y pluma',
                'Compendio básico de islas del mar local',
                'Vestimenta básica de marinero',
                'Raciones para 5 días',
                '2.000 Berries iniciales',
            ),
        ),
        'artesano' => array(
            'nombre' => 'Pack del Artesano',
            'resumen' => 'Para quien construye, repara o forja.',
            'contenido' => array(
                'Herramientas de oficio especializadas',
                'Materiales básicos de trabajo (madera, metal común, tela)',
                'Vestimenta básica de marinero',
                'Raciones para 5 días',
                '2.000 Berries iniciales',
            ),
        ),
        'viajero' => array(
            'nombre' => 'Pack del Viajero',
            'resumen' => 'Para quien prefiere estar preparado para todo.',
            'contenido' => array(
                'Ropa de viaje de calidad (abrigos para clima variable)',
                'Cantimplora y raciones extra (7 días)',
                'Linterna y yesquero',
                '2.000 Berries iniciales',
            ),
        ),
    );
}

function ope_rol_berries_iniciales()
{
    return 2000;
}

function ope_rol_rupies_iniciales()
{
    return ope_rol_berries_iniciales();
}

function ope_rol_elementos()
{
    return array(); // apagado (sin rueda elemental OPE)
}

function ope_rol_enlace_inicial()
{
    return array(); // apagado (sin Enlace Primal)
}

function ope_rol_find_virtud($id)
{
    $all = ope_rol_virtudes();
    return isset($all[$id]) ? $all[$id] : null;
}

function ope_rol_find_defecto($id)
{
    $all = ope_rol_defectos();
    return isset($all[$id]) ? $all[$id] : null;
}

function ope_rol_armas_de_familia($familia)
{
    $out = array();
    foreach (ope_rol_armas() as $k => $a) {
        if (($a['familia'] ?? '') === $familia) {
            $out[$k] = $a;
        }
    }
    return $out;
}
