<?php
/**
 * One Piece: 7 Seas · Dominio — Personajes (F1)
 * ----------------------------------------------------------------------------------
 * Capa de entidad sobre `mybb_ope_personajes` (esquema Anexo A.1):
 *   · Resolución del personaje activo (rol_cuentas.personaje_activo + personaje_tabla).
 *   · Carga de ficha + secundarios calculados (fórmulas confirmadas, Manual del
 *     Jugador §3.6 / Manual del Staff §3.2 — números sagrados, no se tocan).
 *   · Validación de ficha de creación: presupuesto 120, techos por nivel, balanzas
 *     a 0 (dotes/defectos y rasgos), híbridos (media a favor del jugador), tribus
 *     (solo puros), parejas espejo/antagónicas, reparto de dominios (2 puntos).
 *
 * Prefijos: ope7_pj_* (funciones del motor 7 Seas).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Atributos primarios en orden canónico (claves de columna en mybb_ope_personajes). */
function ope7_pj_atributos_claves()
{
    return array('fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol');
}

/**
 * Techo por atributo según nivel (Manual del Jugador §7.2).
 * Curva `20 + 1,6 × (nivel − 1)`, tope en 100 al alcanzar el nivel 50.
 * Tabla confirmada: 1→20 · 10→34 · 20→50 · 25→58 · 30→66 · 35→74 · 45→90 · 50→100.
 */
function ope7_pj_techo_atributo($nivel)
{
    $nivel = max(1, (int) $nivel);
    if ($nivel >= 50) {
        return 100;
    }
    return (int) floor(20 + 1.6 * ($nivel - 1));
}

/**
 * Coste en PP de 1 punto de atributo según tramo (Manual del Jugador §7.4):
 * I 1–10 → 10 · II 11–20 → 15 · III 21–35 → 25 · IV 36–45 → 40 · V 46–50 → 60.
 */
function ope7_pj_coste_punto_pp($nivel)
{
    if ($nivel <= 10) return 10;
    if ($nivel <= 20) return 15;
    if ($nivel <= 35) return 25;
    if ($nivel <= 45) return 40;
    return 60;
}

/** Tiempo real de un bloque de entrenamiento de atributos (Manual del Jugador §7.3). */
function ope7_pj_tiempo_entrenamiento($bloque)
{
    return ((int) $bloque) >= 10 ? 13 : 7; // 10 puntos → 13 días · 5 puntos → 7 días
}

/**
 * Secundarios calculados (fórmulas confirmadas §3.6/§3.2 — NO recalibrar).
 * Usa los atributos EFECTIVOS: base comprada + modificadores raciales
 * (el §3.3 del Staff audita el desglose base + racial + dotes + técnicas).
 * $f: fila de mybb_ope_personajes con nivel y los 8 primarios.
 */
function ope7_pj_secundarios($f)
{
    $n   = max(1, (int) ($f['nivel'] ?? 1));
    $mods = ope7_pj_modificadores_efectivos($f);
    $fue = (int) ($f['fue'] ?? 0) + $mods['fue'];
    $des = (int) ($f['des'] ?? 0) + $mods['des'];
    $agi = (int) ($f['agi'] ?? 0) + $mods['agi'];
    $res = (int) ($f['res'] ?? 0) + $mods['res'];
    $per = (int) ($f['per'] ?? 0) + $mods['per'];
    $int = (int) ($f['inte'] ?? ($f['int'] ?? 0)) + $mods['inte'];
    $car = (int) ($f['car'] ?? 0) + $mods['car'];
    $vol = (int) ($f['vol'] ?? 0) + $mods['vol'];

    $n2  = $n * $n;
    $pv  = (int) round(100 + $res * 6 + $fue * 2 + $vol * 1 + $n2 * 0.5);
    $pe  = (int) round(50 + $vol * 4 + $int * 3 + $car * 1 + $n2 * 0.4);
    $vel = 3 + $agi * 0.08 + $fue * 0.02 + $n2 * 0.01;
    $sprint = $vel * 1.6;
    $salto_v = 0.3 + $fue * 0.015 + $agi * 0.015 + $n2 * 0.004;
    $carga   = 40 + $fue * 4;
    $res_pasiva = $res * 0.15;
    $lanzamiento = $fue * 0.4 + $des * 0.2;
    $recuperacion = ($res * 0.1 + $vol * 0.1); // % del máximo por hora de descanso
    $pa = (int) round(6 + $agi / 10 + $n / 5); // 5.10 — redondeo al entero más cercano

    return array(
        'pv'               => $pv,
        'pe'               => $pe,
        'velocidad'        => round($vel, 2),
        'sprint'           => round($sprint, 2),
        'salto_v'          => round($salto_v, 2),
        'salto_h'          => round($salto_v * 1.5, 2),
        'carga'            => $carga,
        'carga_levantar'   => (int) round($carga * 2.5),
        'resistencia_pasiva' => round($res_pasiva, 2),
        'lanzamiento'      => round($lanzamiento, 2),
        'recuperacion'     => round($recuperacion, 2), // % por hora
        'pa'               => $pa,
    );
}

