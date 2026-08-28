<?php
/**
 * One Piece: 7 Seas · Páginas de trámite (tramite-NN.php)
 * -----------------------------------------------------------------------------
 * Motor compartido de las ventanillas del jugador: cada trámite es un fichero
 * tramite-NN.php que delega aquí. El formulario se genera desde una
 * configuración por trámite (campos + opciones dinámicas por personaje) y al
 * enviar se enruta a ope7_tramite_crear:
 *
 *   · ligeros implementados (1, 4, 14, 17) → se ejecutan al instante;
 *   · el resto → la solicitud va a la bandeja (el staff estudia y firma).
 *
 * Los 11 trámites solo-staff NO tienen página: badge en el hub → bandeja.
 * Scope CSS: body.ope-pg-tramite. Sin estilos inline en el HTML.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Las 6 áreas del hub (agrupación cerrada de los 67). */
function ope7_tramites_areas()
{
    return array(
        array('Personaje y progreso', 'Tu ficha, tus historias, tus técnicas y tu equipo.', range(1, 14)),
        array('Economía', 'Tiendas, precios, reposiciones y personal no jugador.', range(15, 19)),
        array('Mundo Vivo', 'Facciones, bajo mundo y conquista territorial.', range(20, 37)),
        array('Viaje', 'Navegación entre mares y tu barco.', range(38, 44)),
        array('Poderes', 'Akumas, Haki y cibernética.', array_merge(range(45, 51), range(56, 61))),
        array('Grupos', 'Misiones, la muerte y tripulaciones.', array_merge(range(52, 55), range(62, 67))),
    );
}

/** Trámites que solo inicia el staff (sin página propia). */
function ope7_tramites_solo_staff()
{
    return array(18, 21, 24, 30, 36, 49, 54, 55, 59, 60, 61);
}

/** ¿Este trámite tiene página de jugador? (quien incluye a un jugador). */
function ope7_tramite_tiene_pagina($numero)
{
    $info = ope7_tramite_info($numero);
    if (!$info) {
        return false;
    }
    return in_array((string) $info['quien'], array('jugador', 'jugador-staff', 'capitan', 'capitan-staff', 'staff-jugador'), true);
}

/** Ligeros 100 % automáticos con efecto implementado (se ejecutan al instante). */
function ope7_tramite_es_auto($numero)
{
    $info = ope7_tramite_info($numero);
    return $info && $info['naturaleza'] === 'ligero' && !$info['firma']
        && in_array((int) $numero, array(1, 4, 14, 17, 26, 28, 29, 45, 47, 50), true);
}

/** Nota «qué pasa al pedir» según naturaleza. */
function ope7_tramite_pagina_nota($numero, $info)
{
    if (ope7_tramite_es_auto($numero)) {
        return 'Se ejecuta <b>al instante</b>: validación + efectos automáticos (sin esperas).';
    }
    if ($info['naturaleza'] === 'ia') {
        return 'Va a la <b>bandeja del staff</b>: el equipo la estudia y la firma antes de publicar nada. En validación de ficha (3) y técnica (13) el resultado vuelve a ti para confirmarlo.';
    }
    if ($info['naturaleza'] === 'hito') {
        return 'Hito narrativo: <b>requiere firma del staff</b> con motivo. Cuenta qué pasó en tus historias.';
    }
    return 'Va a la <b>bandeja del staff</b>, que valida y firma antes de aplicar nada.';
}

/**
 * Campos del formulario por trámite. Tipos:
 *   personaje · select (estático) · dyn (dinámico por personaje: fuente)
 *   texto · area · number · checkbox
 * Fuentes dyn: barcos, tiendas, dominios, objetos_mochila, objetos_almacen, utensilios.
 */
