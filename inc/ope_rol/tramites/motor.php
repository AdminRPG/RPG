<?php
/**
 * One Piece: 7 Seas · Motor de trámites (5.21 — núcleo transversal)
 * -----------------------------------------------------------------------------
 * Ciclo de vida: borrador → pendiente → prompt_listo → analizado → en_revision
 * → publicado | rechazado | archivado. Toda transición queda en
 * mybb_ope_tramites_historico (auditable, con motivo y actor).
 *
 * Regla de oro (22.5): la automatización nunca decide sola — la IA propone, el
 * staff firma. Únicas excepciones 100 % automáticas (ligeros): 4 · 45 · 50.
 *
 * Tablas: mybb_ope_tramites · mybb_ope_tramites_historico (Anexo A.1).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Nombre BARE de una tabla canónica 7 Seas ('ope_' . $n).
 * Los helpers de MyBB ($db->simple_select/insert_query/update_query/table_exists)
 * anteponen automáticamente el prefijo mybb_, así que aquí NO se incluye.
 */
function ope7_tabla($nombre)
{
    static $prefix = null;
    if ($prefix === null) {
        $prefix = 'ope_';
        if (defined('OPE7_TABLA_PREFIX')) {
            $p = rtrim((string) OPE7_TABLA_PREFIX, '_');
            $p = preg_replace('/^mybb_/i', '', $p);
            if ($p !== '') {
                $prefix = $p . '_';
            }
        }
    }
    return $prefix . $nombre;
}

/** Nombre COMPLETO con prefijo mybb_ (para $db->query() con SQL crudo). */
function ope7_tabla_full($nombre)
{
    global $db;
    return (string) $db->table_prefix . ope7_tabla($nombre);
}

/** Estados válidos del ciclo de vida (22.2 + F1.3: ciclo con usuario). */
function ope7_tramite_estados()
{
    return array('borrador', 'pendiente', 'prompt_listo', 'analizado', 'en_revision', 'revision_usuario', 'aceptado_usuario', 'publicado', 'rechazado', 'archivado');
}

/**
 * ¿El trámite tiene ciclo con usuario (22.4)? Confirmado solo en 3 (ficha)
 * y 13 (técnica): el resultado vuelve al jugador, que acepta o pide cambios.
 */
function ope7_tramite_es_ciclo($numero)
{
    return in_array((int) $numero, array(3, 13), true);
}

/**
 * Crea un trámite (jugador o staff). Naturaleza ligero → se resuelve al instante
 * (publicado) con registro; ia/staff/hito → pendiente + prompt generado.
 *
 * @param int    $uid      uid MyBB del solicitante (staff para los staff-only).
 * @param int    $pid      personaje implicado (0 si no aplica).
 * @param int    $numero   nº del catálogo (1-67).
 * @param string $motivo   obligatorio en trámites narrativos (22.2).
 * @param array  $ids      contexto (ids_json): tema/personaje/isla/rumor/…
 * @param array  $datos    campos específicos por tipo (para el prompt).
 * @return array{ok:bool,msg:string,tid?:int}
 */
function ope7_tramite_crear($uid, $pid, $numero, $motivo = '', array $ids = array(), array $datos = array())
{
    global $db;

    $uid = (int) $uid;
    $pid = (int) $pid;
    $numero = (int) $numero;
    $info = ope7_tramite_info($numero);
    if (!$info) {
        return array('ok' => false, 'msg' => "Trámite nº {$numero} no existe en el catálogo (67 cerrado).");
    }
    if ($uid < 1) {
        return array('ok' => false, 'msg' => 'Necesitas sesión.');
    }
    if (!ope7_tabla_existe('tramites')) {
        return array('ok' => false, 'msg' => 'El motor de trámites no está migrado (mybb_ope_tramites).');
    }

    // Staff-only: solo el staff inicia. Se mira el campo `quien` (el catálogo
    // separa quien inicia de la naturaleza: p.ej. el 34 lo inicia el jugador
    // y el 62 cualquiera de los dos — solo bloquea quien === 'staff').
    if ($info['quien'] === 'staff' && !ope7_es_staff($uid)) {
        return array('ok' => false, 'msg' => 'Este trámite solo lo inicia el staff.');
    }
    // Narrativos (hito y los que lo requieren): motivo obligatorio.
    if ($info['naturaleza'] === 'hito' && trim((string) $motivo) === '') {
        return array('ok' => false, 'msg' => 'Los trámites de hito requieren un motivo narrativo.');
    }

    // Anti-duplicado: un mismo solicitante no repite el mismo nº en cola
    // (incluye el ciclo con usuario: en revisión del jugador también bloquea).
    $q = $db->simple_select(ope7_tabla('tramites'), 'id',
        "solicitante_id = {$uid} AND numero = {$numero} AND estado IN ('pendiente','prompt_listo','analizado','en_revision','revision_usuario','aceptado_usuario')",
        array('limit' => 1));
    if ($db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Ya tienes este trámite en cola.');
    }

    $now = TIME_NOW;
    $estado_inicial = 'pendiente';
    $estado_final = null;
    $nota = '';

    // Ligeros 100 % automáticos: se resuelven al instante Y aplican sus efectos
    // al crear (regla de oro: validación + hooks; F1.3: efectos 1, 4, 45, 50).
    if ($info['naturaleza'] === 'ligero' && !$info['firma']) {
        $estado_inicial = 'publicado';
        $estado_final = 'publicado';
        $nota = 'Trámite ligero: 100 % automático (validación + hooks).';
    }

    $tid = $db->insert_query(ope7_tabla('tramites'), array(
        'numero'         => $numero,
        'tipo'           => $db->escape_string($info['nombre']),
        'estado'         => $estado_inicial,
        'solicitante_id' => $uid,
        'personaje_id'   => $pid > 0 ? $pid : 0,
        'motivo'         => $db->escape_string((string) $motivo),
        'ids_json'       => $db->escape_string(json_encode($ids, JSON_UNESCAPED_UNICODE)),
        'skill'          => $db->escape_string($info['skill']),
        // Bug F0 corregido (F3.6): el 6º argumento ($datos/resultado inicial) se
        // guardaba y se perdía — ahora queda disponible para ligeros y prompts.
        'resultado_json' => $datos ? $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)) : null,
        'fecha_creacion' => $now,
    ));

    // Ligeros automáticos: aplicar efectos ya (firma no existe en su flujo).
    if ($estado_final === 'publicado') {
        $tr = ope7_tramite_get($tid);
        $aplicado = ope7_tramite_aplicar_efectos($tr);
        $msg_efecto = isset($aplicado['msg']) && $aplicado['msg'] !== '' ? (string) $aplicado['msg'] : '';
        // Si la validación del efecto bloquea (cronómetro/techo/PP…), el ligero
        // NO se publica: queda rechazado con el motivo y el solicitante lo ve.
        if (stripos($msg_efecto, 'BLOQUEAD') !== false || stripos($msg_efecto, 'datos incompletos') !== false) {
            $db->update_query(ope7_tabla('tramites'), array('estado' => 'rechazado'), "id = {$tid}");
            ope7_tramite_log($tid, 'rechazado', $uid, $msg_efecto !== '' ? $msg_efecto : 'Validación ligera fallida.');
            return array('ok' => false, 'msg' => $msg_efecto !== '' ? $msg_efecto : 'Validación ligera fallida.', 'tid' => (int) $tid);
        }
        // Efecto aún no implementado (F5-F6): no se publica — la solicitud va a
        // la bandeja para revisión del staff (regla de oro: nada se decide solo).
        // Marcador explícito [PENDIENTE] (nunca subcadenas: 'independiente' contiene
        // 'pendiente' y rompía las compras de dominio ×1,0/×1,5).
        if (strpos($msg_efecto, '[PENDIENTE]') !== false) {
            $estado_final = null;
            $nota = 'Trámite ligero sin efecto automático todavía: queda para revisión del staff.';
        } else {
            $nota = $msg_efecto !== '' ? $msg_efecto : $nota;
            $db->update_query(ope7_tabla('tramites'), array('fecha_firma' => TIME_NOW), "id = {$tid}");
        }
    }

    // Prompt: se genera al crear (22.6). Para ligeros no hace falta.
    if ($estado_final === null) {
        $prompt = ope7_tramite_generar_prompt($tid, $numero, $uid, $pid, $ids, $datos);
        $db->update_query(ope7_tabla('tramites'), array(
            'estado' => 'prompt_listo',
            'prompt' => $db->escape_string($prompt),
        ), "id = {$tid}");
        $estado_final = 'prompt_listo';
    }

    ope7_tramite_log($tid, $estado_final, $uid, $nota !== '' ? $nota : 'Trámite creado.');

    return array('ok' => true, 'msg' => 'Trámite enviado.', 'tid' => (int) $tid);
}

/** ¿Existe la tabla canónica? */
function ope7_tabla_existe($nombre)
{
    global $db;
    return $db->table_exists(ope7_tabla($nombre));
}

/**
 * Genera el prompt de un trámite: plantilla específica si existe, si no genérica.
 * El contexto se arma con los ids_json + campos específicos (22.6).
 */