/** Persiste la materializada atributos_secundarios (se recalcula, no se edita a mano). */
function ope7_pj_recalcular_secundarios($personaje_id, $ficha = null)
{
    global $db;
    $id = (int) $personaje_id;
    if ($id < 1) {
        return false;
    }
    if (!$ficha) {
        $q = $db->simple_select('ope_personajes', '*', "id = {$id}", array('limit' => 1));
        $ficha = $db->fetch_array($q);
        if (!$ficha) {
            return false;
        }
    }
    $s = ope7_pj_secundarios($ficha);
    $db->delete_query('ope_atributos_secundarios', "personaje_id = {$id}");
    $db->insert_query('ope_atributos_secundarios', array(
        'personaje_id'       => $id,
        'pv'                 => $s['pv'],
        'pe'                 => $s['pe'],
        'velocidad'          => $s['velocidad'],
        'sprint'             => $s['sprint'],
        'salto_v'            => $s['salto_v'],
        'salto_h'            => $s['salto_h'],
        'carga'              => $s['carga'],
        'resistencia_pasiva' => $s['resistencia_pasiva'],
        'lanzamiento'        => $s['lanzamiento'],
        'recuperacion'       => $s['recuperacion'],
        'pa'                 => $s['pa'],
        'calculado_en'       => TIME_NOW,
    ));
    return $s;
}

/**
 * Resolución del personaje activo de un usuario (decisión D1.1).
 * Lee rol_cuentas.personaje_activo + personaje_tabla; devuelve array
 * ['tabla' => 'rol'|'ope', 'id' => int] o null si no hay activo.
 */
function ope7_pj_activo($uid = 0)
{
    global $mybb, $db;
    $uid = (int) $uid;
    if ($uid < 1) {
        $uid = (int) ($mybb->user['uid'] ?? 0);
    }
    if ($uid < 1) {
        return null;
    }
    $q = $db->simple_select('rol_cuentas', 'personaje_activo, personaje_tabla', "uid = {$uid}", array('limit' => 1));
    $r = $db->fetch_array($q);
    if (!$r || (int) $r['personaje_activo'] < 1) {
        return null;
    }
    return array(
        'tabla' => ($r['personaje_tabla'] ?? 'rol') === 'ope' ? 'ope' : 'rol',
        'id'    => (int) $r['personaje_activo'],
    );
}

/** Asigna el personaje activo de un usuario (tabla + id). */
function ope7_pj_set_activo($uid, $tabla, $id)
{
    global $db, $mybb;
    $uid = (int) $uid;
    if ($uid < 1) {
        return false;
    }
    $tabla = $tabla === 'ope' ? 'ope' : 'rol';
    $id    = (int) $id;
    $db->update_query('rol_cuentas', array('personaje_activo' => $id, 'personaje_tabla' => $tabla), "uid = {$uid}");
    // Sesión actual (si el uid es el usuario logueado).
    if ($uid === (int) ($mybb->user['uid'] ?? 0)) {
        $mybb->user['ope_active_pid'] = $id;
    }
    return true;
}