function ope7_tramite_pagina_campos($numero)
{
    $atributos = array(
        'fue' => 'FUE · Fuerza', 'des' => 'DES · Destreza', 'agi' => 'AGI · Agilidad',
        'res' => 'RES · Resistencia', 'per' => 'PER · Percepción', 'inte' => 'INT · Intelecto',
        'car' => 'CAR · Carisma', 'vol' => 'VOL · Voluntad',
    );
    $personaje = array('tipo' => 'personaje', 'name' => 'personaje_id', 'label' => 'Personaje', 'required' => true);

    $campos = array(
        $personaje,
        array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto para el staff', 'required' => true,
              'maxlength' => 2000,
              'hint' => 'Cuenta qué necesitas y por qué: el staff estudia tu petición con tu expediente y firma el resultado.'),
    );

    switch ((int) $numero) {
        case 1:
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'tema_titulo', 'label' => 'Título del tema', 'required' => true, 'maxlength' => 120, 'hint' => 'Con qué nombre abrirás el hilo.'),
                array('tipo' => 'select', 'name' => 'tipo', 'label' => 'Tipo de tema', 'required' => true,
                      'opciones' => array('presente' => 'Presente (se juega ahora)', 'pasado' => 'Pasado (flashback, no afecta a tu presente)')),
                array('tipo' => 'texto', 'name' => 'fecha_foro', 'label' => 'Fecha on-roll (solo pasados)', 'required' => false, 'maxlength' => 40,
                      'hint' => 'Si es un tema pasado, la fecha on-roll en la que sucede (el staff la valida).'),
                array('tipo' => 'texto', 'name' => 'mybb_tid', 'label' => 'ID del hilo del foro (opcional)', 'required' => false, 'maxlength' => 10,
                      'hint' => 'Si ya has posteado el hilo, indica su ID (el número de la URL del tema). Si no, se vincula solo al publicar el primer mensaje.'),
            );
            break;
        case 4:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'dominio_id', 'label' => 'Subir un dominio (opcional)', 'required' => false, 'fuente' => 'dominios',
                      'hint' => 'Si eliges dominio se ignora el atributo: sube UN nivel (15 días, ×1,5 el 1.º adicional / ×2 el 2.º+, D4.5).'),
                array('tipo' => 'select', 'name' => 'atributo', 'label' => 'Atributo a entrenar', 'required' => true, 'opciones' => $atributos,
                      'hint' => 'Solo si no has elegido dominio.'),
                array('tipo' => 'select', 'name' => 'bloque', 'label' => 'Bloque de entrenamiento', 'required' => true,
                      'opciones' => array('5' => '5 puntos (200 PP) — entra en reserva a los 5 días', '10' => '10 puntos (400 PP) — entra en reserva a los 10 días')),
            );
            break;
        case 13:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'idea', 'label' => 'Idea de la técnica', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'Nombre/descripción de lo que imaginas. El staff construye la ficha completa (requisitos, efectos, PA/PE) y el resultado vuelve a ti para confirmarlo.'),
                array('tipo' => 'select', 'name' => 'tier', 'label' => 'Tier deseado', 'required' => true,
                      'opciones' => array('T1' => 'T1 · 60 PP', 'T2' => 'T2 · 120 PP', 'T3' => 'T3 · 250 PP', 'T4' => 'T4 · 400 PP', 'T5' => 'T5 · 600 PP')),
            );
            break;
        case 14:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'objeto_id', 'label' => 'Objeto del almacén', 'required' => true, 'fuente' => 'objetos_almacen'),
                array('tipo' => 'select', 'name' => 'zona', 'label' => 'Dónde equiparlo', 'required' => true,
                      'opciones' => array('arma1' => 'Arma principal', 'arma2' => 'Arma secundaria', 'armadura' => 'Armadura', 'escudo' => 'Escudo', 'cinturon' => 'Cinturón')),
            );
            break;
        case 15:
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'tienda_nombre', 'label' => 'Nombre de la tienda', 'required' => true, 'maxlength' => 120),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Local, capital y plan (contexto)', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Dónde está el local (isla/zona), capital disponible y qué venderás. El staff valida Comerciante + capital + bélicos.'),
            );
            break;
        case 16:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tienda_id', 'label' => 'Tienda', 'required' => true, 'fuente' => 'tiendas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo', 'required' => true, 'maxlength' => 1000),
            );
            break;
        case 17:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tienda_id', 'label' => 'Tienda a reponer', 'required' => true, 'fuente' => 'tiendas'),
                array('tipo' => 'dyn', 'name' => 'objeto_id', 'label' => 'Objeto (desde tu almacén)', 'required' => true, 'fuente' => 'objetos_almacen'),
                array('tipo' => 'number', 'name' => 'cantidad', 'label' => 'Cantidad', 'required' => true, 'min' => 1, 'max' => 99, 'value' => 1),
            );
            break;
        case 19:
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'nombre_npc', 'label' => 'Nombre del NPC', 'required' => true, 'maxlength' => 120),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Perfil del NPC (contexto)', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'Rol que cumplirá en tu tripulación. Usa fichas existentes; los NPC no tienen ficha de combate.'),
            );
            break;
        case 20:
        case 22:
        case 23:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto narrativo', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Cuenta qué has hecho en tus historias (fama, misiones, temas cerrados): el staff cruza tu expediente y decide.'),
            );
            break;
        case 25: // Solicitar rumor a la red (5.13/14.2.3, ia+firma)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'espia_id', 'label' => 'Espía de tu red', 'required' => true, 'fuente' => 'mis_espias',
                      'hint' => 'Elige un espía activo de tu red (29). Su capacidad decide qué puede investigar y cuánto tarda (Novato local → Supremo mundial, 14.2.3).'),
                array('tipo' => 'dyn', 'name' => 'isla_id', 'label' => 'Isla del rumor (opcional)', 'required' => false, 'fuente' => 'islas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Qué quieres investigar', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'Describe el tema: el staff elabora la ficha del rumor (contenido, alcance, categoría, fiabilidad, veracidad interna) y la firma. Se cobra el mantenimiento de la ronda.'),
            );
            break;
        case 26: // Comprar rumor (5.13/14.2.2, ligero)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'rumor_id', 'label' => 'Rumor del mercado', 'required' => true, 'fuente' => 'rumores_comprables',
                      'hint' => 'Rumores activos del puesto: el precio aplica fiabilidad × frescura con el techo global (0,5×–2×, 14.2.2). La ficha queda en tu poder.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 27: // Contrastar rumor (5.13/14.4, ia+firma)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'rumor_id', 'label' => 'Rumor en tu poder', 'required' => true, 'fuente' => 'mis_rumores',
                      'hint' => 'Contrastar afina la fiabilidad un grado; en Sólido se revela la veracidad interna (14.4).'),
                array('tipo' => 'select', 'name' => 'sensibilidad', 'label' => 'Sensibilidad del objetivo (multiplica el coste)', 'required' => true,
                      'opciones' => array('comun' => 'Persona común (×1)', 'figura' => 'Figura pública (×2)', 'criminal' => 'Criminal buscado (×3)', 'oculta' => 'Identidad oculta (×5)', 'entidad' => 'Entidad (×10)')),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Fuente que consultas', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'A quién preguntas y cómo: un informante de tu red, un contacto de facción, un NPC. El staff resuelve con veredicto.'),
            );
            break;
        case 28: // Vender rumor (5.13/14.2.4, ligero)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'rumor_id', 'label' => 'Rumor que vendes', 'required' => true, 'fuente' => 'mis_rumores'),
                array('tipo' => 'dyn', 'name' => 'comprador_id', 'label' => 'Comprador (otro personaje)', 'required' => true, 'fuente' => 'pj_otros'),
                array('tipo' => 'number', 'name' => 'precio', 'label' => 'Precio acordado (฿)', 'required' => true, 'min' => 0, 'step' => 1000),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000,
                      'hint' => 'Se vende con su fiabilidad publicada: vender un dudoso como sólido es trama de reputación (14.2.4). Te quedas con tu copia.'),
            );
            break;
        case 29: // Montar/ampliar la red (5.13/14.2.3, ligero)
            $campos = array(
                $personaje,
                array('tipo' => 'select', 'name' => 'tipo', 'label' => 'Espía a incorporar', 'required' => true,
                      'opciones' => array('novato' => 'Novato · local · contrato 5.000 / mant. 500', 'avanzado' => 'Avanzado · regional · 25.000 / 2.500', 'experimentado' => 'Experimentado · regional-mundial · 100.000 / 10.000', 'supremo' => 'Supremo · mundial · 500.000 / 50.000')),
                array('tipo' => 'text', 'name' => 'nombre', 'label' => 'Nombre de la red (solo al montar)', 'required' => false, 'maxlength' => 120,
                      'hint' => 'Si ya tienes red activa se amplía; si no, se monta con este nombre. Máximo 4 espías en combos equivalentes (14.2.3). Sin mantenimiento la red se desactiva (14.5).'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 30: // Publicar cartel (5.13/14.6, staff) — sin página de jugador
            break;
        case 31: // Cobrar recompensa (5.13/14.6, ia+firma)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'cartel_id', 'label' => 'Cartel a cobrar', 'required' => true, 'fuente' => 'carteles_cobrables',
                      'hint' => 'Carteles vigentes con paradero caliente. Exige entrega verificada (tema presente con veredicto de 5.10). Autocaza bloqueada (14.6).'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Entrega (cómo y veredicto)', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'La captura jugada y resuelta: vivo o muerto según el cartel, con veredicto del combate. Sin entrega no hay cobro.'),
            );
            break;
        case 32: // Crear rumor falso (5.13/14.8, ia+firma)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'isla_id', 'label' => 'Isla donde lo siembras', 'required' => true, 'fuente' => 'islas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'El rumor que siembras', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'El contenido falso: el staff decide la fiabilidad publicada (cómo circula) y el alcance; tú lo firmas. Veracidad interna = falsa, nunca se reescribe (14.3).'),
            );
            break;
        case 33: // Ataque a una red (5.13/14.5, ia+firma)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'red_id', 'label' => 'Red objetivo', 'required' => true, 'fuente' => 'redes_ajenas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Método declarado', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'Sabotaje, infiltración, delación… el staff resuelve con veredicto qué descubre o estropea según capacidad y narrativa. Nunca dados (14.5).'),
            );
            break;
        case 34:
        case 37:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'isla_id', 'label' => 'Isla objetivo', 'required' => true, 'fuente' => 'islas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Justificación de presencia (16.2) y plan', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Cómo llegas a la isla, por qué la reclamas y con qué fuerzas. El staff valida el control previo y abre las fases del asedio.'),
            );
            break;
        case 35:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Cómo organizas la defensa', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Defensa activa del asedio: qué tropas y fortificaciones usas. Sin respuesta del defensor no hay veredicto.'),
            );
            break;
        case 38:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco / transporte', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'dyn', 'name' => 'destino_id', 'label' => 'Isla de destino', 'required' => true, 'fuente' => 'islas'),
                array('tipo' => 'dyn', 'name' => 'utensilio_id', 'label' => 'Utensilio (opcional, −12 h por tramo)', 'required' => false, 'fuente' => 'utensilios'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Acompañantes y ruta (contexto)', 'required' => false, 'maxlength' => 1000,
                      'hint' => 'Quién viaja contigo y si hay algo especial en la ruta. El staff calcula el IRT interno, el tiempo off-roll y los oráculos.'),
            );
            break;
        case 39:
        case 40:
            $campos = array(
                $personaje,
                array('tipo' => 'select', 'name' => 'tipo_id', 'label' => 'Tipo de barco', 'required' => true, 'fuente' => 'tipos_barco'),
                array('tipo' => 'select', 'name' => 'madera_id', 'label' => 'Madera del casco', 'required' => true, 'fuente' => 'maderas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (compra/construcción)', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 41:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'nivel', 'label' => 'Nivel objetivo', 'required' => true,
                      'opciones' => array('N2' => 'N2', 'N3' => 'N3'), 'hint' => 'Un paso a la vez (N1→N2→N3): el staff valida el coste (diferencia + madera).'),
            );
            break;
        case 42:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'accion', 'label' => 'Acción', 'required' => true,
                      'opciones' => array('instalar' => 'Instalar módulo', 'quitar' => 'Quitar módulo')),
                array('tipo' => 'select', 'name' => 'modulo_id', 'label' => 'Módulo', 'required' => true, 'fuente' => 'modulos'),
            );
            break;
        case 43:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'grado', 'label' => 'Grado de daño', 'required' => true,
                      'opciones' => array('leve' => 'Leve', 'moderado' => 'Moderado', 'grave' => 'Grave')),
            );
            break;
        case 44:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'accion', 'label' => 'Acción', 'required' => true,
                      'opciones' => array('vender' => 'Vender (50 % del precio)', 'desguace' => 'Desguazar (materiales)')),
            );
            break;
        case 45:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000,
                      'hint' => 'La tirada es automática (nv3+, pool por nivel): el sistema elige la fruta. Añade aquí el sabor narrativo que quieras para tu personaje.'),
            );
            break;
        case 46:
            $campos = array(
                $personaje,
                array('tipo' => 'select', 'name' => 'especificidad', 'label' => 'Qué eliges', 'required' => true,
                      'opciones' => array('familia' => 'Familia (×1) — «una Paramecia», el sistema elige cuál', 'concepto' => 'Concepto (×2) — «una fruta de fuego», el staff estudia cuál encaja', 'concreta' => 'Fruta concreta (×3) — la nombras, sujeta al cupo mundial')),
                array('tipo' => 'select', 'name' => 'tier', 'label' => 'Tier (matriz 19.7)', 'required' => true,
                      'opciones' => array('1' => 'T1 · 150 PP base (Zoan común)', '2' => 'T2 · 300 PP base (Paramecia de cuerpo)', '3' => 'T3 · 600 PP base (creación/Zoan ancestral)', '4' => 'T4 · 1.000 PP base', '5' => 'T5 · 1.500 PP base (Logia/mitológica)')),
                array('tipo' => 'select', 'name' => 'familia', 'label' => 'Familia (solo si elegiste «Familia»)', 'required' => false,
                      'opciones' => array('' => 'Cualquiera', 'paramecia' => 'Paramecia', 'zoan' => 'Zoan', 'logia' => 'Logia')),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000,
                      'hint' => 'Si eliges concepto o fruta concreta, describe aquí lo que buscas: el staff valida el encaje con la guía de frutas y firma.'),
            );
            break;
        case 47:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'akuma_id', 'label' => 'Fruta que comes', 'required' => true, 'fuente' => 'akumas',
                      'hint' => 'Tus frutas asignadas y aún sin comer (las recibes por tirada 45, compra 46 o recompensa). Una mordida basta.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 48:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Cómo lo has demostrado (19.6)', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'El despertar no se compra: se demuestra. Cuenta aquí la antigüedad como portador (meses on-roll), los temas cerrados usándola y el momento que lo merece. El staff estudia el despertar de TU fruta según su ficha y lo firma.'),
            );
            break;
        case 49:
            // Solo-staff: no tiene ventanilla (se inicia desde el panel «Akumas y Haki»).
            break;
        case 51:
            $campos = array(
                $personaje,
                array('tipo' => 'select', 'name' => 'tipo', 'label' => 'Tipo de Haki a subir', 'required' => true,
                      'opciones' => array('armadura' => 'Armadura (VOL+RES)', 'mantra' => 'Mantra (VOL+PER)', 'conquistador' => 'Conquistador (VOL+CAR)')),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (opcional)', 'required' => false, 'maxlength' => 1000,
                      'hint' => 'El staff valida usos (1 por tipo y por tema) + PP + VOL y decide el escalón; lo firma. N1→N2: 6 usos + 200 PP + VOL 55 (5.19).'),
            );
            break;
        case 52: // Solicitud de auto-narrada (5.20): elige misión del tablón
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'mision_id', 'label' => 'Misión del tablón', 'required' => true, 'fuente' => 'misiones',
                      'hint' => 'Elige una misión publicada con ficha completa (condiciones de victoria/fracaso explícitas). La misión ocupa tu único presente (5.6) y es invadible.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Por qué tu grupo la acepta', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Confirma que cumplís los requisitos y cómo llegáis a la isla. El staff valida y firma, y el primer tramo se narra a partir de vuestra ficha.'),
            );
            break;
        case 53: // Posteo de tramo (5.20): siguiente tramo de la ronda
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'mision_id', 'label' => 'Tu misión en curso', 'required' => true, 'fuente' => 'misiones_curso'),
                array('tipo' => 'area', 'name' => 'posts', 'label' => 'Resumen de los posts de esta ronda', 'required' => true, 'maxlength' => 3000,
                      'hint' => 'Pega o resume lo que vuestros personajes hicieron en el tema: se suma a la historia y se narra el siguiente tramo. Sin posts no hay tramo (21.3).'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto para el staff (opcional)', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 56: // Instalación de implante (5.22/23.2): elige del catálogo
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'implante_id', 'label' => 'Implante del catálogo', 'required' => true, 'fuente' => 'implantes',
                      'hint' => 'El staff adapta el concepto a la ficha calibrada (zona × nivel, requisitos acumulativos, balanza a 0, 5.22).'),
                array('tipo' => 'select', 'name' => 'autocirugia', 'label' => '¿Autocirugía?', 'required' => true,
                      'opciones' => array('0' => 'No (Cirujano + Ingeniero)', '1' => 'Sí (vara −1, 23.3)')),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Concepto / justificación', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Qué quieres y por qué: el staff construye la ficha y la firma. El pago (berries + PP) se cobra al instalar.'),
            );
            break;
        case 57: // Retirada de implante (23.2, ligero): elige tu implante
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'modificacion_id', 'label' => 'Implante a retirar', 'required' => true, 'fuente' => 'mis_implantes',
                      'hint' => 'Libera el cupo de la zona y la balanza; las mejoras se pierden (23.2).'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo de la retirada (obligatorio)', 'required' => true, 'maxlength' => 1000),
            );
            break;
        case 58: // Mantenimiento/reparación (23.3, ligero sin firma)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'modificacion_id', 'label' => 'Implante', 'required' => true, 'fuente' => 'mis_implantes',
                      'hint' => 'Paga el mantenimiento de la ronda (2.500/10.000/40.000 ฿ según nivel; ×2 con «Mantenimiento oneroso») o repara uno averiado (23.3).'),
            );
            break;
        case 59: // Diseño de mejora a medida (23.6, staff): ranura calibrada
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'implante_id', 'label' => 'Implante a mejorar', 'required' => true, 'fuente' => 'implantes'),
                array('tipo' => 'area', 'name' => 'concepto', 'label' => 'Concepto de la mejora', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'La ranura de habilidad especial calibrada: efecto del catálogo de 5.7 o no registrado con condiciones (guía 5.22 §4/§6).'),
            );
            break;
        case 60: // Concesión de linaje (23.7, staff): expediente × cupo
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'familia_id', 'label' => 'Familia legendaria', 'required' => true, 'fuente' => 'familias'),
                array('tipo' => 'dyn', 'name' => 'personaje_id', 'label' => 'Personaje', 'required' => true, 'fuente' => 'pj_sin_linaje'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Expediente de fidelidad / motivo', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'La herencia se juega, no se demuestra: cruza el expediente con el cupo (3–5). Dote + «La sangre llama» (−1) + suceso de ronda (23.7).'),
            );
            break;
        case 61: // Revocación de linaje (23.7, staff)
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'linaje_id', 'label' => 'Linaje a revocar', 'required' => true, 'fuente' => 'linajes_activos'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo de la revocación (obligatorio)', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'Traición al nombre o contradicciones de 5.5: se retira dote/defecto, se libera el cupo y hay suceso de ronda (23.7).'),
            );
            break;
        case 62:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'causa', 'label' => 'Causa de la muerte', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'PV ≤ −(VOL×2) o PE ≤ −RES en combate, o desenlace narrativo cerrado. El staff valida el umbral y decide la herencia y los efectos de mundo.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto del desenlace', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 63: // Fundación de tripulación (5.21-ter): capitán + fundadores + barco
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'nombre', 'label' => 'Nombre de la tripulación', 'required' => true, 'maxlength' => 120,
                      'hint' => 'Único en el foro (22.9). La banda se conoce por este nombre.'),
                array('tipo' => 'texto', 'name' => 'bandera', 'label' => 'Bandera / emblema', 'required' => false, 'maxlength' => 160),
                array('tipo' => 'area', 'name' => 'proposito', 'label' => 'Propósito de la banda', 'required' => false, 'maxlength' => 2000,
                      'hint' => 'Qué busca la tripulación: es parte de la ficha y aparece en el panel del staff.'),
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco de la banda', 'required' => true, 'fuente' => 'barcos',
                      'hint' => 'Debe ser del capitán y con plazas (5.17): los miembros ocupan espacio según su raza (Tontatta 0, Gigante 5).'),
                array('tipo' => 'dyn', 'name' => 'fundador_1', 'label' => 'Fundador 1 (mínimo 1)', 'required' => true, 'fuente' => 'pj_sin_tripulacion',
                      'hint' => 'Personaje de OTRO usuario (un PJ por usuario, 22.9) que escenifica la fundación contigo.'),
                array('tipo' => 'dyn', 'name' => 'fundador_2', 'label' => 'Fundador 2 (opcional)', 'required' => false, 'fuente' => 'pj_sin_tripulacion'),
            );
            break;
        case 64: // Ingreso en tripulación (5.21-ter, ligero): el capitán ingresa
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tripulacion_id', 'label' => 'Tu tripulación', 'required' => true, 'fuente' => 'mis_tripulaciones'),
                array('tipo' => 'dyn', 'name' => 'ingresado_id', 'label' => 'Personaje que ingresa', 'required' => true, 'fuente' => 'pj_sin_tripulacion',
                      'hint' => 'Debe estar aprobado, sin tripulación activa y con plaza en el barco (5.17). Un PJ por usuario.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto del ingreso', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 65: // Baja/expulsión (5.21-ter, ligero): el capitán expulsa
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tripulacion_id', 'label' => 'Tu tripulación', 'required' => true, 'fuente' => 'mis_tripulaciones'),
                array('tipo' => 'dyn', 'name' => 'expulsado_id', 'label' => 'Personaje que sale', 'required' => true, 'fuente' => 'miembros_trip',
                      'hint' => 'Solo miembros activos (el capitán no sale por aquí: usa 66/67).'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo de la baja (obligatorio)', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'Queda en el histórico de la tripulación; se reparte su parte del cofre común (5.9).'),
            );
            break;
        case 66: // Cambio de capitán (5.21-ter, staff): cesión o motín con veredicto
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tripulacion_id', 'label' => 'Tripulación', 'required' => true, 'fuente' => 'mis_tripulaciones',
                      'hint' => 'El staff ve todas; el jugador solo las que capitanea (motín).'),
                array('tipo' => 'dyn', 'name' => 'sucesor_id', 'label' => 'Nuevo capitán', 'required' => true, 'fuente' => 'miembros_trip'),
                array('tipo' => 'select', 'name' => 'tipo', 'label' => 'Tipo', 'required' => true,
                      'opciones' => array('cesion' => 'Cesión (voluntaria)', 'motin' => 'Motín (veredicto del staff)')),
                array('tipo' => 'texto', 'name' => 'nuevo_nombre', 'label' => 'Nuevo nombre (si la banda cambia de nombre)', 'required' => false, 'maxlength' => 120),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo / veredicto', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'Si es motín, el staff justifica el veredicto (5.10/5.14); si cambia el nombre, se genera el suceso de ronda (5.14).'),
            );
            break;
        case 67: // Disolución (5.21-ter, staff): reparto del cofre y cierre
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tripulacion_id', 'label' => 'Tripulación', 'required' => true, 'fuente' => 'mis_tripulaciones',
                      'hint' => 'El staff ve todas; el capitán puede pedir la disolución de la suya.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo de la disolución (obligatorio)', 'required' => true, 'maxlength' => 1500,
                      'hint' => 'Se reparte el cofre común entre los miembros (5.9), el barco vuelve al último capitán y la entidad se cierra (22.9).'),
            );
            break;
        default:
            break;
    }
    return $campos;
}