function ope7_tramite_generar_prompt($tid, $numero, $uid, $pid, array $ids, array $datos)
{
    $info = ope7_tramite_info($numero);
    $ctx = array_merge($ids, $datos);
    $ctx['personaje_id'] = (int) ($ctx['personaje_id'] ?? $pid);

    $especifica = ope7_prompt_especifica($numero, $ctx);
    if ($especifica !== '') {
        return $especifica;
    }

    $contexto = '';
    foreach ($ctx as $k => $v) {
        if (is_array($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        $contexto .= "- {$k}: {$v}\n";
    }
    if ($contexto === '') {
        $contexto = "- solicitante uid: {$uid}\n- personaje pid: " . (int) $pid . "\n";
    }

    return ope7_prompt_generica($numero, $info['nombre'], $info['skill'], $contexto);
}

/** Guarda el resultado de la IA (editable en la bandeja) y pasa a en_revision. */
function ope7_tramite_guardar_resultado($tid, array $resultado, $estado = 'en_revision')
{
    global $db;
    $tid = (int) $tid;
    $tr = ope7_tramite_get($tid);
    if (!$tr) {
        return array('ok' => false, 'msg' => 'Trámite no encontrado.');
    }
    if (!in_array($tr['estado'], array('prompt_listo', 'analizado', 'en_revision'), true)) {
        return array('ok' => false, 'msg' => 'Este trámite no acepta un resultado ahora (estado: ' . $tr['estado'] . ').');
    }
    if (!in_array($estado, array('analizado', 'en_revision'), true)) {
        $estado = 'en_revision';
    }
    // Ciclo con usuario (F1.3): el resultado vuelve al SOLICITANTE, no directo a firma.
    if (ope7_tramite_es_ciclo($tr['numero']) && $estado === 'en_revision') {
        $estado = 'revision_usuario';
    }
    $db->update_query(ope7_tabla('tramites'), array(
        'resultado_json' => $db->escape_string(json_encode($resultado, JSON_UNESCAPED_UNICODE)),
        'estado'         => $estado,
    ), "id = {$tid}");
    ope7_tramite_log($tid, $estado, 0, 'Resultado de la IA guardado (editable).');
    return array('ok' => true, 'msg' => 'Resultado guardado.', 'tid' => $tid);
}

/** Ciclo con usuario (F1.3): el solicitante acepta el resultado → aceptado_usuario. */
function ope7_tramite_usuario_aceptar($tid, $uid, $motivo = '')
{
    global $db;
    $tid = (int) $tid;
    $uid = (int) $uid;
    $tr = ope7_tramite_get($tid);
    if (!$tr) {
        return array('ok' => false, 'msg' => 'Trámite no encontrado.');
    }
    if ((int) $tr['solicitante_id'] !== $uid && !ope7_es_staff($uid)) {
        return array('ok' => false, 'msg' => 'Solo el solicitante (o el staff) puede aceptar este trámite.');
    }
    if ($tr['estado'] !== 'revision_usuario') {
        return array('ok' => false, 'msg' => 'Este trámite no está esperando tu aceptación (estado: ' . $tr['estado'] . ').');
    }
    $db->update_query(ope7_tabla('tramites'), array('estado' => 'aceptado_usuario'), "id = {$tid}");
    ope7_tramite_log($tid, 'aceptado_usuario', $uid, $motivo !== '' ? $motivo : 'Resultado aceptado por el solicitante.');
    return array('ok' => true, 'msg' => 'Resultado aceptado. El staff puede firmarlo.', 'tid' => $tid);
}

/** Ciclo con usuario (F1.3): el solicitante pide cambios → vuelve a en_revision. */
function ope7_tramite_usuario_pedir_cambios($tid, $uid, $motivo)
{
    global $db;
    $tid = (int) $tid;
    $uid = (int) $uid;
    $motivo = trim((string) $motivo);
    $tr = ope7_tramite_get($tid);
    if (!$tr) {
        return array('ok' => false, 'msg' => 'Trámite no encontrado.');
    }
    if ((int) $tr['solicitante_id'] !== $uid) {
        return array('ok' => false, 'msg' => 'Solo el solicitante puede pedir cambios.');
    }
    if ($tr['estado'] !== 'revision_usuario') {
        return array('ok' => false, 'msg' => 'Este trámite no está esperando tu respuesta (estado: ' . $tr['estado'] . ').');
    }
    if ($motivo === '') {
        return array('ok' => false, 'msg' => 'Explica qué quieres cambiar (el motivo queda en el histórico).');
    }
    $db->update_query(ope7_tabla('tramites'), array('estado' => 'en_revision'), "id = {$tid}");
    ope7_tramite_log($tid, 'en_revision', $uid, 'El solicitante pide cambios: ' . $motivo);
    return array('ok' => true, 'msg' => 'Petición de cambios registrada. El staff la revisa.', 'tid' => $tid);
}

/** Firma el trámite: publicado | rechazado | archivado (con motivo obligatorio). */
function ope7_tramite_firmar($tid, $staff_uid, $accion, $motivo = '')
{
    global $db;
    $tid = (int) $tid;
    $staff_uid = (int) $staff_uid;
    $accion = (string) $accion;
    if (!in_array($accion, array('publicar', 'rechazar', 'archivar'), true)) {
        return array('ok' => false, 'msg' => 'Acción de firma no válida.');
    }
    if (!ope7_es_staff($staff_uid)) {
        return array('ok' => false, 'msg' => 'Solo el staff firma trámites.');
    }
    $tr = ope7_tramite_get($tid);
    if (!$tr) {
        return array('ok' => false, 'msg' => 'Trámite no encontrado.');
    }
    if (in_array($tr['estado'], array('publicado', 'rechazado', 'archivado'), true)) {
        return array('ok' => false, 'msg' => 'Este trámite ya está cerrado.');
    }
    if (trim((string) $motivo) === '') {
        return array('ok' => false, 'msg' => 'La firma requiere un motivo escrito (queda en el histórico).');
    }
    // Ciclo con usuario (F1.3): publicar exige la aceptación previa del solicitante.
    if ($accion === 'publicar' && ope7_tramite_es_ciclo($tr['numero']) && $tr['estado'] !== 'aceptado_usuario') {
        return array('ok' => false, 'msg' => 'Este trámite tiene ciclo con usuario: el solicitante debe aceptar el resultado antes de publicar (estado: ' . $tr['estado'] . ').');
    }

    $estado_final = $accion === 'publicar' ? 'publicado' : ($accion === 'rechazar' ? 'rechazado' : 'archivado');
    $nota = '';

    if ($estado_final === 'publicado') {
        // Aplicar efectos al publicar (por fase de implementación; transversal en F0).
        $aplicado = ope7_tramite_aplicar_efectos($tr);
        $nota = isset($aplicado['msg']) ? (string) $aplicado['msg'] : '';
        // Regla de oro (D1.6, extendida a la firma F3): si el efecto bloquea
        // (BLOQUEADO / datos incompletos), el trámite NO se publica en falso —
        // queda rechazado con el motivo y el staff lo ve en la bandeja.
        if (stripos($nota, 'BLOQUEAD') !== false || stripos($nota, 'datos incompletos') !== false) {
            $db->update_query(ope7_tabla('tramites'), array(
                'estado'      => 'rechazado',
                'firma_staff' => $staff_uid,
                'fecha_firma' => TIME_NOW,
            ), "id = {$tid}");
            ope7_tramite_log($tid, 'rechazado', $staff_uid, $motivo . ' · BLOQUEADO por validación: ' . $nota);
            return array('ok' => false, 'msg' => 'Trámite rechazado: ' . $nota, 'tid' => $tid);
        }
    }

    $db->update_query(ope7_tabla('tramites'), array(
        'estado'         => $estado_final,
        'firma_staff'    => $staff_uid,
        'fecha_firma'    => TIME_NOW,
    ), "id = {$tid}");

    ope7_tramite_log($tid, $estado_final, $staff_uid, $motivo . ($nota !== '' ? ' · ' . $nota : ''));
    return array('ok' => true, 'msg' => 'Trámite ' . $estado_final . ($nota !== '' ? ': ' . $nota : '') . '.', 'tid' => $tid);
}

/**
 * Efectos «al publicar» (F1.3: trámites 1–12 + los 3 automáticos confirmados).
 * Cada trámite aplica aquí sus efectos mecánicos del catálogo (cap. 22.3); los
 * sistemas completos (calendario 5.6, tiendas 5.9…) se conectan en F3–F6.
 * Devuelve array('ok', 'msg') con el resumen para el histórico de la firma.
 */
function ope7_tramite_aplicar_efectos($tr)
{
    global $db;
    $msg = '';
    $numero = (int) $tr['numero'];
    $pid = (int) ($tr['personaje_id'] ?? 0);
    $res = $tr['resultado'] ?: array();
    $ids = $tr['ids'] ?: array();

    switch ($numero) {
        case 1: // Apertura de tema (presente/pasado) — ancla, instantánea, bloqueo un-presente (5.6)
            $msg = ope7_efecto_apertura_tema($tr, $pid, $res, $ids);
            break;
        case 2: // Cierre de temas — PP, karma, fama (skill-cierre-temas)
            $msg = ope7_efecto_cierre_tema($tr, $pid, $res);
            break;
        case 3: // Validación de ficha — estado → aprobado
            if ($pid > 0 && ope7_tabla_existe('personajes')) {
                $db->update_query('ope_personajes', array('estado' => 'aprobado'), "id = {$pid}");
                $msg = "Ficha {$pid} aprobada.";
            } else {
                $msg = 'Aprobación de ficha: sin personaje asociado (pendiente).';
            }
            break;
        case 4: // Compra de PP (ligero automático) — descuento, reserva, techo, cronómetro
            $msg = ope7_efecto_compra_pp($pid, $ids, $tr);
            break;
        case 5: // Maestría Suprema (hito nv5 de rama)
            $msg = ope7_efecto_maestria($pid, $res);
            break;
        case 6: // Producción de oficio — ítem a inventario/almacén (base 5.8)
            $msg = ope7_efecto_produccion($pid, $res);
            break;
        case 7: // Dote/defecto por hito narrativo — 0 PP, origen hito
            $msg = ope7_efecto_hito_dote($pid, $res);
            break;
        case 8: // Genética Alterada (híbrido) — UNA dote de la 2ª raza
            $msg = ope7_efecto_genetica_alterada($pid, $res);
            break;
        case 9: // Evolución por hito (arraigo positivo → dote)
            $msg = ope7_efecto_evolucion_dote($pid, $res);
            break;
        case 10: // Superación de rasgo negativo
        case 11: // Pérdida/cambio por contradicciones
            $msg = ope7_efecto_cambio_rasgo($pid, $res);
            break;
        case 12: // Justificación de contradicción
            $msg = ope7_efecto_justificar_contradiccion($pid, $res);
            break;
        case 13: // Creación de técnica (F2.1): ficha → librería, PP por tier, cupo INT/4
            $msg = ope7_efecto_creacion_tecnica($pid, $res, $tr);
            break;
        case 14: // Equipar/cambiar equipo (F3.2): ranuras, cupos Meitou, duplicados
            $msg = ope7_efecto_equipar($pid, $ids);
            break;
        case 15: // Apertura de tienda (F3.3): Comerciante + local + capital + bélicos
            $msg = ope7_efecto_abrir_tienda($pid, $res);
            break;
        case 16: // Cierre/reapertura de tienda (F3.3)
            $msg = ope7_efecto_tienda_cierre($pid, $res);
            break;
        case 38: // Navegación (F4.3, 5.16): valida ubicación/un-presente/límite de
                 // mar, calcula IRT + oráculos + tiempo + víveres y abre la travesía
            $msg = ope7_efecto_navegacion($tr, $pid, $res, $ids);
            break;
        case 17: // Reposición de stock (F3.3): desde el almacén
            $msg = ope7_efecto_tienda_reponer($pid, $res);
            break;
        case 18: // Boletín de precios (F3.3, staff): precios_mercado con motivo
            $msg = ope7_efecto_boletin_precios($pid, $res);
            break;
        case 20: // Ascenso de facción (F4.3, 5.12/13.4): termómetro (skill) +
                 // duros (rango siguiente, cupo, rep_min) + firma
            $msg = ope7_efecto_ascenso_faccion($tr, $pid, $res);
            break;
        case 21: // Subfacción élite (F4.3, 13.2/13.8, staff): Shichibukai cupo 7
            $msg = ope7_efecto_concesion_elite($tr, $pid, $res);
            break;
        case 22: // Cambio de facción (F4.3, 13.7, hito): baja+alta, rango inicial
            $msg = ope7_efecto_cambio_faccion($tr, $pid, $res);
            break;
        case 23: // Deserción (F4.3, 13.7, hito): hostil → criminal/Wanted · legal
            $msg = ope7_efecto_desercion($tr, $pid, $res);
            break;
        case 24: // Infiltración (F4.3, 13.7/13.8, staff): capa oculta solo-staff
            $msg = ope7_efecto_infiltracion($tr, $pid, $res);
            break;
        case 34: // Anuncio de conquista (F4.3, 5.15/16.2-3): control previo,
                 // fases, rondas de asedio, suceso público e invitación
            $msg = ope7_efecto_anuncio_conquista($tr, $pid, $res);
            break;
        case 35: // Responder al asedio (F4.3, 16.4, ligero): defensa activa
            $msg = ope7_efecto_responder_asedio($tr, $pid, $res);
            break;
        case 36: // Resolver/registrar conquista (F4.3, 16.8, staff): veredicto
                 // con motivo + suspensión de tiendas del anterior dueño
            $msg = ope7_efecto_resolver_conquista($tr, $pid, $res);
            break;
        case 37: // Declarar reconquista (F4.3, 16.5): mismas cinco fases
            $msg = ope7_efecto_reconquista($tr, $pid, $res);
            break;
        case 39: // Compra/adquisición de barco (F4.3, 18.4/18.5, ligero): tipo,
                 // madera mínima, acorazado (D4.10), primer barco gratis
            $msg = ope7_efecto_comprar_barco($tr, $pid, $res);
            break;
        case 40: // Construcción de barco (F4.3, 18.4, Astillero): oficio + materiales
            $msg = ope7_efecto_construir_barco($tr, $pid, $res);
            break;
        case 41: // Mejora N1→N2→N3 (F4.3, 18.4): diferencia de precio + madera
            $msg = ope7_efecto_mejorar_barco($tr, $pid, $res);
            break;
        case 42: // Módulos instalar/quitar (F4.3, 18.6): ranuras + oficios
            $msg = ope7_efecto_modulo_barco($tr, $pid, $res);
            break;
        case 43: // Reparación (F4.3, 18.7): grados con Carpintero/Astillero
            $msg = ope7_efecto_reparar_barco($tr, $pid, $res);
            break;
        case 44: // Venta/desguace/baja (F4.3, 18.7, D4.9): fuera de flota
            $msg = ope7_efecto_vender_barco($tr, $pid, $res);
            break;
        case 62: // Muerte (F2.1): veredicto, reliquia, herencia, fruta renace
            $msg = ope7_efecto_muerte($tr, $pid, $res);
            break;
        case 45:
            $msg = '[PENDIENTE] Tirada de akuma sin implementar todavía (F5).';
            break;
        case 50:
            $msg = '[PENDIENTE] Tirada del Conquistador sin implementar todavía (F5).';
            break;
        default:
            $msg = '[PENDIENTE] Efectos del sistema sin implementar: el trámite queda para revisión del staff.';
            break;
    }

    if (function_exists('ope7_notificar')) {
        ope7_notificar((int) ($tr['personaje_id'] ?? 0), 'Tu trámite «' . $tr['tipo'] . '» ha sido ' . $tr['estado'] . '.');
    }
    return array('ok' => true, 'msg' => $msg);
}

/** Fecha on-roll actual (calendario 5.6; si no hay ronda sembrada, la real). */
function ope7_fecha_foro_actual()
{
    global $db;
    // F3.0: cualquier consulta de fecha avanza primero el calendario (perezoso).
    if (function_exists('ope7_calendario_avanzar')) {
        ope7_calendario_avanzar();
    }
    if (ope7_tabla_existe('calendario_foro')) {
        $q = $db->simple_select('ope_calendario_foro', 'fecha_foro_actual', '1=1', array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'DESC'));
        if ($db->num_rows($q)) {
            return (string) $db->fetch_field($q, 'fecha_foro_actual');
        }
    }
    return date('Y-m-d');
}

/** Instantánea mínima de ficha para temas presentes (5.6; ampliar en F2 con estados). */
function ope7_pj_instantanea($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1) {
        return null;
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return null;
    }
    $tids = array();
    $tq = $db->simple_select('ope_tecnicas', 'id', "personaje_id = {$pid} AND activa = 1");
    while ($r = $db->fetch_array($tq)) {
        $tids[] = (int) $r['id'];
    }
    return array(
        'fecha'   => date('Y-m-d H:i'),
        'nivel'   => (int) $f['nivel'],
        'fue'     => (int) $f['fue'], 'des' => (int) $f['des'], 'agi' => (int) $f['agi'],
        'res'     => (int) $f['res'], 'per' => (int) $f['per'], 'inte' => (int) $f['inte'],
        'car'     => (int) $f['car'], 'vol' => (int) $f['vol'],
        'pp_saldo' => (int) $f['pp_saldo'],
        'secundarios' => ope7_pj_secundarios($f),
        'tecnicas' => $tids,
    );
}