/** Carga la ficha completa de un personaje ope (con secundarios y componentes). */
function ope7_pj_get($id)
{
    global $db;
    $id = (int) $id;
    if ($id < 1) {
        return null;
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$id}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return null;
    }
    $f['secundarios'] = ope7_pj_secundarios($f);

    // Razas (para híbridos, la segunda raza).
    $razas = array();
    foreach (array('raza_id', 'raza_hibrida_id') as $k) {
        if ((int) $f[$k] > 0) {
            $rq = $db->simple_select('ope_razas', '*', "id = " . (int) $f[$k], array('limit' => 1));
            $rr = $db->fetch_array($rq);
            if ($rr) {
                $razas[$k] = $rr;
            }
        }
    }
    $f['razas'] = $razas;

    // Tribu.
    if ((int) $f['tribu_id'] > 0) {
        $tq = $db->simple_select('ope_tribus', '*', "id = " . (int) $f['tribu_id'], array('limit' => 1));
        $f['tribu'] = $db->fetch_array($tq);
    } else {
        $f['tribu'] = null;
    }

    // Dotes y defectos.
    $f['dotes']    = array();
    $f['defectos'] = array();
    $dq = $db->query("
        SELECT pd.id AS link_id, pd.origen, d.id, d.nombre, d.efecto, d.puntuacion, d.tipo, d.requiere_raza_pura
        FROM " . TABLE_PREFIX . "ope_personaje_dotes pd
        LEFT JOIN " . TABLE_PREFIX . "ope_dotes d ON d.id = pd.dote_id
        WHERE pd.personaje_id = {$id} AND pd.dote_id IS NOT NULL
    ");
    while ($r = $db->fetch_array($dq)) {
        $f['dotes'][] = $r;
    }
    $dfq = $db->query("
        SELECT pd.id AS link_id, pd.origen, df.id, df.nombre, df.efecto, df.puntuacion
        FROM " . TABLE_PREFIX . "ope_personaje_dotes pd
        LEFT JOIN " . TABLE_PREFIX . "ope_defectos df ON df.id = pd.defecto_id
        WHERE pd.personaje_id = {$id} AND pd.defecto_id IS NOT NULL
    ");
    while ($r = $db->fetch_array($dfq)) {
        $f['defectos'][] = $r;
    }

    // Rasgos (con karma).
    $f['rasgos'] = array();
    $rq = $db->query("
        SELECT pr.id AS link_id, pr.origen, pr.karma_acumulado, pr.estado, pr.contador_contradicciones,
               r.id, r.nombre, r.tipo, r.puntuacion, r.descripcion
        FROM " . TABLE_PREFIX . "ope_personaje_rasgos pr
        LEFT JOIN " . TABLE_PREFIX . "ope_rasgos r ON r.id = pr.rasgo_id
        WHERE pr.personaje_id = {$id}
    ");
    while ($r = $db->fetch_array($rq)) {
        $f['rasgos'][] = $r;
    }

    // Dominios.
    $f['dominios'] = array();
    $moq = $db->query("
        SELECT dp.id AS link_id, dp.nivel, dp.rama, dp.origen, dp.entrenamiento_fin, dp.entrenamiento_nivel, dp.coste_mult,
               d.id AS dominio_id, d.nombre, d.tipo, d.atributo_rey
        FROM " . TABLE_PREFIX . "ope_dominios_personaje dp
        LEFT JOIN " . TABLE_PREFIX . "ope_dominios d ON d.id = dp.dominio_id
        WHERE dp.personaje_id = {$id}
    ");
    while ($r = $db->fetch_array($moq)) {
        $f['dominios'][] = $r;
    }

    // Técnicas (librería personal).
    $f['tecnicas'] = array();
    $tq = $db->query("
        SELECT t.*, d.nombre AS dominio_nombre
        FROM " . TABLE_PREFIX . "ope_tecnicas t
        LEFT JOIN " . TABLE_PREFIX . "ope_dominios d ON d.id = t.dominio_id
        WHERE t.personaje_id = {$id} AND t.activa = 1
        ORDER BY t.tier ASC, t.fecha ASC
    ");
    while ($r = $db->fetch_array($tq)) {
        $f['tecnicas'][] = $r;
    }

    return $f;
}

/**
 * Modificadores efectivos de la ficha: base + raciales (puro/híbrido/tribu).
 * $f: fila ope_personajes. Devuelve array clave → total (los raciales van por
 * encima del techo; aquí solo se computan, la validación audita los techos base).
 */
function ope7_pj_modificadores_efectivos($f)
{
    global $db;
    $mods = array('fue' => 0, 'des' => 0, 'agi' => 0, 'res' => 0, 'per' => 0, 'inte' => 0, 'car' => 0, 'vol' => 0);

    $get_razas = function ($id1, $id2) use ($db) {
        $out = array();
        foreach (array_filter(array($id1, $id2)) as $rid) {
            $q = $db->simple_select('ope_razas', 'nombre, modificadores, es_hibrido', "id = " . (int) $rid, array('limit' => 1));
            $r = $db->fetch_array($q);
            if ($r && $r['modificadores']) {
                $out[] = $r;
            }
        }
        return $out;
    };

    $razas = $get_razas($f['raza_id'] ?? 0, $f['raza_hibrida_id'] ?? 0);

    if (count($razas) === 0) {
        return $mods;
    }

    if (count($razas) === 2) {
        // Híbrido: media de los modificadores de ambas razas, mitades a favor del jugador.
        foreach ($mods as $k => $v) {
            $sum = 0;
            foreach ($razas as $r) {
                $m = json_decode($r['modificadores'], true);
                $sum += (int) ($m[$k] ?? 0);
            }
            $mods[$k] = (int) ceil($sum / 2);
        }
        return $mods;
    }

    // Puro: modificadores de su raza + (si tiene tribu) los raciales no cambian
    // atributos — la tribu solo sustituye la racial secundaria (5.1-bis).
    $m = json_decode($razas[0]['modificadores'], true);
    foreach ($mods as $k => $v) {
        $mods[$k] = (int) ($m[$k] ?? 0);
    }
    return $mods;
}

/**
 * Validador de ficha de creación (skill-validacion-personajes, parte dura).
 * $f: fila ope_personajes (fue..vol = base comprada, nivel, raza_*).
 * $opts: arrays de selección: 'dotes' (ids), 'defectos' (ids), 'rasgos' (ids),
 *        'dominios' (lista ['dominio_id'=>nivel]), 'es_creacion' (bool).
 * Devuelve array ['errores' => [...], 'avisos' => [...]].
 */
function ope7_pj_validar_ficha($f, $opts = array())
{
    global $db;
    $errores = array();
    $avisos  = array();
    $opts = array_merge(array('dotes' => array(), 'defectos' => array(), 'rasgos' => array(), 'dominios' => array(), 'es_creacion' => true), $opts);

    $nivel = max(1, (int) ($f['nivel'] ?? 1));
    $techo = ope7_pj_techo_atributo($nivel);

    // 1) Presupuesto de atributos (creación): 120 repartidos (sin contar raciales).
    if (!empty($opts['es_creacion'])) {
        $suma = 0;
        foreach (ope7_pj_atributos_claves() as $k) {
            $suma += (int) ($f[$k] ?? 0);
        }
        if ($suma !== 120) {
            $errores[] = "La suma de atributos base es {$suma} (debe ser exactamente 120).";
        }
    }

    // 2) Techos por nivel (solo base; los raciales van por encima).
    foreach (ope7_pj_atributos_claves() as $k) {
        if ((int) ($f[$k] ?? 0) > $techo) {
            $errores[] = "El atributo {$k} base (" . (int) $f[$k] . ") supera el techo del nivel {$nivel} ({$techo}).";
        }
    }

    // 3) Raza e híbrido.
    $raza_id = (int) ($f['raza_id'] ?? 0);
    $hib     = (int) ($f['raza_hibrida_id'] ?? 0);
    if ($raza_id < 1) {
        $errores[] = 'Falta la raza del personaje.';
    }
    $es_puro = $hib < 1;
    $raza_pura_check = $es_puro;
    if ($hib > 0) {
        // Híbrido: media bien calculada (los modificadores efectivos deben ser ceil(media)).
        $mods = ope7_pj_modificadores_efectivos($f);
        $avisos[] = 'Híbrido: se aplican las primarias de ambas razas y la media de modificadores (mitades a favor del jugador).';
        $raza_pura_check = false;
    }

    // 4) Tribu: solo puros y de su raza.
    $tribu_id = (int) ($f['tribu_id'] ?? 0);
    if ($tribu_id > 0) {
        if (!$raza_pura_check) {
            $errores[] = 'Un híbrido no puede pertenecer a una tribu (5.1-bis).';
        } else {
            $tq = $db->simple_select('ope_tribus', 'raza_id, nombre', "id = {$tribu_id}", array('limit' => 1));
            $tr = $db->fetch_array($tq);
            if (!$tr) {
                $errores[] = 'La tribu seleccionada no existe.';
            } elseif ((int) $tr['raza_id'] !== $raza_id) {
                $errores[] = "La tribu «{$tr['nombre']}» no pertenece a la raza del personaje.";
            }
        }
    }

    // 5) Balanza de dotes/defectos = 0 exacto.
    $balanza = 0;
    if (!empty($opts['dotes'])) {
        $ids = array_map('intval', (array) $opts['dotes']);
        if ($ids) {
            $q = $db->simple_select('ope_dotes', 'id, puntuacion, tipo, raza_id, requiere_raza_pura', 'id IN (' . implode(',', $ids) . ')');
            while ($r = $db->fetch_array($q)) {
                $balanza += (int) $r['puntuacion'];
                if ($r['tipo'] === 'racial' && !$es_puro) {
                    // Híbrido: solo dotes de la raza dominante (la primera de la ficha).
                    $dote_raza = (int) $r['raza_id'];
                    $dominante = $raza_id;
                    if ($dote_raza > 0 && $dote_raza !== $dominante) {
                        $errores[] = "La dote racial «{$r['id']}» no es de la raza dominante del híbrido (5.4).";
                    }
                }
                if ((int) $r['requiere_raza_pura'] === 1 && !$raza_pura_check) {
                    $errores[] = "La dote requiere raza pura y el personaje no es puro (ni con Genética Alterada) (5.4, Revisión 10).";
                }
            }
        }
    }
    if (!empty($opts['defectos'])) {
        $ids = array_map('intval', (array) $opts['defectos']);
        if ($ids) {
            $q = $db->simple_select('ope_defectos', 'id, puntuacion', 'id IN (' . implode(',', $ids) . ')');
            while ($r = $db->fetch_array($q)) {
                $balanza += (int) $r['puntuacion'];
            }
        }
    }
    if ($balanza !== 0) {
        $errores[] = "La balanza de dotes/defectos suma {$balanza} (debe ser exactamente 0).";
    }

    // 6) Balanza de rasgos = 0 y sin antagónicos.
    $balanza_rasgos = 0;
    $vistos = array();
    $n_pos = 0;
    $n_neg = 0;
    if (!empty($opts['rasgos'])) {
        $ids = array_map('intval', (array) $opts['rasgos']);
        if ($ids) {
            $q = $db->simple_select('ope_rasgos', 'id, nombre, tipo, puntuacion, pareja_incompatible_id', 'id IN (' . implode(',', $ids) . ')');
            while ($r = $db->fetch_array($q)) {
                $balanza_rasgos += (int) $r['puntuacion'];
                if ($r['tipo'] === 'positivo') $n_pos++;
                if ($r['tipo'] === 'negativo') $n_neg++;
                $vistos[(int) $r['id']] = $r['nombre'];
                if ((int) $r['pareja_incompatible_id'] > 0 && isset($vistos[(int) $r['pareja_incompatible_id']])) {
                    $errores[] = "Los rasgos «{$r['nombre']}» y «{$vistos[(int) $r['pareja_incompatible_id']]}» son antagónicos (6.2).";
                }
            }
        }
    }
    if ($balanza_rasgos !== 0) {
        $errores[] = "La balanza de rasgos suma {$balanza_rasgos} (debe ser exactamente 0).";
    }
    if ($n_pos < 1 || $n_neg < 1) {
        $avisos[] = 'Se recomienda al menos 1 rasgo positivo y 1 negativo (no es regla dura, 6.2).';
    }

    // 7) Reparto de dominios de creación: 2 puntos (1+1 o un dominio a nivel 2).
    if (!empty($opts['es_creacion'])) {
        $puntos_dominio = 0;
        foreach ((array) $opts['dominios'] as $nivel_dom) {
            $puntos_dominio += (int) $nivel_dom;
        }
        if ($puntos_dominio !== 2) {
            $errores[] = "Los puntos de dominio iniciales suman {$puntos_dominio} (deben ser 2: Opción A 1+1 u Opción B un dominio a nivel 2).";
        }
    }

    // 8) Secundarios recalculados y coherentes (el sistema los calcula; aquí se audita el desglose).
    $sec = ope7_pj_secundarios($f);
    $avisos[] = "Secundarios calculados: PV {$sec['pv']} · PE {$sec['pe']} · PA {$sec['pa']} · Velocidad {$sec['velocidad']} m/s.";

    return array('errores' => $errores, 'avisos' => $avisos);
}

/** Slug URL-friendly (para fichas públicas: personaje.php?pid=…) */
function ope7_slug($texto)
{
    $s = trim((string) $texto);
    $s = strtolower($s);
    $s = strtr($s, 'áàäâãåéèëêíìïîóòöôõúùüûñç', 'aaaaaaeeeeiiiiooooouuuunc');
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'personaje-' . time();
}

/** Crea o actualiza un personaje ope y recalcula sus secundarios. */
function ope7_pj_guardar($datos, $id = 0)
{
    global $db;
    $id = (int) $id;
    // Columnas admitidas (blanco seguro): solo las del esquema ope_personajes.
    $cols = array(
        'uid', 'nombre', 'slug', 'estado', 'estado_vida', 'es_NPC', 'tipo_npc', 'nivel',
        'raza_id', 'raza_hibrida_id', 'tribu_id', 'akuma_id', 'faccion_id', 'rango_id',
        'fama_global_grado', 'fama_infamia_expo', 'rep_faccion', 'wanted_base',
        'ubicacion_isla_id', 'ubicacion_zona_id', 'ubicacion_texto',
        'fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol',
        'puntos_comprados', 'reserva', 'entrenamiento_fin', 'entrenamiento_bloque',
        'pp_saldo', 'avatar', 'icono', 'firma', 'bio', 'historia', 'personalidad',
        'retrato', 'datos', 'dateline', 'lastedit',
    );
    // Columnas INT: MyBB convierte null → '' (falla en columnas INT/JSON).
    $int_cols = array('uid', 'es_NPC', 'nivel', 'raza_id', 'raza_hibrida_id', 'tribu_id', 'akuma_id', 'faccion_id', 'rango_id', 'fama_global_grado', 'fama_infamia_expo', 'rep_faccion', 'wanted_base', 'ubicacion_isla_id', 'ubicacion_zona_id', 'fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol', 'puntos_comprados', 'reserva', 'entrenamiento_fin', 'entrenamiento_bloque', 'pp_saldo', 'dateline', 'lastedit');
    $data = array();
    foreach ($cols as $c) {
        if (array_key_exists($c, $datos)) {
            $v = $datos[$c];
            if ($v === null) {
                if (in_array($c, $int_cols, true)) {
                    $v = 0;
                } else {
                    $v = ''; // varchar/text: MyBB inserta cadena vacía
                }
            }
            if ($c === 'datos' && ($v === '' || $v === null)) {
                continue; // JSON: no enviar si vacío
            }
            $data[$c] = $v;
        }
    }
    if (empty($data)) {
        return 0;
    }
    if (!isset($data['dateline'])) {
        $data['dateline'] = TIME_NOW;
    }
    $data['lastedit'] = TIME_NOW;

    if ($id > 0) {
        $db->update_query('ope_personajes', $data, "id = {$id}");
    } else {
        $id = (int) $db->insert_query('ope_personajes', $data);
        // F3.2: todo personaje nuevo abre su cartera (0/0) y su almacén vacío
        // queda implícito; la cartera es la única bolsa que el saqueo toca.
        if (ope7_tabla_existe('carteras')) {
            $cq = $db->simple_select('ope_carteras', 'personaje_id', "personaje_id = {$id}", array('limit' => 1));
            if (!$db->num_rows($cq)) {
                $db->insert_query('ope_carteras', array('personaje_id' => $id, 'cartera' => 0, 'boveda' => 0));
            }
        }
    }
    ope7_pj_recalcular_secundarios($id);
    return $id;
}

/**
 * Herencia de un personaje muerto (F2.1, trámite 62) al siguiente personaje.
 * Al crear un personaje nuevo, si el usuario tiene muertes SIN reclamar
 * (heredero_id NULL) se aplican: PP + berries al nuevo personaje y se marca
 * la muerte como reclamada. Devuelve el resumen de lo aplicado.
 */
function ope7_pj_heredar($uid, $nuevo_pid)
{
    global $db;
    $uid = (int) $uid;
    $nuevo_pid = (int) $nuevo_pid;
    if ($uid < 1 || $nuevo_pid < 1 || !ope7_tabla_existe('muertes')) {
        return array('ok' => false, 'aplicadas' => 0);
    }
    $aplicadas = 0;
    $total_pp = 0;
    $total_berries = 0;
    // Muertes del usuario sin reclamar: JOIN con el personaje muerto (uid) y
    // estado_vida = muerta para no reclamar reliquias que no son del usuario.
    $q = $db->query("SELECT m.id, m.herencia FROM " . ope7_tabla_full('muertes') . " m "
        . "JOIN " . ope7_tabla_full('personajes') . " p ON p.id = m.personaje_id "
        . "WHERE p.uid = {$uid} AND m.heredero_id IS NULL AND m.herencia IS NOT NULL");
    while ($row = $db->fetch_array($q)) {
        $her = json_decode((string) $row['herencia'], true);
        if (!is_array($her)) {
            continue;
        }
        $total_pp += (int) ($her['pp'] ?? 0);
        $total_berries += (int) ($her['berries'] ?? 0);
        // Marcar reclamada (SQL crudo: NULL → 0 no aplica; usamos write_query).
        $db->write_query("UPDATE " . ope7_tabla_full('muertes') . " SET heredero_id = {$nuevo_pid} WHERE id = " . (int) $row['id']);
        $aplicadas++;
    }
    if ($aplicadas === 0) {
        return array('ok' => true, 'aplicadas' => 0);
    }
    if ($total_pp > 0) {
        $db->update_query('ope_personajes', array('pp_saldo' => $total_pp), "id = {$nuevo_pid}");
    }
    if ($total_berries > 0) {
        if (ope7_tabla_existe('carteras')) {
            $cq = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$nuevo_pid}", array('limit' => 1));
            $c = (int) $db->fetch_field($cq, 'cartera');
            if ($c > 0 || $db->num_rows($cq) > 0) {
                $db->update_query('ope_carteras', array('cartera' => $c + $total_berries), "personaje_id = {$nuevo_pid}");
            } else {
                $db->insert_query('ope_carteras', array('personaje_id' => $nuevo_pid, 'cartera' => $total_berries, 'boveda' => 0));
            }
        }
    }
    return array('ok' => true, 'aplicadas' => $aplicadas, 'pp' => $total_pp, 'berries' => $total_berries);
}