/** Opciones de un select dinámico (dyn) para TODOS los PJ del usuario (data-pj). */
function ope7_tramite_pagina_dyn($fuente, $uid)
{
    global $db;
    $out = array(); // [value => [label, pid]]
    $pjs = ope7_tramite_pj_opciones($uid);
    if (!$pjs) {
        return $out;
    }
    $pids = array_keys($pjs);

    switch ($fuente) {
        case 'islas':
            if (ope7_tabla_existe('islas')) {
                $q = $db->simple_select('ope_islas', '*', '1=1', array('order_by' => 'mar_id, nombre'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'], 0);
                }
            }
            break;
        case 'tipos_barco':
            if (ope7_tabla_existe('tipos_barcos')) {
                $q = $db->simple_select('ope_tipos_barcos', '*', '1=1', array('order_by' => 'id'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' · ' . ope7_objeto_precio_barco_txt($r), 0);
                }
            }
            break;
        case 'maderas':
            if (ope7_tabla_existe('maderas_casco')) {
                $q = $db->simple_select('ope_maderas_casco', '*', '1=1', array('order_by' => 'id'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'], 0);
                }
            }
            break;
        case 'modulos':
            if (ope7_tabla_existe('modulos_barcos')) {
                $q = $db->simple_select('ope_modulos_barcos', '*', '1=1', array('order_by' => 'precio'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ($r['requisito_oficio'] ? ' (' . $r['requisito_oficio'] . ')' : ''), 0);
                }
            }
            break;
        case 'barcos':
            if ($pids && ope7_tabla_existe('barcos') && ope7_tabla_existe('tipos_barcos')) {
                $q = $db->query("SELECT b.id, b.nombre, b.nivel, b.dueno_id, t.nombre AS tipo
                    FROM " . ope7_tabla_full('barcos') . " b
                    JOIN " . ope7_tabla_full('tipos_barcos') . " t ON t.id = b.tipo_id
                    WHERE b.dueno_id IN (" . implode(',', $pids) . ") AND b.estado NOT IN ('hundido','vendido')
                    ORDER BY b.id");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . $r['tipo'] . ' ' . $r['nivel'] . ')', (int) $r['dueno_id']);
                }
            }
            break;
        case 'tiendas':
            if ($pids && ope7_tabla_existe('tiendas')) {
                $q = $db->simple_select('ope_tiendas', '*', 'personaje_id IN (' . implode(',', $pids) . ')', array('order_by' => 'id'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . $r['estado'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'dominios':
            if ($pids && ope7_tabla_existe('dominios') && ope7_tabla_existe('dominios_personaje')) {
                $q = $db->query("SELECT dp.personaje_id, d.id, d.nombre, dp.nivel
                    FROM " . ope7_tabla_full('dominios_personaje') . " dp
                    JOIN " . ope7_tabla_full('dominios') . " d ON d.id = dp.dominio_id
                    WHERE dp.personaje_id IN (" . implode(',', $pids) . ")
                    ORDER BY d.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (nv ' . (int) $r['nivel'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'objetos_almacen':
            if ($pids && ope7_tabla_existe('almacen') && ope7_tabla_existe('objetos')) {
                $q = $db->query("SELECT a.personaje_id, a.objeto_id, a.cantidad, o.nombre
                    FROM " . ope7_tabla_full('almacen') . " a
                    JOIN " . ope7_tabla_full('objetos') . " o ON o.id = a.objeto_id
                    WHERE a.personaje_id IN (" . implode(',', $pids) . ") AND a.cantidad > 0
                    ORDER BY o.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['objeto_id']] = array($r['nombre'] . ' (×' . (int) $r['cantidad'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'utensilios':
            if ($pids && ope7_tabla_existe('almacen') && ope7_tabla_existe('objetos')) {
                $q = $db->query("SELECT a.personaje_id, a.objeto_id, a.cantidad, o.nombre
                    FROM " . ope7_tabla_full('almacen') . " a
                    JOIN " . ope7_tabla_full('objetos') . " o ON o.id = a.objeto_id
                    WHERE a.personaje_id IN (" . implode(',', $pids) . ") AND a.cantidad > 0
                      AND (o.nombre LIKE '%Log Pose%' OR o.nombre LIKE '%Brújula%' OR o.nombre LIKE '%Eternal%')
                    ORDER BY o.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['objeto_id']] = array($r['nombre'] . ' (×' . (int) $r['cantidad'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'akumas':
            // Frutas asignadas al PJ y aún sin comer (47): el cupo se consuma al morder.
            if ($pids && ope7_tabla_existe('akumas') && ope7_tabla_existe('personajes')) {
                $q = $db->query("SELECT a.id, a.nombre_propio, a.tier, a.familia, a.portador_id, p.akuma_id AS comido
                    FROM " . ope7_tabla_full('akumas') . " a
                    JOIN " . ope7_tabla_full('personajes') . " p ON p.id = a.portador_id
                    WHERE a.portador_id IN (" . implode(',', $pids) . ") AND a.estado = 'con_portador' AND (p.akuma_id IS NULL OR p.akuma_id = 0)
                    ORDER BY a.tier, a.nombre_propio");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre_propio'] . ' (T' . (int) $r['tier'] . ' ' . $r['familia'] . ', sin comer)', (int) $r['portador_id']);
                }
            }
            break;
        case 'misiones':
            // Misiones publicadas en el tablón (52: solicitud de auto-narrada).
            if (ope7_tabla_existe('misiones')) {
                $q = $db->simple_select('ope_misiones', '*', "estado = 'publicada' AND en_tablon = 1", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 50));
                while ($r = $db->fetch_array($q)) {
                    $ident = json_decode((string) ($r['identidad'] ?? '{}'), true);
                    $nombre = (string) ($ident['nombre'] ?? ('Misión #' . $r['id']));
                    $out[(int) $r['id']] = array($nombre . ' · ' . ((string) $r['dificultad'] !== '' ? $r['dificultad'] : 'dificultad ?') . ' · ' . (int) $r['duracion_rondas'] . ' ronda(s)', 0);
                }
            }
            break;
        case 'misiones_curso':
            // Misión en curso del PJ (53: posteo de tramo).
            if ($pids && ope7_tabla_existe('misiones')) {
                $q = $db->query("SELECT m.*, p.nombre AS pj_nombre FROM " . ope7_tabla_full('misiones') . " m
                    JOIN " . ope7_tabla_full('personajes') . " p ON p.id = m.solicitante_id
                    WHERE m.estado = 'en_curso' AND m.solicitante_id IN (" . implode(',', $pids) . ")
                    ORDER BY m.id DESC");
                while ($r = $db->fetch_array($q)) {
                    $ident = json_decode((string) ($r['identidad'] ?? '{}'), true);
                    $nombre = (string) ($ident['nombre'] ?? ('Misión #' . $r['id']));
                    $ult = function_exists('ope7_mision_ultimo_tramo') ? ope7_mision_ultimo_tramo((int) $r['id']) : array('tramo' => 0);
                    $out[(int) $r['id']] = array($nombre . ' · tramo ' . (int) ($ult['tramo'] ?? 0) . '/' . (int) $r['duracion_rondas'], (int) $r['solicitante_id']);
                }
            }
            break;
        case 'mis_tripulaciones':
            // Tripulaciones activas donde el PJ es capitán (63–67).
            if ($pids && ope7_tabla_existe('tripulaciones')) {
                $q = $db->query("SELECT t.*, b.nombre AS barco_nombre FROM " . ope7_tabla_full('tripulaciones') . " t
                    LEFT JOIN " . ope7_tabla_full('barcos') . " b ON b.id = t.barco_id
                    WHERE t.estado = 'activa' AND t.capitan_id IN (" . implode(',', $pids) . ")
                    ORDER BY t.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' · ' . (int) ope7_trip_espacio_usado((int) $r['id']) . '/' . (int) ope7_trip_espacio_max($r) . ' plazas', (int) $r['capitan_id']);
                }
            }
            break;
        case 'miembros_trip':
            // Miembros activos de las tripulaciones del capitán (65/66: expulsar/sucesor).
            if ($pids && ope7_tabla_existe('tripulantes') && ope7_tabla_existe('tripulaciones')) {
                $q = $db->query("SELECT m.personaje_id, p.nombre AS pj_nombre, m.rol, t.nombre AS trip_nombre, t.id AS trip_id
                    FROM " . ope7_tabla_full('tripulantes') . " m
                    JOIN " . ope7_tabla_full('tripulaciones') . " t ON t.id = m.tripulacion_id
                    JOIN " . ope7_tabla_full('personajes') . " p ON p.id = m.personaje_id
                    WHERE t.estado = 'activa' AND t.capitan_id IN (" . implode(',', $pids) . ")
                      AND m.estado = 'activo' AND m.personaje_id NOT IN (" . implode(',', $pids) . ")
                    ORDER BY t.nombre, p.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['personaje_id']] = array($r['pj_nombre'] . ' (' . ($r['rol'] === 'capitan' ? '👑 ' : '') . $r['trip_nombre'] . ')', 0);
                }
            }
            break;
        case 'pj_sin_tripulacion':
            // Personajes aprobados sin tripulación activa (63/64: fundadores e ingreso).
            if (ope7_tabla_existe('personajes') && ope7_tabla_existe('tripulantes')) {
                $q = $db->query("SELECT p.id, p.nombre, p.uid, u.username
                    FROM " . ope7_tabla_full('personajes') . " p
                    LEFT JOIN " . ope7_tabla_full('users') . " u ON u.uid = p.uid
                    WHERE p.estado = 'aprobado' AND p.id NOT IN (
                        SELECT m.personaje_id FROM " . ope7_tabla_full('tripulantes') . " m
                        JOIN " . ope7_tabla_full('tripulaciones') . " t ON t.id = m.tripulacion_id
                        WHERE m.estado = 'activo' AND t.estado = 'activa'
                    ) ORDER BY p.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . (string) ($r['username'] ?? ('#' . $r['uid'])) . ')', 0);
                }
            }
            break;
        case 'implantes':
            // Catálogo de implantes activos (56/59: instalar o mejorar).
            if (ope7_tabla_existe('implantes')) {
                $q = $db->simple_select('ope_implantes', '*', 'activo = 1', array('order_by' => 'zona, nivel, nombre'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . $r['zona'] . ' ' . $r['nivel'] . ')', 0);
                }
            }
            break;
        case 'mis_implantes':
            // Implantes activos del PJ (57/58: retirar o mantener).
            if ($pids && ope7_tabla_existe('modificaciones_personaje') && ope7_tabla_existe('implantes')) {
                $q = $db->query("SELECT m.id, m.personaje_id, i.nombre, i.zona, m.nivel, m.estado
                    FROM " . ope7_tabla_full('modificaciones_personaje') . " m
                    JOIN " . ope7_tabla_full('implantes') . " i ON i.id = m.implante_id
                    WHERE m.personaje_id IN (" . implode(',', $pids) . ") AND m.estado IN ('activo','averiado')
                    ORDER BY i.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . $r['zona'] . ' ' . $r['nivel'] . ($r['estado'] === 'averiado' ? ' ⚠ averiado' : '') . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'familias':
            // Familias legendarias con cupo libre (60: concesión).
            if (ope7_tabla_existe('familias_legendarias')) {
                $q = $db->query('SELECT f.*, (SELECT COUNT(*) FROM ' . ope7_tabla_full('linaje_personaje') . " l WHERE l.familia_id = f.id AND l.estado = 'activo') AS ocupados "
                    . 'FROM ' . ope7_tabla_full('familias_legendarias') . ' f ORDER BY f.nombre');
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . (int) $r['ocupados'] . '/' . (int) $r['cupo'] . ' cupo)', 0);
                }
            }
            break;
        case 'pj_sin_linaje':
            // Personajes aprobados sin linaje activo (60: concesión).
            if (ope7_tabla_existe('personajes') && ope7_tabla_existe('linaje_personaje')) {
                $q = $db->query("SELECT p.id, p.nombre, u.username
                    FROM " . ope7_tabla_full('personajes') . " p
                    LEFT JOIN " . ope7_tabla_full('users') . " u ON u.uid = p.uid
                    WHERE p.estado = 'aprobado' AND p.id NOT IN (
                        SELECT l.personaje_id FROM " . ope7_tabla_full('linaje_personaje') . " l
                        WHERE l.estado = 'activo'
                    ) ORDER BY p.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . (string) ($r['username'] ?? ('#' . $r['id'])) . ')', 0);
                }
            }
            break;
        case 'linajes_activos':
            // Linajes activos (61: revocación).
            if (ope7_tabla_existe('linaje_personaje') && ope7_tabla_existe('familias_legendarias')) {
                $q = $db->query('SELECT l.id, f.nombre AS fam_nombre, p.nombre AS pj_nombre
                    FROM ' . ope7_tabla_full('linaje_personaje') . ' l
                    JOIN ' . ope7_tabla_full('familias_legendarias') . ' f ON f.id = l.familia_id
                    JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = l.personaje_id
                    WHERE l.estado = 'activo' ORDER BY f.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['pj_nombre'] . ' — ' . $r['fam_nombre'], 0);
                }
            }
            break;
        case 'mis_espias':
            // Espías activos de las redes del PJ (25: solicitar rumor a la red).
            if ($pids && ope7_tabla_existe('red_espionaje') && ope7_tabla_existe('espias')) {
                $q = $db->query('SELECT e.id, e.tipo, e.mantenimiento, r.nombre AS red_nombre FROM ' . ope7_tabla_full('espias') . ' e '
                    . 'JOIN ' . ope7_tabla_full('red_espionaje') . ' r ON r.id = e.red_id '
                    . 'WHERE r.dueno_id IN (' . implode(',', $pids) . ") AND e.estado = 'activo' ORDER BY e.tipo");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['tipo'] . ' · ' . $r['red_nombre'] . ' · mant. ' . number_format((int) $r['mantenimiento'], 0, ',', '.') . ' ฿/ronda', 0);
                }
            }
            break;
        case 'rumores_comprables':
            // Rumores activos del puesto (26: compra directa).
            if (ope7_tabla_existe('rumores')) {
                $q = $db->simple_select('ope_rumores', '*', "estado IN ('activo','contrastado') AND fiabilidad IN ('rumoroso','plausible','solido')", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 40));
                while ($r = $db->fetch_array($q)) {
                    $mult = function_exists('ope7_rumor_multiplier') ? ope7_rumor_multiplier($r) : array('precio' => 0);
                    $out[(int) $r['id']] = array($r['contenido'] . ' (' . $r['fiabilidad'] . ' · ' . $r['alcance'] . ' · ' . $mult['precio'] . ' ฿)', 0);
                }
            }
            break;
        case 'mis_rumores':
            // Rumores en poder del PJ (27 contraste / 28 venta) — vía operaciones.
            if ($pids && ope7_tabla_existe('rumor_operaciones') && ope7_tabla_existe('rumores')) {
                $q = $db->query('SELECT o.rumor_id, MAX(o.id) AS max_id FROM ' . ope7_tabla_full('rumor_operaciones') . ' o '
                    . 'WHERE o.solicitante_id IN (' . implode(',', $pids) . ") AND o.tipo IN ('compra','venta','contraste') GROUP BY o.rumor_id ORDER BY max_id DESC");
                $ids_rumor = array();
                while ($r = $db->fetch_array($q)) {
                    $ids_rumor[] = (int) $r['rumor_id'];
                }
                if ($ids_rumor) {
                    $q = $db->simple_select('ope_rumores', '*', 'id IN (' . implode(',', $ids_rumor) . ') AND estado != \'retirado\'', array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 40));
                    while ($r = $db->fetch_array($q)) {
                        $out[(int) $r['id']] = array($r['contenido'] . ' (' . $r['fiabilidad'] . ' · ' . $r['alcance'] . ')', 0);
                    }
                }
            }
            break;
        case 'pj_otros':
            // Otros personajes aprobados (28: comprador de un rumor).
            if ($pids && ope7_tabla_existe('personajes')) {
                $q = $db->simple_select('ope_personajes', 'id, nombre', "estado = 'aprobado' AND id NOT IN (" . implode(',', $pids) . ")", array('order_by' => 'nombre', 'limit' => 60));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'], 0);
                }
            }
            break;
        case 'carteles_cobrables':
            // Carteles vigentes (31: cobrar recompensa).
            if (ope7_tabla_existe('carteles_recompensa') && ope7_tabla_existe('personajes')) {
                $q = $db->query('SELECT c.id, c.cifra, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('carteles_recompensa') . ' c '
                    . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = c.personaje_id WHERE c.estado = 'vigente' ORDER BY c.id DESC");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array('#' . (int) $r['id'] . ' · ' . $r['pj_nombre'] . ' · ' . number_format((int) $r['cifra'], 0, ',', '.') . ' ฿', 0);
                }
            }
            break;
        case 'redes_ajenas':
            // Redes de otros (33: ataque) — solo activas.
            if ($pids && ope7_tabla_existe('red_espionaje') && ope7_tabla_existe('personajes')) {
                $q = $db->query('SELECT r.id, r.nombre, r.dueno_id, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('red_espionaje') . ' r '
                    . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = r.dueno_id '
                    . "WHERE r.estado = 'activa' AND r.dueno_id NOT IN (" . implode(',', $pids) . ") ORDER BY r.id DESC");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' · de ' . $r['pj_nombre'], 0);
                }
            }
            break;
    }
    return $out;
}

/** PJs del usuario (para el selector de personaje). */
function ope7_tramite_pj_opciones($uid)
{
    global $db;
    $out = array();
    if (!ope7_tabla_existe('personajes')) {
        return $out;
    }
    $q = $db->simple_select('ope_personajes', 'id, nombre, nivel, estado', "uid = " . (int) $uid . " AND estado != 'rechazado'", array('order_by' => 'id'));
    while ($r = $db->fetch_array($q)) {
        $out[(int) $r['id']] = $r['nombre'] . ' (nv ' . (int) $r['nivel'] . ')';
    }
    return $out;
}

/** Precio por nivel del tipo de barco (para el label de la compra). */
function ope7_objeto_precio_barco_txt($tipo)
{
    $precios = $tipo['precios'] ? json_decode($tipo['precios'], true) : array();
    $p = (int) ($precios[0] ?? $tipo['precio'] ?? 0);
    return $p > 0 ? number_format($p) . ' ฿' : '—';
}

/**
 * Construye ids/datos/motivo desde el POST según el trámite.
 * Devuelve array('ok', 'msg'|'ids', 'datos', 'motivo', 'pid').
 */
function ope7_tramite_pagina_procesar($numero, $uid, array $info, array $campos)
{
    global $mybb, $db;

    $pid = (int) $mybb->get_input('personaje_id', 1);
    $motivo = trim((string) $mybb->get_input('motivo'));

    // Validar campos requeridos.
    foreach ($campos as $c) {
        if (empty($c['required'])) {
            continue;
        }
        $v = trim((string) $mybb->get_input($c['name']));
        if ($v === '') {
            return array('ok' => false, 'msg' => 'Falta un campo obligatorio: «' . $c['label'] . '».');
        }
    }
    if ($pid < 1) {
        return array('ok' => false, 'msg' => 'Elige un personaje (necesitas crear uno primero).');
    }

    $ids = array('personaje_id' => $pid);
    $datos = array();

    switch ((int) $numero) {
        case 1:
            $ids['tipo'] = (string) $mybb->get_input('tipo') === 'pasado' ? 'pasado' : 'presente';
            $ids['tema_id'] = 0;
            $ids['zona'] = trim((string) $mybb->get_input('tema_titulo'));
            $ids['fecha_foro'] = trim((string) $mybb->get_input('fecha_foro'));
            break;
        case 4:
            $dom_id = (int) $mybb->get_input('dominio_id', 1);
            if ($dom_id > 0) {
                $nivel_act = 0;
                $dq = $db->simple_select('ope_dominios_personaje', 'nivel', "personaje_id = {$pid} AND dominio_id = {$dom_id}", array('limit' => 1));
                if ($db->num_rows($dq)) {
                    $nivel_act = (int) $db->fetch_field($dq, 'nivel');
                }
                $ids = array('personaje_id' => $pid, 'dominio_id' => $dom_id, 'nivel' => $nivel_act + 1);
            } else {
                $ids = array('personaje_id' => $pid, 'atributo' => (string) $mybb->get_input('atributo'), 'bloque' => (int) $mybb->get_input('bloque', 1));
            }
            break;
        case 13:
            $ids['idea'] = trim((string) $mybb->get_input('idea'));
            $ids['tier'] = (string) $mybb->get_input('tier');
            $datos['idea'] = $ids['idea'];
            $datos['tier'] = $ids['tier'];
            break;
        case 14:
            $ids['objeto_id'] = (int) $mybb->get_input('objeto_id', 1);
            $ids['zona'] = (string) $mybb->get_input('zona');
            break;
        case 15:
            $ids['tienda_nombre'] = trim((string) $mybb->get_input('tienda_nombre'));
            break;
        case 16:
            $ids['tienda_id'] = (int) $mybb->get_input('tienda_id', 1);
            break;
        case 17:
            $ids['tienda_id'] = (int) $mybb->get_input('tienda_id', 1);
            $ids['objeto_id'] = (int) $mybb->get_input('objeto_id', 1);
            $ids['cantidad'] = max(1, (int) $mybb->get_input('cantidad', 1));
            // El efecto automático lee $res (= $datos): tienda + items a reponer.
            $datos = array('tienda_id' => $ids['tienda_id'], 'items' => array(array('objeto_id' => $ids['objeto_id'], 'stock' => $ids['cantidad'])));
            break;
        case 19:
            $ids['nombre_npc'] = trim((string) $mybb->get_input('nombre_npc'));
            break;
        case 25:
            $ids['espia_id'] = (int) $mybb->get_input('espia_id', 1);
            $ids['isla_id'] = (int) $mybb->get_input('isla_id', 1);
            break;
        case 26:
            $ids['rumor_id'] = (int) $mybb->get_input('rumor_id', 1);
            break;
        case 27:
            $ids['rumor_id'] = (int) $mybb->get_input('rumor_id', 1);
            $ids['sensibilidad'] = (string) $mybb->get_input('sensibilidad');
            break;
        case 28:
            $ids['rumor_id'] = (int) $mybb->get_input('rumor_id', 1);
            $ids['comprador_id'] = (int) $mybb->get_input('comprador_id', 1);
            $ids['precio'] = (int) $mybb->get_input('precio', 1);
            break;
        case 29:
            $ids['tipo'] = (string) $mybb->get_input('tipo');
            $ids['nombre'] = trim((string) $mybb->get_input('nombre'));
            break;
        case 30:
            // Solo-staff (panel Bajo Mundo): el staff fija cifra/paradero al firmar.
            break;
        case 31:
            $ids['cartel_id'] = (int) $mybb->get_input('cartel_id', 1);
            break;
        case 32:
            $ids['isla_id'] = (int) $mybb->get_input('isla_id', 1);
            break;
        case 33:
            $ids['red_id'] = (int) $mybb->get_input('red_id', 1);
            break;
        case 34:
        case 37:
            $isla_id = (int) $mybb->get_input('isla_id', 1);
            $isla = $isla_id > 0 && ope7_tabla_existe('islas') ? ope7_isla_por_id($isla_id) : null;
            $ids['isla_id'] = $isla_id;
            $ids['isla'] = $isla ? $isla['nombre'] : ('isla #' . $isla_id);
            break;
        case 38:
            $destino_id = (int) $mybb->get_input('destino_id', 1);
            $barco_id = (int) $mybb->get_input('barco_id', 1);
            $utensilio_id = (int) $mybb->get_input('utensilio_id', 1);
            $ids['destino_id'] = $destino_id;
            $ids['barco_id'] = $barco_id;
            $ids['utensilio_id'] = $utensilio_id;
            $pj = $pid > 0 ? ope7_pj_get($pid) : null;
            $origen = 'sin ubicación';
            if ($pj) {
                $oi = (int) ($pj['ubicacion_isla_id'] ?? 0);
                $origen = $oi > 0 && ope7_tabla_existe('islas') ? (ope7_isla_por_id($oi)['nombre'] ?? 'isla #' . $oi) : 'sin ubicación';
            }
            $dest = $destino_id > 0 && ope7_tabla_existe('islas') ? ope7_isla_por_id($destino_id) : null;
            $barco = $barco_id > 0 && ope7_tabla_existe('barcos') ? ope7_barco_por_id($barco_id) : null;
            $ut = $utensilio_id > 0 && ope7_tabla_existe('objetos') ? ope7_objeto_nombre($utensilio_id) : '';
            $ids['origen'] = $origen;
            $ids['destino'] = $dest ? $dest['nombre'] : ('isla #' . $destino_id);
            $ids['barco'] = $barco ? $barco['nombre'] : ('barco #' . $barco_id);
            $ids['utensilio'] = $ut;
            $ids['acompanantes'] = $motivo;
            break;
        case 39:
        case 40:
            $ids['tipo_id'] = (int) $mybb->get_input('tipo_id', 1);
            $ids['madera_id'] = (int) $mybb->get_input('madera_id', 1);
            $tipo = null;
            $mad = '';
            if (ope7_tabla_existe('tipos_barcos')) {
                $tq = $db->simple_select('ope_tipos_barcos', '*', 'id = ' . $ids['tipo_id'], array('limit' => 1));
                $tipo = $db->fetch_array($tq);
            }
            if (ope7_tabla_existe('maderas_casco')) {
                $mq = $db->simple_select('ope_maderas_casco', 'nombre', 'id = ' . $ids['madera_id'], array('limit' => 1));
                $mad = $db->fetch_field($mq, 'nombre') ?: '';
            }
            $ids['tipo'] = $tipo ? $tipo['nombre'] : '';
            $ids['madera'] = $mad;
            break;
        case 41:
        case 42:
        case 43:
        case 44:
            $ids['barco_id'] = (int) $mybb->get_input('barco_id', 1);
            $ids['nivel'] = (string) $mybb->get_input('nivel');
            $ids['accion'] = (string) $mybb->get_input('accion');
            $ids['grado'] = (string) $mybb->get_input('grado');
            $ids['modulo_id'] = (int) $mybb->get_input('modulo_id', 1);
            $barco = $ids['barco_id'] > 0 && ope7_tabla_existe('barcos') ? ope7_barco_por_id($ids['barco_id']) : null;
            $ids['barco'] = $barco ? $barco['nombre'] : '';
            if ($numero === 42 && ope7_tabla_existe('modulos_barcos')) {
                $mq = $db->simple_select('ope_modulos_barcos', 'nombre', 'id = ' . $ids['modulo_id'], array('limit' => 1));
                $ids['modulo'] = $db->fetch_field($mq, 'nombre') ?: '';
            }
            break;
        case 45:
            // Automática: solo personaje (la pool la resuelve el efecto).
            break;
        case 46:
            $ids['especificidad'] = (string) $mybb->get_input('especificidad');
            $ids['tier'] = (int) $mybb->get_input('tier', 1);
            $ids['familia'] = (string) $mybb->get_input('familia');
            break;
        case 47:
            $ids['akuma_id'] = (int) $mybb->get_input('akuma_id', 1);
            break;
        case 48:
            // El despertar usa la fruta comida de la ficha (personajes.akuma_id).
            break;
        case 49:
            // Solo-staff (panel Akumas): el staff edita la ficha en la bandeja.
            break;
        case 50:
            // Automática: solo personaje (ventana + probabilidad las resuelve el efecto).
            break;
        case 51:
            $ids['tipo'] = (string) $mybb->get_input('tipo');
            break;
        case 52:
            $ids['mision_id'] = (int) $mybb->get_input('mision_id', 1);
            $m = $ids['mision_id'] > 0 && function_exists('ope7_mision_get') ? ope7_mision_get($ids['mision_id']) : null;
            $ids['mision'] = $m ? (string) ($m['identidad']['nombre'] ?? ('misión #' . $ids['mision_id'])) : ('misión #' . $ids['mision_id']);
            break;
        case 53:
            $ids['mision_id'] = (int) $mybb->get_input('mision_id', 1);
            $m = $ids['mision_id'] > 0 && function_exists('ope7_mision_get') ? ope7_mision_get($ids['mision_id']) : null;
            $ids['mision'] = $m ? (string) ($m['identidad']['nombre'] ?? ('misión #' . $ids['mision_id'])) : ('misión #' . $ids['mision_id']);
            $ids['posts'] = trim((string) $mybb->get_input('posts'));
            $datos['posts'] = $ids['posts'];
            break;
        case 56:
            $ids['implante_id'] = (int) $mybb->get_input('implante_id', 1);
            $ids['autocirugia'] = (int) $mybb->get_input('autocirugia', 1) === 1 ? 1 : 0;
            break;
        case 57:
            $ids['modificacion_id'] = (int) $mybb->get_input('modificacion_id', 1);
            break;
        case 58:
            $ids['modificacion_id'] = (int) $mybb->get_input('modificacion_id', 1);
            break;
        case 59:
            $ids['implante_id'] = (int) $mybb->get_input('implante_id', 1);
            $ids['concepto'] = trim((string) $mybb->get_input('concepto'));
            break;
        case 60:
            $ids['familia_id'] = (int) $mybb->get_input('familia_id', 1);
            $ids['personaje_id'] = (int) $mybb->get_input('personaje_id', 1);
            break;
        case 61:
            $ids['linaje_id'] = (int) $mybb->get_input('linaje_id', 1);
            break;
        case 62:
            $ids['causa'] = trim((string) $mybb->get_input('causa'));
            $ids['tema_id'] = 0;
            break;
        case 63: // Fundación: nombre/bandera/propósito + barco + fundadores
            $ids['nombre'] = trim((string) $mybb->get_input('nombre'));
            $ids['bandera'] = trim((string) $mybb->get_input('bandera'));
            $ids['proposito'] = trim((string) $mybb->get_input('proposito'));
            $ids['barco_id'] = (int) $mybb->get_input('barco_id', 1);
            $fund = array((int) $mybb->get_input('fundador_1', 1), (int) $mybb->get_input('fundador_2', 1));
            $ids['fundadores'] = array_values(array_unique(array_filter($fund)));
            $datos['nombre'] = $ids['nombre'];
            $datos['bandera'] = $ids['bandera'];
            $datos['proposito'] = $ids['proposito'];
            break;
        case 64:
            $ids['tripulacion_id'] = (int) $mybb->get_input('tripulacion_id', 1);
            $ids['ingresado_id'] = (int) $mybb->get_input('ingresado_id', 1);
            break;
        case 65:
            $ids['tripulacion_id'] = (int) $mybb->get_input('tripulacion_id', 1);
            $ids['expulsado_id'] = (int) $mybb->get_input('expulsado_id', 1);
            break;
        case 66:
            $ids['tripulacion_id'] = (int) $mybb->get_input('tripulacion_id', 1);
            $ids['sucesor_id'] = (int) $mybb->get_input('sucesor_id', 1);
            $ids['tipo'] = (string) $mybb->get_input('tipo');
            $ids['nuevo_nombre'] = trim((string) $mybb->get_input('nuevo_nombre'));
            break;
        case 67:
            $ids['tripulacion_id'] = (int) $mybb->get_input('tripulacion_id', 1);
            break;
        default:
            break;
    }

    return array('ok' => true, 'ids' => $ids, 'datos' => $datos, 'motivo' => $motivo, 'pid' => $pid);
}

/** Campo → HTML (opciones dinámicas con data-pj para filtrar por personaje). */
function ope7_tramite_pagina_field_html(array $c, $uid, array $dyn)
{
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $name = $c['name'];
    $req = !empty($c['required']) ? ' <span class="tp-req">*</span>' : '';
    $hint = !empty($c['hint']) ? '<span class="fl-hint">' . $c['hint'] . '</span>' : '';
    $req_attr = !empty($c['required']) ? ' required' : '';

    if ($c['tipo'] === 'personaje') {
        $opts = ope7_tramite_pj_opciones($uid);
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '" class="tp-pj" data-pjsel="1"' . $req_attr . '>';
        if (!$opts) {
            $html .= '<option value="">— no tienes personajes —</option>';
        } else {
            $html .= '<option value="">— elige —</option>';
            foreach ($opts as $pid => $lab) {
                $html .= '<option value="' . (int) $pid . '" data-pj="' . (int) $pid . '">' . $e($lab) . '</option>';
            }
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'select' && !empty($c['opciones'])) {
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '"' . $req_attr . '>';
        $html .= '<option value="">— elige —</option>';
        foreach ($c['opciones'] as $v => $lab) {
            $html .= '<option value="' . $e($v) . '">' . $e($lab) . '</option>';
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'select' && !empty($c['fuente'])) {
        $opts = $dyn[$c['fuente']] ?? array();
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '" class="tp-dyn" data-fuente="' . $e($c['fuente']) . '"' . $req_attr . '>';
        $html .= '<option value="">— elige —</option>';
        foreach ($opts as $v => $info) {
            $html .= '<option value="' . (int) $v . '" data-pj="' . (int) $info[1] . '">' . $e($info[0]) . '</option>';
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'dyn') {
        $opts = $dyn[$c['fuente']] ?? array();
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '" class="tp-dyn" data-fuente="' . $e($c['fuente']) . '"' . $req_attr . '>';
        $html .= '<option value="">— elige —</option>';
        foreach ($opts as $v => $info) {
            $html .= '<option value="' . (int) $v . '" data-pj="' . (int) $info[1] . '">' . $e($info[0]) . '</option>';
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'area') {
        $max = (int) ($c['maxlength'] ?? 2000);
        return '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
             . '<textarea id="f-' . $e($name) . '" name="' . $e($name) . '" maxlength="' . $max . '" class="tp-area"' . $req_attr . '></textarea>' . $hint . '</div>';
    }

    if ($c['tipo'] === 'texto') {
        $max = (int) ($c['maxlength'] ?? 120);
        return '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
             . '<input type="text" id="f-' . $e($name) . '" name="' . $e($name) . '" maxlength="' . $max . '"' . $req_attr . '>' . $hint . '</div>';
    }

    if ($c['tipo'] === 'number') {
        $min = (int) ($c['min'] ?? 1);
        $max = (int) ($c['max'] ?? 99);
        $val = (int) ($c['value'] ?? 1);
        return '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
             . '<input type="number" id="f-' . $e($name) . '" name="' . $e($name) . '" min="' . $min . '" max="' . $max . '" value="' . $val . '" class="tp-num"' . $req_attr . '>' . $hint . '</div>';
    }

    return '';
}

/**
 * Página completa de un trámite (POST + render). La usan los 56 tramite-NN.php.
 */
function ope7_tramite_pagina($numero)
{
    global $mybb, $db;

    $numero = (int) $numero;
    $info = ope7_tramite_info($numero);
    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    $bbname = htmlspecialchars_uni($mybb->settings['bbname']);
    $uid = (int) ($mybb->user['uid'] ?? 0);
    $es_staff = function_exists('ope7_es_staff') && ope7_es_staff($uid);

    if ($uid < 1) {
        header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
        exit;
    }
    if (!$info) {
        echo 'Trámite no encontrado.';
        exit;
    }
    // Solo-staff: sin página de jugador (el hub no enlaza; acceso directo → bandeja).
    if ($info['quien'] === 'staff' && !$es_staff) {
        header('Location: ' . $mybb->settings['bburl'] . '/tramites.php');
        exit;
    }

    $campos = ope7_tramite_pagina_campos($numero);
    $flash = '';
    $ok_msg = '';

    if ($mybb->request_method === 'post') {
        $r = ope7_tramite_pagina_procesar($numero, $uid, $info, $campos);
        if (!$r['ok']) {
            $flash = '<div class="flash warn">' . htmlspecialchars_uni($r['msg']) . '</div>';
        } else {
            $creado = ope7_tramite_crear($uid, $r['pid'], $numero, $r['motivo'], $r['ids'], $r['datos']);
            if (!$creado['ok']) {
                $flash = '<div class="flash warn">' . htmlspecialchars_uni($creado['msg']) . '</div>';
            } else {
                $ok_msg = ope7_tramite_es_auto($numero)
                    ? 'Trámite ejecutado: ' . htmlspecialchars_uni((string) ($creado['msg'] ?? 'efectos aplicados.'))
                    : 'Solicitud creada (#' . (int) ($creado['tid'] ?? 0) . '): la revisa el staff. Sigue su estado en tus trámites.';
                $flash = '<div class="flash ok">' . $ok_msg . '</div>';
            }
        }
    }

    // Opciones dinámicas (todos los PJ del usuario, filtradas por data-pj).
    $dyn = array('islas' => array(), 'barcos' => array(), 'tiendas' => array(), 'dominios' => array(),
                 'objetos_almacen' => array(), 'objetos_mochila' => array(), 'utensilios' => array());
    foreach ($campos as $c) {
        if ($c['tipo'] === 'dyn' || ($c['tipo'] === 'select' && !empty($c['fuente']))) {
            $dyn[$c['fuente']] = ope7_tramite_pagina_dyn($c['fuente'], $uid);
        }
    }
    $dyn['tipos_barco'] = ope7_tramite_pagina_dyn('tipos_barco', $uid);
    $dyn['maderas'] = ope7_tramite_pagina_dyn('maderas', $uid);
    $dyn['modulos'] = ope7_tramite_pagina_dyn('modulos', $uid);

    $nat_label = ope7_naturaleza_label($info['naturaleza']);
    $quien = ope7_quien_label($info['quien']);
    $nota = ope7_tramite_pagina_nota($numero, $info);
    $es_auto = ope7_tramite_es_auto($numero);

    $html = '';
    foreach ($campos as $c) {
        $html .= ope7_tramite_pagina_field_html($c, $uid, $dyn);
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Trámite <?php echo (int) $numero; ?> — <?php echo htmlspecialchars_uni($info['nombre']); ?></title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tramite">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a><span class="sep">›</span><b>#<?php echo (int) $numero; ?> <?php echo htmlspecialchars_uni($info['nombre']); ?></b>
</div></div>
<div class="wrap">
  <div class="shead"><h1><?php echo htmlspecialchars_uni($info['nombre']); ?></h1><span class="code">VENTANILLA #<?php echo (int) $numero; ?></span><span class="rule"></span></div>
  <?php echo $flash; ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Pedir este trámite</span><span class="c"><?php echo htmlspecialchars_uni($nat_label); ?> · <?php echo htmlspecialchars_uni($quien); ?></span></div>
    <div class="plate-b">
      <p class="tp-nota"><?php echo $nota; ?></p>
      <?php if ($info['efecto'] !== ''): ?>
      <p class="tp-efecto">Efecto: <?php echo htmlspecialchars_uni($info['efecto']); ?></p>
      <?php endif; ?>
      <form method="post" action="<?php echo $bburl; ?>/tramite-<?php echo str_pad((string) $numero, 2, '0', STR_PAD_LEFT); ?>.php" class="tp-form">
        <?php echo $html; ?>
        <div class="tp-actions">
          <button type="submit" class="btn btn-hot"><?php echo $es_auto ? 'Ejecutar ahora' : 'Enviar solicitud'; ?></button>
          <a class="btn btn-ghost" href="<?php echo $bburl; ?>/tramites.php">Volver a mis trámites</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include MYBB_ROOT . 'footer_custom.php'; ?>
<script>
(function () {
  // Los selectores dinámicos (barco, tienda, dominio, objeto…) se filtran por
  // el personaje elegido: cada opción lleva data-pj con su dueño.
  var pjSel = document.querySelector('.tp-pj');
  var dyns = Array.prototype.slice.call(document.querySelectorAll('.tp-dyn'));
  if (pjSel) {
    var aplicar = function () {
      var pid = pjSel.value;
      dyns.forEach(function (sel) {
        Array.prototype.forEach.call(sel.options, function (o) {
          if (!o.getAttribute('data-pj')) { return; } // placeholder
          var ok = o.getAttribute('data-pj') === pid || o.getAttribute('data-pj') === '0';
          o.disabled = !ok;
          if (!ok && o.selected) { sel.value = ''; }
        });
      });
    };
    pjSel.addEventListener('change', aplicar);
    aplicar();
  }
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    const io = new IntersectionObserver(es => es.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
    }), { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
  } else {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
  }
})();
</script>
</body>
</html>
<?php
    exit;
}