/** ¿El personaje tiene un presente abierto? (bloqueo un-presente, 5.6) */
function ope7_pj_tiene_presente_abierto($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('temas_participantes') || !ope7_tabla_existe('temas')) {
        return false;
    }
    $q = $db->query("SELECT COUNT(*) AS c FROM " . ope7_tabla_full('temas_participantes') . " tp "
        . "JOIN " . ope7_tabla_full('temas') . " t ON t.tid = tp.tema_id "
        . "WHERE tp.personaje_id = {$pid} AND t.tipo = 'presente' AND t.estado = 'abierto'");
    $r = $db->fetch_array($q);
    return (int) ($r['c'] ?? 0) > 0;
}

/** Efecto 1 · Apertura de tema (presente/pasado). */
function ope7_efecto_apertura_tema($tr, $pid, $res, $ids)
{
    global $db;
    // Tipo y fecha pueden llegar por ids (formulario) o por resultado (staff/IA).
    $tipo_raw = $res['tipo'] ?? $ids['tipo'] ?? 'presente';
    $tipo = in_array((string) $tipo_raw, array('presente', 'pasado'), true) ? (string) $tipo_raw : 'presente';
    $tema_id = (int) ($ids['tema_id'] ?? 0);
    $zona = trim((string) ($ids['zona'] ?? $res['zona'] ?? ''));
    $tema_tipo = (string) ($res['tema_tipo'] ?? 'social');

    if ($pid < 1) {
        return 'Apertura de tema: sin personaje asociado (pendiente).';
    }
    if ($tipo === 'presente' && ope7_pj_tiene_presente_abierto($pid)) {
        return 'BLOQUEADO: el personaje ya tiene un tema presente abierto (un-presente, 5.6).';
    }
    if (!ope7_tabla_existe('temas')) {
        return 'Apertura de tema: tabla temas no migrada (pendiente).';
    }
    // 7.5: presente = fecha actual del foro · pasado = la DECLARADA (≤ actual).
    $ancla = ope7_fecha_foro_actual();
    if ($tipo === 'pasado') {
        $declarada = trim((string) ($res['fecha_foro'] ?? $ids['fecha_foro'] ?? ''));
        if ($declarada !== '') {
            if (strtotime($declarada) > strtotime($ancla)) {
                return "Apertura BLOQUEADA: un pasado no puede anclarse en el futuro (declarada {$declarada} > actual {$ancla}) — 7.7.";
            }
            $ancla = $declarada;
        }
    }
    $tid_tema = $tema_id > 0 ? $tema_id : (int) ($ids['tema_id'] ?? 0);
    if ($tid_tema < 1) {
        // insert_query devuelve el id (insert_id es método, no propiedad).
        $tid_tema = (int) $db->insert_query('ope_temas', array(
            'tipo' => $tipo, 'fecha_foro' => $ancla, 'fecha_real_apertura' => TIME_NOW,
            'estado' => 'abierto', 'invadible' => $tipo === 'pasado' ? 1 : 0,
            'zona' => $zona, 'tema_tipo' => $tema_tipo,
        ));
    }
    if (ope7_tabla_existe('temas_participantes')) {
        $db->insert_query('ope_temas_participantes', array(
            'tema_id' => $tid_tema, 'personaje_id' => $pid,
            'congelado_desde' => $ancla, 'tramo' => 0,
            'ficha_instantanea' => json_encode(ope7_pj_instantanea($pid), JSON_UNESCAPED_UNICODE),
        ));
    }
    return "Tema {$tipo} anclado al {$ancla} (instantánea de ficha tomada)." . ($tipo === 'presente' ? ' Bloqueo un-presente activo.' : '');
}

/** Efecto 2 · Cierre de temas: skill-cierre-temas (7.2), referencia al
 * veredicto de combate (D2.3), libera la congelación del presente y cierra. */
function ope7_efecto_cierre_tema($tr, $pid, $res)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return 'Cierre de tema: sin personaje asociado (pendiente).';
    }
    $notas = array();
    $tema_id = (int) ($tr['ids']['tema_id'] ?? 0);

    // Tipo del tema (para la banda de tiempo ampliada 7.2).
    $tipo = 'presente';
    if ($tema_id > 0 && ope7_tabla_existe('temas')) {
        $tq = $db->simple_select('ope_temas', 'tipo, estado', "tid = {$tema_id}", array('limit' => 1));
        $tema = $db->fetch_array($tq);
        if ($tema) {
            $tipo = (string) ($tema['tipo'] ?? 'presente');
        }
    }

    // Fórmula de skill-cierre-temas: la skill/IA propone los 7 factores, el
    // sistema valida las bandas y calcula (el staff firma con motivo).
    $q = $db->simple_select('ope_personajes', 'nivel, pp_saldo', "id = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($q);
    $calculo = ope7_cierre_pp_calcular((int) ($pj['nivel'] ?? 1), $tipo, (array) ($res['factores'] ?? array()));
    if (!$calculo['ok']) {
        return 'Cierre de tema BLOQUEADO: ' . $calculo['msg'];
    }
    $pp = (int) $calculo['pp'];
    $base = (int) $calculo['base'];
    $factores_json = json_encode($res['factores'] ?? array(), JSON_UNESCAPED_UNICODE);

    // Referencia al veredicto de combate (D2.3): sala firmada del tema.
    $veredicto_ref = '';
    $sala_id = (int) ($res['sala_id'] ?? 0);
    if ($sala_id > 0 && ope7_tabla_existe('sala_combate')) {
        $sq = $db->simple_select('ope_sala_combate', 'estado, resuelto_por, nota_resolucion', "id = {$sala_id}", array('limit' => 1));
        $sala = $db->fetch_array($sq);
        if ($sala) {
            $veredicto_ref = 'Veredicto de combate (sala ' . $sala_id . ') ' . ((string) ($sala['estado'] ?? '') === 'cerrada' ? 'firmado' : 'SIN firmar — revisar en resolución') . ($sala['resuelto_por'] ? ' por staff ' . (int) $sala['resuelto_por'] : '');
            if (!empty($sala['nota_resolucion'])) {
                $veredicto_ref .= ' · nota: ' . (string) $sala['nota_resolucion'];
            }
            $notas[] = $veredicto_ref;
        }
    }

    if ($pp !== 0) {
        $saldo = (int) ($pj['pp_saldo'] ?? 0);
        $db->update_query('ope_personajes', array('pp_saldo' => $saldo + $pp), "id = {$pid}");
        $notas[] = "PP +{$pp} (base {$base} × factores, tramo del nivel; saldo {$saldo} → " . ($saldo + $pp) . ')';
        if (ope7_tabla_existe('historico_pp')) {
            $db->insert_query('ope_historico_pp', array(
                'personaje_id' => $pid, 'tema_id' => $tema_id, 'tramo' => 0,
                'base_pp' => $base,
                'factores' => $factores_json,
                'pp_otorgado' => $pp,
                // F4.2: el libro general también registra cantidad/concepto (A.3 «Progresión»).
                'cantidad' => $pp, 'concepto' => 'Cierre de tema (skill-cierre-temas)',
                'tramite_id' => (int) ($tr['id'] ?? 0),
                'firmado_por' => (int) ($tr['firma_staff'] ?? 0),
                'motivo' => (string) ($res['motivo'] ?? $veredicto_ref), 'fecha' => TIME_NOW,
            ));
        }
    }
    // Karma de rasgos (6.2): jugado +1 · contradicho +1 al contador.
    if (!empty($res['rasgos']) && ope7_tabla_existe('personaje_rasgos') && ope7_tabla_existe('rasgos')) {
        foreach ((array) $res['rasgos'] as $inf) {
            $rid = (int) ($inf['rasgo_id'] ?? 0);
            if ($rid < 1) {
                continue;
            }
            $estado_inf = (string) ($inf['estado'] ?? '');
            $lk = $db->simple_select('ope_personaje_rasgos', 'id, karma_acumulado, contador_contradicciones, estado', "personaje_id = {$pid} AND rasgo_id = {$rid}", array('limit' => 1));
            $lk_row = $db->fetch_array($lk);
            if (!$lk_row) {
                continue;
            }
            $nuevo = array();
            if ($estado_inf === 'jugado') {
                $karma = (int) $lk_row['karma_acumulado'] + 1;
                $nuevo['karma_acumulado'] = $karma;
                if ($karma >= 5) {
                    $nuevo['estado'] = 'arraigado'; // 6.3: umbral de arraigo
                }
            } elseif ($estado_inf === 'contradicho') {
                $nuevo['contador_contradicciones'] = (int) $lk_row['contador_contradicciones'] + 1;
                $nuevo['tema_ultima_contradiccion_id'] = $tema_id;
            }
            if ($nuevo) {
                $db->update_query('ope_personaje_rasgos', $nuevo, "id = " . (int) $lk_row['id']);
                $notas[] = "Rasgo {$rid}: " . ($estado_inf === 'jugado' ? '+1 karma' : '+1 contradicción');
            }
        }
    }
    // Libera la congelación del participante (7.5: `salio_en`).
    if (ope7_tabla_existe('temas_participantes') && $tema_id > 0) {
        $db->update_query('ope_temas_participantes', array('salio_en' => TIME_NOW), "tema_id = {$tema_id} AND personaje_id = {$pid}");
    }
    // F4.3: si el tema es una travesía, aplica el veredicto (víveres, daños al
    // barco y ubicacion = destino — 17.6). No hace nada si no hay travesía.
    if (function_exists('ope7_travesia_cierre_veredicto')) {
        $vt = ope7_travesia_cierre_veredicto($tema_id, $pid, $res);
        if ($vt !== '') {
            $notas[] = $vt;
        }
    }
    if (ope7_tabla_existe('temas') && $tema_id > 0) {
        $db->update_query('ope_temas', array('estado' => 'cerrado'), "tid = {$tema_id}");
        $notas[] = "Tema {$tema_id} cerrado y congelación liberada.";
    }
    return implode(' · ', $notas) !== '' ? implode(' · ', $notas) : 'Cierre registrado (sin efectos pendientes de su sistema).';
}

/** Coste base de subir un dominio al nivel objetivo (5.3/4.4): 60/120/240/400 PP. */
function ope7_dominio_coste_base($nivel_objetivo)
{
    $tabla = array(2 => 60, 3 => 120, 4 => 240, 5 => 400);
    return (int) ($tabla[(int) $nivel_objetivo] ?? 0);
}

/** Nivel mínimo de personaje para un nivel de dominio (5.3/4.4). */
function ope7_dominio_nivel_pj_minimo($nivel_objetivo)
{
    $tabla = array(2 => 10, 3 => 20, 4 => 35, 5 => 45);
    return (int) ($tabla[(int) $nivel_objetivo] ?? 0);
}

/**
 * Multiplicador de coste anclado al dominio adicional (D4.5): ×1,5 el 1.º
 * adicional adquirido, ×2 el 2.º+ — se fija al adquirir y aplica también a
 * sus subidas. Los dominios de creación (origen 'creacion') siempre ×1,0.
 */
function ope7_dominio_mult_adicional($pid, $dominio_id)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('dominios_personaje')) {
        return 1.0;
    }
    $q = $db->simple_select('ope_dominios_personaje', 'origen, coste_mult', "personaje_id = {$pid} AND dominio_id = " . (int) $dominio_id, array('limit' => 1));
    $row = $db->fetch_array($q);
    if ($row) {
        return (string) $row['origen'] === 'creacion' ? 1.0 : (float) ($row['coste_mult'] ?? 1.5);
    }
    // Adquisición nueva: el multiplicador es el del nº de adicionales ya adquiridos.
    $q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('dominios_personaje') . " WHERE personaje_id = {$pid} AND origen = 'compra'");
    $ya = (int) $db->fetch_field($q, 'n');
    return $ya < 1 ? 1.5 : 2.0;
}

/** Efecto 4 · Compra de PP (automático): atributos (7.3) y dominios (5.3). */
function ope7_efecto_compra_pp($pid, $ids, $tr = null)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return 'Compra de PP: sin personaje (pendiente).';
    }
    // Rama dominios (5.3/4.4): $ids = [dominio_id, nivel objetivo].
    $dominio_id = (int) ($ids['dominio_id'] ?? 0);
    if ($dominio_id > 0) {
        return ope7_efecto_compra_dominio($pid, $dominio_id, (int) ($ids['nivel'] ?? 0), $tr);
    }
    $atributo = (string) ($ids['atributo'] ?? '');
    $bloque = (int) ($ids['bloque'] ?? 0);
    if (!in_array($atributo, array('fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol'), true) || !in_array($bloque, array(5, 10), true)) {
        return 'Compra de PP: datos incompletos (atributo o bloque inválido).';
    }
    $q = $db->simple_select('ope_personajes', 'nivel, pp_saldo, entrenamiento_fin, fue, des, agi, res, per, inte, car, vol', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Compra de PP: personaje no encontrado.';
    }
    if ((int) $f['entrenamiento_fin'] > TIME_NOW) {
        return 'Compra de PP BLOQUEADA: ya hay un entrenamiento en curso (cronómetro 5.6).';
    }
    $coste_punto = ope7_pj_coste_punto_pp((int) $f['nivel']);
    $coste = $coste_punto * $bloque;
    if ((int) $f['pp_saldo'] < $coste) {
        return 'Compra de PP BLOQUEADA: PP insuficientes (coste ' . $coste . ' · saldo ' . (int) $f['pp_saldo'] . ').';
    }
    // 7.3: el bloque debe poder colocarse en algún atributo (la reserva es
    // flexible); se valida contra el atributo más bajo del personaje.
    $techo = ope7_pj_techo_atributo((int) $f['nivel']);
    $min_atr = min((int) $f['fue'], (int) $f['des'], (int) $f['agi'], (int) $f['res'], (int) $f['per'], (int) $f['inte'], (int) $f['car'], (int) $f['vol']);
    if ($min_atr + $bloque > $techo) {
        return "Compra de PP BLOQUEADA: el bloque de {$bloque} no cabe en ningún atributo bajo el techo del nivel (techo {$techo}, atributo más bajo {$min_atr}).";
    }
    $dias = ope7_pj_tiempo_entrenamiento($bloque);
    // 7.3 (D3.5): la compra paga PP y arranca el cronómetro; los puntos entran
    // en la reserva al terminar (ope7_pj_finalizar_entrenamientos, F3.0).
    $db->update_query('ope_personajes', array(
        'pp_saldo'             => (int) $f['pp_saldo'] - $coste,
        'entrenamiento_fin'    => TIME_NOW + $dias * 86400,
        'entrenamiento_bloque' => $bloque,
    ), "id = {$pid}");
    // F4.2: el gasto queda en el libro de PP (A.3 «Progresión» → gastos por concepto).
    if (ope7_tabla_existe('historico_pp')) {
        $db->insert_query('ope_historico_pp', array(
            'personaje_id' => $pid, 'cantidad' => -$coste,
            'concepto' => "Compra de PP: bloque de {$bloque} en {$atributo}",
            'tramite_id' => (int) ($tr['id'] ?? 0),
            'fecha' => TIME_NOW,
        ));
    }
    return "Compra aceptada: −{$coste} PP por el bloque de {$bloque} · entrenamiento de {$dias} días — al terminar, los puntos entran en tu reserva para colocarlos donde quieras (7.3).";
}

/**
 * Compra de dominio (5.3/4.4, ligero automático): adquisición o subida.
 * Coste = base del nivel objetivo (60/120/240/400) × multiplicador anclado
 * (D4.5: ×1,5 el 1.º adicional, ×2 el 2.º+, creación ×1,0). Valida nivel
 * mínimo del personaje, cupo INT (1 adicional por 50 INT, solo adquisiciones),
 * un solo dominio en entrenamiento a la vez (cronómetro independiente del de
 * atributos) y saldo de PP. Arranca el cronómetro de 15 días.
 */
function ope7_efecto_compra_dominio($pid, $dominio_id, $nivel_objetivo, $tr = null)
{
    global $db;
    $dominio_id = (int) $dominio_id;
    $nivel_objetivo = (int) $nivel_objetivo;
    if ($dominio_id < 1 || !ope7_tabla_existe('dominios') || !ope7_tabla_existe('dominios_personaje')) {
        return 'Compra de dominio: módulo no disponible (pendiente).';
    }
    $q = $db->simple_select('ope_dominios', '*', "id = {$dominio_id} AND activo = 1", array('limit' => 1));
    $dom = $db->fetch_array($q);
    if (!$dom) {
        return 'Compra de dominio BLOQUEADA: el dominio no existe o está inactivo.';
    }
    if (!in_array($nivel_objetivo, array(2, 3, 4, 5), true)) {
        return 'Compra de dominio BLOQUEADA: nivel objetivo inválido (2–5).';
    }
    $q = $db->simple_select('ope_personajes', 'nivel, pp_saldo, inte, entrenamiento_fin', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Compra de dominio: personaje no encontrado.';
    }
    // ¿Ya lo tiene? (adquisición nueva vs subida).
    $q = $db->simple_select('ope_dominios_personaje', '*', "personaje_id = {$pid} AND dominio_id = {$dominio_id}", array('limit' => 1));
    $tiene = $db->fetch_array($q);

    // Un solo dominio en entrenamiento a la vez (cronómetro independiente del de atributos).
    if ($tiene && (int) $tiene['entrenamiento_fin'] > TIME_NOW) {
        return 'Compra de dominio BLOQUEADA: este dominio ya está en entrenamiento (cronómetro de 15 días).';
    }
    $q = $db->simple_select('ope_dominios_personaje', 'id', "personaje_id = {$pid} AND entrenamiento_fin > " . TIME_NOW . " AND dominio_id <> {$dominio_id}", array('limit' => 1));
    if ($db->num_rows($q)) {
        return 'Compra de dominio BLOQUEADA: no puedes entrenar dos dominios a la vez (5.3) — termina el actual o espera.';
    }
    // El cronómetro de dominios es INDEPENDIENTE del de atributos (4.4): no se
    // bloquea por entrenamiento de atributos en curso.

    if ($tiene) {
        // Subida: el nivel objetivo debe ser exactamente el siguiente (no saltos).
        if ($nivel_objetivo !== (int) $tiene['nivel'] + 1) {
            return 'Compra de dominio BLOQUEADA: solo puedes subir de nivel en nivel (ahora nv' . (int) $tiene['nivel'] . ', pediste nv' . $nivel_objetivo . ').';
        }
        $es_adicional = (string) $tiene['origen'] === 'compra';
    } else {
        // Adquisición nueva: nivel mínimo de personaje + cupo de INT.
        $min_pj = ope7_dominio_nivel_pj_minimo($nivel_objetivo);
        if ((int) $f['nivel'] < $min_pj) {
            return "Compra de dominio BLOQUEADA: el nivel " . $nivel_objetivo . " exige personaje nv{$min_pj}+ (tienes nv" . (int) $f['nivel'] . ").";
        }
        $q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('dominios_personaje') . " WHERE personaje_id = {$pid} AND origen = 'compra'");
        $adicionales = (int) $db->fetch_field($q, 'n');
        $cupo_int = (int) floor((int) $f['inte'] / 50);
        if ($adicionales >= $cupo_int) {
            return "Compra de dominio BLOQUEADA: cupo de INT alcanzado ({$adicionales}/{$cupo_int} adicionales — 1 por cada 50 INT, Revisión 10).";
        }
        $es_adicional = true;
    }
    // Coste: base del nivel objetivo × multiplicador anclado (D4.5).
    $mult = ope7_dominio_mult_adicional($pid, $dominio_id);
    $coste = (int) round(ope7_dominio_coste_base($nivel_objetivo) * $mult);
    if ((int) $f['pp_saldo'] < $coste) {
        return "Compra de dominio BLOQUEADA: PP insuficientes (coste " . $coste . ' PP ×' . number_format($mult, 2, ',', '') . ' · saldo ' . (int) $f['pp_saldo'] . ').';
    }

    $db->update_query('ope_personajes', array('pp_saldo' => (int) $f['pp_saldo'] - $coste), "id = {$pid}");
    $fin = TIME_NOW + 15 * 86400;
    if ($tiene) {
        $db->update_query('ope_dominios_personaje', array(
            'entrenamiento_fin'    => $fin,
            'entrenamiento_nivel'  => $nivel_objetivo,
        ), "id = " . (int) $tiene['id']);
    } else {
        $db->insert_query('ope_dominios_personaje', array(
            'personaje_id' => $pid, 'dominio_id' => $dominio_id,
            'nivel' => 1, 'entrenamiento_fin' => $fin, 'entrenamiento_nivel' => $nivel_objetivo,
            'origen' => 'compra', 'coste_mult' => $mult,
        ));
    }
    // Gasto en el libro de PP (A.3 «Progresión»).
    if (ope7_tabla_existe('historico_pp')) {
        $db->insert_query('ope_historico_pp', array(
            'personaje_id' => $pid, 'cantidad' => -$coste,
            'concepto' => "Compra de dominio: «" . $dom['nombre'] . "» → nv{$nivel_objetivo} (×" . number_format($mult, 2, ',', '') . ")",
            'tramite_id' => (int) ($tr['id'] ?? 0),
            'fecha' => TIME_NOW,
        ));
    }
    return "Compra de dominio aceptada: «" . $dom['nombre'] . "» hacia nv{$nivel_objetivo} por " . $coste . ' PP (×' . number_format($mult, 2, ',', '') . ') · entrenamiento de 15 días (independiente del de atributos, 5.3).';
}

/** Efecto 5 · Maestría Suprema (hito nv5 de rama). */
function ope7_efecto_maestria($pid, $res)
{
    global $db;
    $dominio_id = (int) ($res['dominio_id'] ?? 0);
    $rama = trim((string) ($res['rama'] ?? ''));
    if ($pid < 1 || $dominio_id < 1 || $rama === '' || !ope7_tabla_existe('dominios_personaje')) {
        return 'Maestría Suprema: datos incompletos (dominio/rama).';
    }
    $db->update_query('ope_dominios_personaje', array('rama' => $rama), "personaje_id = {$pid} AND dominio_id = {$dominio_id}");
    $titulo = (string) ($res['titulo'] ?? '');
    return 'Maestría Suprema registrada: ' . ($titulo !== '' ? $titulo : $rama) . " (dominio {$dominio_id}).";
}

/** Efecto 6 · Producción de oficio: ítem al almacén (base 5.8; tiendas en F3). */
function ope7_efecto_produccion($pid, $res)
{
    global $db;
    $objeto_id = (int) ($res['objeto_id'] ?? 0);
    $cantidad = max(1, (int) ($res['cantidad'] ?? 1));
    if ($pid < 1 || $objeto_id < 1 || !ope7_tabla_existe('almacen') || !ope7_tabla_existe('objetos')) {
        return 'Producción de oficio: sin objeto registrado en el catálogo (5.8) — pendiente.';
    }
    // 9.7: la producción entra en el ALMACÉN (tabla propia), jamás en
    // inventario_personaje (su ENUM de zonas no incluye 'almacen').
    $q = $db->simple_select('ope_almacen', 'id, cantidad', "personaje_id = {$pid} AND objeto_id = {$objeto_id}", array('limit' => 1));
    $row = $db->fetch_array($q);
    if ($row) {
        $db->update_query('ope_almacen', array('cantidad' => (int) $row['cantidad'] + $cantidad), "id = " . (int) $row['id']);
    } else {
        $db->insert_query('ope_almacen', array('personaje_id' => $pid, 'objeto_id' => $objeto_id, 'cantidad' => $cantidad));
    }
    return "Producción aplicada: +{$cantidad} × «" . ope7_objeto_nombre($objeto_id) . "» al almacén.";
}

/** Efecto 14 · Equipar/cambiar equipo (9.2/9.3): ranuras + cupos Meitou. */
function ope7_efecto_equipar($pid, $ids)
{
    $objeto_id = (int) ($ids['objeto_id'] ?? 0);
    $zona = (string) ($ids['zona'] ?? '');
    $r = ope7_inventario_equipar($pid, $objeto_id, $zona);
    return $r['ok'] ? $r['msg'] : 'Equipar BLOQUEADO: ' . $r['msg'];
}

/** Efectos 7 y 8 · Dote/defecto por hito (y Genética Alterada). */
function ope7_efecto_hito_dote($pid, $res)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personaje_dotes')) {
        return 'Hito de dote: sin personaje (pendiente).';
    }
    $dote_id = (int) ($res['dote_id'] ?? 0);
    $defecto_id = (int) ($res['defecto_id'] ?? 0);
    $tema_origen = (int) ($res['tema_origen_id'] ?? 0);
    if ($dote_id < 1 && $defecto_id < 1) {
        return 'Hito de dote: sin dote/defecto en el resultado.';
    }
    if ($dote_id > 0) {
        $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'dote_id' => $dote_id, 'origen' => 'hito', 'tema_origen_id' => $tema_origen, 'fecha' => TIME_NOW));
        return "Dote {$dote_id} adquirida por hito (0 PP, sin tocar la balanza).";
    }
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'defecto_id' => $defecto_id, 'origen' => 'hito', 'tema_origen_id' => $tema_origen, 'fecha' => TIME_NOW));
    return "Defecto {$defecto_id} adquirido por hito (0 PP, sin tocar la balanza).";
}

/** Efecto 8 · Genética Alterada: UNA dote racial de la 2ª raza. */
function ope7_efecto_genetica_alterada($pid, $res)
{
    global $db;
    $dote_id = (int) ($res['dote_id'] ?? 0);
    if ($pid < 1 || $dote_id < 1 || !ope7_tabla_existe('personaje_dotes') || !ope7_tabla_existe('dotes') || !ope7_tabla_existe('personajes')) {
        return 'Genética Alterada: datos incompletos.';
    }
    $pq = $db->simple_select('ope_personajes', 'raza_id, raza_hibrida_id', "id = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($pq);
    $dq = $db->simple_select('ope_dotes', 'raza_id, tipo', "id = {$dote_id}", array('limit' => 1));
    $dt = $db->fetch_array($dq);
    if (!$pj || (int) $pj['raza_hibrida_id'] < 1) {
        return 'Genética Alterada BLOQUEADA: el personaje no es híbrido.';
    }
    if (!$dt || $dt['tipo'] !== 'racial' || (int) $dt['raza_id'] !== (int) $pj['raza_hibrida_id']) {
        return 'Genética Alterada BLOQUEADA: la dote debe ser racial de la SEGUNDA raza (5.4, Revisión 10).';
    }
    $existe = $db->simple_select('ope_personaje_dotes', 'id', "personaje_id = {$pid} AND origen = 'hito' AND dote_id = {$dote_id}", array('limit' => 1));
    if ($db->num_rows($existe)) {
        return 'Genética Alterada BLOQUEADA: esa dote ya está (máximo 1 por hito).';
    }
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'dote_id' => $dote_id, 'origen' => 'hito', 'fecha' => TIME_NOW));
    return "Genética Alterada aplicada: dote {$dote_id} (2ª raza) por hito.";
}

/** Efecto 9 · Evolución por hito: rasgo arraigado → dote. */
function ope7_efecto_evolucion_dote($pid, $res)
{
    global $db;
    $rasgo_id = (int) ($res['rasgo_id'] ?? 0);
    $dote_id = (int) ($res['dote_id'] ?? 0);
    if ($pid < 1 || $rasgo_id < 1 || $dote_id < 1 || !ope7_tabla_existe('personaje_rasgos') || !ope7_tabla_existe('personaje_dotes')) {
        return 'Evolución por hito: datos incompletos.';
    }
    $lk = $db->simple_select('ope_personaje_rasgos', 'id, estado', "personaje_id = {$pid} AND rasgo_id = {$rasgo_id}", array('limit' => 1));
    $row = $db->fetch_array($lk);
    if (!$row || $row['estado'] !== 'arraigado') {
        return 'Evolución BLOQUEADA: el rasgo debe estar arraigado (karma 5, 6.3).';
    }
    $db->delete_query('ope_personaje_rasgos', "id = " . (int) $row['id']);
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'dote_id' => $dote_id, 'origen' => 'hito', 'fecha' => TIME_NOW));
    return "Evolución aplicada: rasgo {$rasgo_id} arraigado → dote {$dote_id} (origen hito).";
}

/** Efectos 10 y 11 · Superación / pérdida-cambio de rasgo (rebalanceo). */
function ope7_efecto_cambio_rasgo($pid, $res)
{
    global $db;
    $rasgo_id = (int) ($res['rasgo_id'] ?? 0);
    $nuevo_id = (int) ($res['rasgo_nuevo_id'] ?? 0);
    if ($pid < 1 || $rasgo_id < 1 || $nuevo_id < 1 || !ope7_tabla_existe('personaje_rasgos')) {
        return 'Cambio de rasgo: datos incompletos.';
    }
    $lk = $db->simple_select('ope_personaje_rasgos', 'id', "personaje_id = {$pid} AND rasgo_id = {$rasgo_id}", array('limit' => 1));
    $row = $db->fetch_array($lk);
    if (!$row) {
        return 'Cambio de rasgo: el rasgo no está en la ficha.';
    }
    $db->delete_query('ope_personaje_rasgos', "id = " . (int) $row['id']);
    $db->insert_query('ope_personaje_rasgos', array('personaje_id' => $pid, 'rasgo_id' => $nuevo_id, 'origen' => 'hito', 'karma_acumulado' => 0, 'estado' => 'activo', 'contador_contradicciones' => 0));
    return "Rasgo {$rasgo_id} sustituido por {$nuevo_id} (misma puntuación, rebalanceado).";
}

/** Efecto 12 · Justificación de contradicción: no cuenta (reset del contador). */
function ope7_efecto_justificar_contradiccion($pid, $res)
{
    global $db;
    $rasgo_id = (int) ($res['rasgo_id'] ?? 0);
    if ($pid < 1 || $rasgo_id < 1 || !ope7_tabla_existe('personaje_rasgos')) {
        return 'Justificación: datos incompletos.';
    }
    $db->update_query('ope_personaje_rasgos', array('contador_contradicciones' => 0, 'estado' => 'activo'), "personaje_id = {$pid} AND rasgo_id = {$rasgo_id}");
    return "Contradicción justificada: contador de rasgo {$rasgo_id} a 0 (6.7).";
}

/**
 * Efecto 13 · Creación de técnica (F2.1) — cap. 8 del Manual del Jugador.
 * Al firmar (con el ciclo del usuario aceptado): valida la ficha, descuenta PP
 * por tier (T1 60 · T2 120 · T3 240 · T4 400 · T5 600), verifica el cupo
 * INT/4 y guarda la técnica en la librería (ope_tecnicas). Si el coste excede
 * el saldo, el trámite se bloquea (BLOQUEADO → rechazado).
 */
function ope7_efecto_creacion_tecnica($pid, $res, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('tecnicas') || !ope7_tabla_existe('personajes')) {
        return 'Creación de técnica: tablas no migradas (pendiente).';
    }
    $nombre = trim((string) ($res['nombre'] ?? ''));
    $tier = (int) ($res['tier'] ?? 0);
    $tipo = (string) ($res['tipo'] ?? 'ofensiva');
    if ($nombre === '' || $tier < 1 || $tier > 5) {
        return 'Creación de técnica BLOQUEADA: ficha incompleta (nombre y tier 1–5 requeridos).';
    }
    if (!in_array($tipo, array('ofensiva', 'defensiva', 'apoyo', 'mixta'), true)) {
        return 'Creación de técnica BLOQUEADA: tipo inválido.';
    }

    $costes = array(1 => 60, 2 => 120, 3 => 240, 4 => 400, 5 => 600);
    $coste = $costes[$tier];
    $q = $db->simple_select('ope_personajes', 'pp_saldo, inte, id', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Creación de técnica: personaje no encontrado.';
    }
    if ((int) $f['pp_saldo'] < $coste) {
        return "Creación de técnica BLOQUEADA: PP insuficientes (coste T{$tier} = {$coste} · saldo " . (int) $f['pp_saldo'] . ').';
    }

    // Cupo INT/4 (8.5): 1 técnica por cada 4 puntos de Intelecto.
    $cupo = (int) floor((int) $f['inte'] / 4);
    $q2 = $db->simple_select('ope_tecnicas', 'COUNT(*) AS c', "personaje_id = {$pid} AND activa = 1");
    $n = (int) $db->fetch_field($q2, 'c');
    if ($n >= $cupo) {
        return "Creación de técnica BLOQUEADA: arsenal completo (cupo INT/4 = {$cupo} técnicas).";
    }

    // Ficha (8.2/8.5): PA 2+tier · PE % por tier · reposo · puerta de turno (T4 3º, T5 5º).
    $pe_pct = array(1 => 10, 2 => 15, 3 => 20, 4 => 30, 5 => 40);
    $reposo = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
    $puerta = array(1 => 0, 2 => 0, 3 => 0, 4 => 3, 5 => 5);
    $efectos = is_array($res['efectos'] ?? null) ? array_values($res['efectos']) : array();
    $requisitos = is_array($res['requisitos'] ?? null) ? $res['requisitos'] : array();

    $db->insert_query('ope_tecnicas', array(
        'personaje_id'  => $pid,
        'nombre'        => $db->escape_string($nombre),
        'tier'          => $tier,
        'tipo'          => $tipo,
        'dominio_id'    => (int) ($res['dominio_id'] ?? 0) > 0 ? (int) $res['dominio_id'] : 0,
        'requisitos'    => json_encode($requisitos, JSON_UNESCAPED_UNICODE),
        'efectos'       => json_encode($efectos, JSON_UNESCAPED_UNICODE),
        'coste_pp'      => $coste,
        'pa'            => 2 + $tier,
        'pe_pct'        => $pe_pct[$tier],
        'reposo'        => $reposo[$tier],
        'puerta_turno'  => $puerta[$tier],
        'origen'        => 'creacion',
        'nota_moderacion' => $db->escape_string((string) ($res['nota_moderacion'] ?? '')),
        'activa'        => 1,
        'fecha'         => TIME_NOW,
    ));
    $db->update_query('ope_personajes', array('pp_saldo' => (int) $f['pp_saldo'] - $coste), "id = {$pid}");
    if (ope7_tabla_existe('historico_pp')) {
        $db->insert_query('ope_historico_pp', array(
            'personaje_id' => $pid, 'cantidad' => -$coste, 'concepto' => "Técnica «{$nombre}» (T{$tier})",
            'tramite_id' => (int) ($tr['id'] ?? 0), 'fecha' => TIME_NOW,
        ));
    }
    return "Técnica «{$nombre}» creada (T{$tier}): −{$coste} PP (saldo " . ((int) $f['pp_saldo'] - $coste) . "). Cupo INT/4: " . ($n + 1) . "/{$cupo}. En la librería de la ficha.";
}

/**
 * Efecto 62 · Muerte de personaje (F2.1) — cap. 11.8 / trámite 62.
 * Veredicto del umbral (PV ≤ −(VOL×2) o PE ≤ −RES), banda de calidad
 * (descuidada/digna/leyenda), ficha → reliquia (estado_vida = muerta),
 * herencia (PP 60→1.000 · berries 5.000→1M × calidad) y fruta renace (5.18).
 * Los efectos de mundo (cartel retirado, baja de facción, suceso de ronda)
 * quedan anotados en efectos_mundo para aplicarse en F4 (D2.2).
 */
function ope7_efecto_muerte($tr, $pid, $res)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('muertes') || !ope7_tabla_existe('personajes')) {
        return 'Muerte: tablas no migradas (pendiente).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Muerte: personaje no encontrado.';
    }

    // Veredicto del umbral (5.10): el resultado de la IA trae el umbral confirmado;
    // se valida contra la ficha (no-crunch: el sistema verifica, no calcula el jugador).
    $umbral = trim((string) ($res['umbral_confirmado'] ?? ''));
    $calidad = in_array((string) ($res['calidad'] ?? ''), array('descuidada', 'digna', 'leyenda'), true)
        ? (string) $res['calidad'] : 'digna';
    $causa = trim((string) ($res['causa'] ?? $tr['motivo'] ?? ''));

    // Herencia: bandas por nivel del muerto (60→1.000 PP · 5.000→1M ฿) × calidad.
    $mult = array('descuidada' => 0.5, 'digna' => 1.0, 'leyenda' => 1.5);
    $nivel = (int) $f['nivel'];
    $pp_banda = max(60, min(1000, (int) round($nivel * 20)));
    $berries = 0;
    if (ope7_tabla_existe('carteras')) {
        $cq = $db->simple_select('ope_carteras', 'cartera, boveda', "personaje_id = {$pid}", array('limit' => 1));
        $cw = $db->fetch_array($cq);
        if ($cw) {
            $berries = (int) ((int) $cw['cartera'] + (int) $cw['boveda']);
        }
    }
    $berries_banda = max(5000, min(1000000, (int) round($berries * 0.1)));
    $herencia = array(
        'pp'      => (int) round($pp_banda * $mult[$calidad]),
        'berries' => (int) round($berries_banda * $mult[$calidad]),
        'calidad' => $calidad, 'mult' => $mult[$calidad],
    );

    // Efectos de mundo (esquema F2, aplicación en F4): cartel retirado · baja de
    // facción · suceso de ronda · fruta renacida.
    $efectos_mundo = array(
        'cartel_retirado' => array('aplicado' => false, 'nota' => 'F4 (5.13): si wanted_base > 0, retirar el cartel.'),
        'baja_faccion'    => array('aplicado' => false, 'nota' => 'F4 (5.12): si faccion_id > 0, registrar la baja.'),
        'suceso_ronda'    => array('aplicado' => false, 'nota' => 'F4 (5.14): el desenlace alimenta la ronda mensual.'),
    );

    // Fruta renace (5.18): la akuma del muerto vuelve al mundo (estado renacida).
    // portador_id se libera con NULL real (SQL crudo: MyBB convierte null en ''
    // y la columna es UNIQUE, así que 0 colisionaría entre frutas libres).
    if ((int) $f['akuma_id'] > 0 && ope7_tabla_existe('akumas')) {
        $db->write_query("UPDATE " . ope7_tabla_full('akumas') . " SET estado = 'renacida', portador_id = NULL WHERE id = " . (int) $f['akuma_id']);
        $efectos_mundo['fruta_renacida'] = array('aplicado' => true, 'akuma_id' => (int) $f['akuma_id']);
    }

    $db->update_query('ope_personajes', array('estado_vida' => 'muerta'), "id = {$pid}");
    $db->insert_query('ope_muertes', array(
        'personaje_id'     => $pid,
        'tema_id'          => (int) ($res['tema_id'] ?? $tr['ids']['tema_id'] ?? 0) > 0 ? (int) ($res['tema_id'] ?? $tr['ids']['tema_id'] ?? 0) : 0,
        'causa'            => $db->escape_string($causa),
        'umbral_confirmado'=> $db->escape_string($umbral),
        'calidad'          => $calidad,
        'herencia'         => json_encode($herencia, JSON_UNESCAPED_UNICODE),
        'efectos_mundo'    => json_encode($efectos_mundo, JSON_UNESCAPED_UNICODE),
        'tramite_id'       => (int) ($tr['id'] ?? 0),
        'firmado_por'      => (int) ($tr['firma_staff'] ?? 0),
        'fecha'            => TIME_NOW,
    ));
    return "Muerte registrada ({$calidad}): ficha → reliquia · herencia " . number_format($herencia['pp']) . " PP y " . number_format($herencia['berries']) . " ฿ · " . ($efectos_mundo['fruta_renacida']['aplicado'] ?? false ? 'fruta renacida' : 'sin fruta') . '. Efectos de mundo pendientes de F4.';
}

/** Notificación (stub F0: registra en el histórico; alertas reales por fase). */
function ope7_notificar($pid, $mensaje)
{
    // TODO(F1): escribir en mybb_rol_alertas (o el canal de alertas canónico).
    return true;
}

/** Registro auditable de cada transición de estado. */
function ope7_tramite_log($tid, $estado, $actor_id, $motivo)
{
    global $db;
    $db->insert_query(ope7_tabla('tramites_historico'), array(
        'tramite_id' => (int) $tid,
        'estado'     => $db->escape_string((string) $estado),
        'actor_id'   => (int) $actor_id,
        'motivo'     => $db->escape_string((string) $motivo),
        'fecha'      => TIME_NOW,
    ));
}

/** Devuelve un trámite por id (con ids/resultado decodificados). */
function ope7_tramite_get($tid)
{
    global $db;
    $tid = (int) $tid;
    if ($tid < 1 || !ope7_tabla_existe('tramites')) {
        return null;
    }
    $q = $db->simple_select(ope7_tabla('tramites'), '*', "id = {$tid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $tr = $db->fetch_array($q);
    $tr['ids'] = json_decode((string) ($tr['ids_json'] ?? ''), true) ?: array();
    $tr['resultado'] = json_decode((string) ($tr['resultado_json'] ?? ''), true) ?: null;
    return $tr;
}

/** Histórico auditable de un trámite (orden cronológico). */
function ope7_tramite_historico($tid)
{
    global $db;
    $tid = (int) $tid;
    if ($tid < 1 || !ope7_tabla_existe('tramites_historico')) {
        return array();
    }
    $q = $db->simple_select(ope7_tabla('tramites_historico'), '*', "tramite_id = {$tid}", array('order_by' => 'id', 'order_dir' => 'ASC'));
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/**
 * Lista de trámites con filtros: numero, estado, solicitante_id, personaje_id.
 * @return array{fila:array}
 */
function ope7_tramite_listar(array $filtros = array(), $limit = 100)
{
    global $db;
    if (!ope7_tabla_existe('tramites')) {
        return array();
    }
    $where = array('1=1');
    if (isset($filtros['numero']) && (int) $filtros['numero'] > 0) {
        $where[] = 'numero = ' . (int) $filtros['numero'];
    }
    if (isset($filtros['estado']) && $filtros['estado'] !== '') {
        $estados = (array) $filtros['estado'];
        $esc = array_map(function ($e) use ($db) {
            return "'" . $db->escape_string($e) . "'";
        }, $estados);
        $where[] = 'estado IN (' . implode(',', $esc) . ')';
    }
    if (isset($filtros['solicitante_id']) && (int) $filtros['solicitante_id'] > 0) {
        $where[] = 'solicitante_id = ' . (int) $filtros['solicitante_id'];
    }
    if (isset($filtros['personaje_id']) && (int) $filtros['personaje_id'] > 0) {
        $where[] = 'personaje_id = ' . (int) $filtros['personaje_id'];
    }
    $opts = array(
        'order_by' => 'id',
        'order_dir' => 'DESC',
        'limit' => (int) $limit,
    );
    // Paginación (listas largas → 20): desplazamiento desde el final más reciente.
    if (isset($filtros['pagina']) && (int) $filtros['pagina'] > 1) {
        $opts['limit_start'] = ((int) $filtros['pagina'] - 1) * (int) $limit;
    }
    $q = $db->simple_select(ope7_tabla('tramites'), '*', implode(' AND ', $where), $opts);
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Total de trámites que cumplen los filtros (para paginar). */
function ope7_tramite_contar(array $filtros = array())
{
    global $db;
    if (!ope7_tabla_existe('tramites')) {
        return 0;
    }
    $where = array('1=1');
    if (isset($filtros['numero']) && (int) $filtros['numero'] > 0) {
        $where[] = 'numero = ' . (int) $filtros['numero'];
    }
    if (isset($filtros['estado']) && $filtros['estado'] !== '') {
        $estados = (array) $filtros['estado'];
        $esc = array_map(function ($e) use ($db) {
            return "'" . $db->escape_string($e) . "'";
        }, $estados);
        $where[] = 'estado IN (' . implode(',', $esc) . ')';
    }
    if (isset($filtros['solicitante_id']) && (int) $filtros['solicitante_id'] > 0) {
        $where[] = 'solicitante_id = ' . (int) $filtros['solicitante_id'];
    }
    if (isset($filtros['personaje_id']) && (int) $filtros['personaje_id'] > 0) {
        $where[] = 'personaje_id = ' . (int) $filtros['personaje_id'];
    }
    $q = $db->simple_select(ope7_tabla('tramites'), 'COUNT(*) AS n', implode(' AND ', $where));
    return (int) $db->fetch_field($q, 'n');
}

/** Conteos por estado para la bandeja (resumen). */
function ope7_tramite_conteos()
{
    global $db;
    $out = array();
    foreach (ope7_tramite_estados() as $e) {
        $out[$e] = 0;
    }
    if (!ope7_tabla_existe('tramites')) {
        return $out;
    }
    $q = $db->query('SELECT estado, COUNT(*) AS n FROM ' . ope7_tabla_full('tramites') . ' GROUP BY estado');
    while ($r = $db->fetch_array($q)) {
        $e = (string) $r['estado'];
        if (isset($out[$e])) {
            $out[$e] = (int) $r['n'];
        }
    }
    return $out;
}

/** Resumen del catálogo: total y desglose por naturaleza (para la bandeja). */
function ope7_tramite_resumen_catalogo()
{
    $cat = ope7_tramites_catalogo();
    $res = array('total' => count($cat), 'ia' => 0, 'ligero' => 0, 'staff' => 0, 'hito' => 0, 'ligeros_sin_ia' => array());
    foreach ($cat as $e) {
        $n = $e['naturaleza'];
        if (isset($res[$n])) {
            $res[$n]++;
        }
        if ($n === 'ligero' && !$e['firma']) {
            $res['ligeros_sin_ia'][] = $e['numero'];
        }
    }
    // Las únicas 3 excepciones 100 % automáticas (22.5): 4 · 45 · 50.
    $res['automaticos'] = array(4, 45, 50);
    return $res;
}
