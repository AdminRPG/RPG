<?php
/**
 * One Piece: 7 Seas · Migración del esquema canónico (Anexo A.1 del Manual del Staff)
 * ----------------------------------------------------------------------------------
 * Crea TODAS las tablas del Anexo A.1 con prefijo `mybb_ope_*` (decisión D0.3-bis),
 * recreando desde cero el esquema que los manuales definen por sistema.
 *
 * - Idempotente: CREATE TABLE IF NOT EXISTS; se puede re-ejecutar sin efectos.
 * - Esquema exclusivamente canónico: no se crea ni referencia ninguna tabla `mybb_rol_*`.
 * - Sin dados (principio 1): no existe ninguna tabla de tiradas; las únicas
 *   excepciones (Conquistador 5.19, fruta aleatoria 5.18) registran resultados,
 *   no resuelven acciones.
 *
 * Ejecutar:
 *   php scripts/migrate-7seas-esquema.php
 *
 * Fuente: docs/sistema/Manual_del_Staff.md — Anexo A.1 (y capítulos por sistema).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$P = 'mybb_ope_'; // prefijo canónico del esquema 7 Seas (D0.3-bis)

/** Ejecuta una sentencia y aborta con mensaje claro si falla. */
function ope7_run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] $label\n";
}

/** Añade una columna si no existe (idempotente, compatible MySQL 8). */
function ope7_add_col(mysqli $db, string $tabla, string $col, string $def, string $after = ''): void
{
    $q = $db->query("SELECT COUNT(*) c FROM information_schema.COLUMNS "
        . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mybb_ope_{$tabla}' AND COLUMN_NAME = '{$col}'");
    $existe = $q && (int) $q->fetch_assoc()['c'] > 0;
    if ($existe) {
        echo "  [ok ] {$tabla}.{$col} ya existe\n";
        return;
    }
    $after_sql = $after !== '' ? " AFTER `{$after}`" : '';
    if ($db->query("ALTER TABLE mybb_ope_{$tabla} ADD COLUMN `{$col}` {$def}{$after_sql}") === false) {
        fwrite(STDERR, "  [ERROR] {$tabla}.{$col}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$tabla}.{$col} añadida\n";
}

echo "=== Migración 7 Seas (mybb_ope_*) ===\n";

// ─────────────────────────────────────────────────────────────
// 5.1 / 5.1-bis — Razas, raciales y tribus
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'razas', "
CREATE TABLE IF NOT EXISTS {$P}razas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    lore TEXT NULL,
    altura_min DECIMAL(6,2) NULL,
    altura_max DECIMAL(6,2) NULL,
    vida_media SMALLINT UNSIGNED NULL,
    edad_min SMALLINT UNSIGNED NULL,
    modificadores JSON NULL COMMENT 'FUE/DES/AGI/RES/PER/INT/CAR/VOL (tabla 5.1)',
    es_hibrido TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'raciales', "
CREATE TABLE IF NOT EXISTS {$P}raciales (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raza_id INT UNSIGNED NOT NULL,
    tipo ENUM('primaria','secundaria') NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    descripcion TEXT NULL,
    efecto JSON NULL COMMENT 'efecto mecánico estructurado',
    PRIMARY KEY (id),
    KEY idx_raza (raza_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'tribus', "
CREATE TABLE IF NOT EXISTS {$P}tribus (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    raza_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    descripcion TEXT NULL,
    racial_nombre VARCHAR(80) NOT NULL,
    racial_efecto JSON NULL,
    sustituye_a VARCHAR(80) NULL COMMENT 'racial secundaria estándar que reemplaza',
    PRIMARY KEY (id),
    KEY idx_raza (raza_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.2 / 5.6 / 5.11 / 5.12 / 5.18 / 5.21-bis — Personajes (ficha)
// ─────────────────────────────────────────────────────────────
// Cuentas de rol: puntero de personaje activo por usuario + rol staff/narrador.
ope7_run($db, 'cuentas', "
CREATE TABLE IF NOT EXISTS {$P}cuentas (
    uid INT UNSIGNED NOT NULL,
    personaje_activo INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'id en mybb_ope_personajes',
    personaje_tabla ENUM('ope','rol') NOT NULL DEFAULT 'ope' COMMENT 'ope = esquema canónico 7 Seas (único valor en uso)',
    staff_level TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-3 (5.12): 0 jugador · 1 colaborador · 2 moderador · 3 admin',
    staff_rol VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'webmaster/moderador/colaborador (legado del plugin)',
    staff_narrador TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'narrador habilitado por personaje (21.2, independiente del staff_level)',
    slots TINYINT UNSIGNED NOT NULL DEFAULT 1,
    datos JSON NULL,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'personajes', "
CREATE TABLE IF NOT EXISTS {$P}personajes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'MyBB user propietario (0 = NPC)',
    nombre VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL DEFAULT '',
    estado ENUM('borrador','revision','aprobado','rechazado') NOT NULL DEFAULT 'borrador',
    estado_vida ENUM('activa','muerta') NOT NULL DEFAULT 'activa',
    es_NPC TINYINT(1) NOT NULL DEFAULT 0,
    tipo_npc ENUM('primario') NULL,
    nivel TINYINT UNSIGNED NOT NULL DEFAULT 1,
    raza_id INT UNSIGNED NULL,
    raza_hibrida_id INT UNSIGNED NULL COMMENT 'NULL si puro',
    tribu_id INT UNSIGNED NULL,
    akuma_id INT UNSIGNED NULL COMMENT '0 o 1 fruta (5.18)',
    faccion_id INT UNSIGNED NULL,
    rango_id INT UNSIGNED NULL,
    fama_global_grado TINYINT UNSIGNED NULL COMMENT '1-8 (5.12)',
    fama_infamia_expo INT NULL,
    rep_faccion INT NOT NULL DEFAULT 0,
    wanted_base BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '→ 5.13',
    ubicacion_isla_id INT UNSIGNED NULL,
    ubicacion_zona_id INT UNSIGNED NULL,
    ubicacion_texto VARCHAR(160) NOT NULL DEFAULT '',
    -- Atributos primarios (escala 1-100, SMALLINT)
    fue SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    des SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    agi SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    res SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    per SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    inte SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Intelecto (reservada: int)',
    car SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    vol SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    -- Progresión (5.6): 10 puntos comprados = nivel
    puntos_comprados SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    reserva SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'puntos sin colocar',
    entrenamiento_fin INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'fin cronómetro atributos',
    entrenamiento_bloque TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '5 o 10',
    pp_saldo INT NOT NULL DEFAULT 0 COMMENT 'saldo de PP (tramo actual)',
    -- Ficha narrativa
    avatar VARCHAR(255) NOT NULL DEFAULT '',
    icono VARCHAR(255) NOT NULL DEFAULT '',
    firma TEXT NULL,
    bio TEXT NULL,
    historia TEXT NULL,
    personalidad TEXT NULL,
    retrato VARCHAR(255) NOT NULL DEFAULT '',
    datos JSON NULL COMMENT 'extensible (bonus raza/dotes/técnicas, estado)',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    lastedit INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug),
    KEY idx_uid (uid),
    KEY idx_estado (estado),
    KEY idx_raza (raza_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'atributos_secundarios', "
CREATE TABLE IF NOT EXISTS {$P}atributos_secundarios (
    personaje_id INT UNSIGNED NOT NULL,
    pv INT NOT NULL DEFAULT 0,
    pe INT NOT NULL DEFAULT 0,
    velocidad DECIMAL(8,2) NOT NULL DEFAULT 0,
    sprint DECIMAL(8,2) NOT NULL DEFAULT 0,
    salto_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    salto_h DECIMAL(8,2) NOT NULL DEFAULT 0,
    carga INT NOT NULL DEFAULT 0,
    resistencia_pasiva DECIMAL(8,2) NOT NULL DEFAULT 0,
    lanzamiento DECIMAL(8,2) NOT NULL DEFAULT 0,
    recuperacion DECIMAL(8,2) NOT NULL DEFAULT 0,
    pa SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '6 + AGI/10 + Nivel/5 (5.10)',
    calculado_en INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.3 — Dominios (bélicos y oficios) + especializaciones
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'dominios', "
CREATE TABLE IF NOT EXISTS {$P}dominios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    tipo ENUM('belico','oficio') NOT NULL,
    atributo_rey ENUM('fue','des','agi','res','per','int','car','vol') NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'dominios_personaje', "
CREATE TABLE IF NOT EXISTS {$P}dominios_personaje (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    dominio_id INT UNSIGNED NOT NULL,
    nivel TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-5',
    rama VARCHAR(80) NULL COMMENT 'obligatoria desde nv3 (especialización)',
    entrenamiento_fin INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'cronómetro 15 días',
    origen ENUM('creacion','compra') NOT NULL DEFAULT 'creacion',
    PRIMARY KEY (id),
    UNIQUE KEY uq_pj_dom (personaje_id, dominio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F4.2-ter — cronómetro de dominios (5.3/4.4): nivel objetivo del entrenamiento
// en curso y multiplicador de coste anclado al dominio (D4.5: ×1,5 el 1.º
// adicional, ×2 el 2.º+, en adquisición y subidas; los de creación ×1,0).
ope7_add_col($db, 'dominios_personaje', 'entrenamiento_nivel', "TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'nivel objetivo del cronómetro (0 = sin entrenamiento)'", 'entrenamiento_fin');
ope7_add_col($db, 'dominios_personaje', 'coste_mult', "DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT 'multiplicador de coste anclado (D4.5)'", 'origen');

ope7_run($db, 'especializaciones', "
CREATE TABLE IF NOT EXISTS {$P}especializaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dominio_id INT UNSIGNED NOT NULL COMMENT 'oficio',
    nombre VARCHAR(80) NOT NULL,
    efectos_n3 JSON NULL,
    efectos_n4 JSON NULL,
    efectos_n5 JSON NULL COMMENT 'Maestría Suprema (título único por rama)',
    PRIMARY KEY (id),
    UNIQUE KEY uq_dom_rama (dominio_id, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.4 — Dotes y defectos
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'dotes', "
CREATE TABLE IF NOT EXISTS {$P}dotes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    efecto JSON NULL,
    puntuacion TINYINT NOT NULL DEFAULT 0 COMMENT '+1..+5',
    tipo ENUM('general','racial') NOT NULL DEFAULT 'general',
    raza_id INT UNSIGNED NULL,
    requiere_raza_pura TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Revisión 10',
    requisitos JSON NULL COMMENT 'atributo/dominio/nivel/raza',
    prerrequisitos JSON NULL COMMENT 'cadenas (Sulong I→II→III)',
    incompatibilidades JSON NULL COMMENT 'parejas espejo',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'defectos', "
CREATE TABLE IF NOT EXISTS {$P}defectos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    efecto JSON NULL,
    puntuacion TINYINT NOT NULL DEFAULT 0 COMMENT '-1..-5',
    requisitos JSON NULL,
    incompatibilidades JSON NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'personaje_dotes', "
CREATE TABLE IF NOT EXISTS {$P}personaje_dotes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    dote_id INT UNSIGNED NULL,
    defecto_id INT UNSIGNED NULL,
    origen ENUM('creacion','narrativo','hito') NOT NULL DEFAULT 'creacion',
    tema_origen_id INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.5 — Rasgos de personalidad (karma)
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'rasgos', "
CREATE TABLE IF NOT EXISTS {$P}rasgos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    tipo ENUM('positivo','negativo') NOT NULL,
    puntuacion TINYINT NOT NULL DEFAULT 0 COMMENT '-3..+3',
    descripcion TEXT NULL,
    pareja_incompatible_id INT UNSIGNED NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'personaje_rasgos', "
CREATE TABLE IF NOT EXISTS {$P}personaje_rasgos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    rasgo_id INT UNSIGNED NOT NULL,
    origen ENUM('creacion','hito') NOT NULL DEFAULT 'creacion',
    karma_acumulado INT NOT NULL DEFAULT 0,
    estado ENUM('activo','arraigado','en_duda','perdido') NOT NULL DEFAULT 'activo',
    contador_contradicciones TINYINT UNSIGNED NOT NULL DEFAULT 0,
    tema_ultima_contradiccion_id INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.6 — Temas (presente/pasado), calendario on-roll, histórico PP
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'temas', "
CREATE TABLE IF NOT EXISTS {$P}temas (
    tid INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'identificador de tema; se vincula al thread MyBB real en F4',
    tipo ENUM('presente','pasado') NOT NULL DEFAULT 'presente',
    fecha_foro VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'ancla on-roll',
    fecha_real_apertura INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('abierto','cerrado','en_cierre') NOT NULL DEFAULT 'abierto',
    invadible TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'presente = invadible',
    zona VARCHAR(120) NOT NULL DEFAULT '',
    tema_tipo ENUM('travesia','aventura','fic','combate','entrenamiento','social','trama') NOT NULL DEFAULT 'social',
    primer_post_fecha TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'muestra la fecha anclada',
    PRIMARY KEY (tid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F1.3 — el registro de tema se crea sin hilo de foro aún (el jugador postea el
// hilo aparte; se vincula el thread MyBB real en F4). Idempotente.
ope7_run($db, 'temas+tid-auto', "
ALTER TABLE {$P}temas MODIFY tid INT UNSIGNED NOT NULL AUTO_INCREMENT;
");

// D1.8 — vincula ope_temas.tid al thread MyBB real: mybb_tid = tid de mybb_threads
// (0 = el hilo aún no se ha posteado/vinculado). Lo rellena el hook de posteo
// (inc/plugins/ope_rol.php → ope_rol_after_thread) y lo lee el cierre de tema
// para cerrar también el hilo real del foro. Idempotente.
ope7_add_col($db, 'temas', 'mybb_tid', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'thread MyBB real vinculado (D1.8): 0 = sin hilo aún'", 'tid');

ope7_run($db, 'temas_participantes', "
CREATE TABLE IF NOT EXISTS {$P}temas_participantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tema_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    congelado_desde VARCHAR(60) NOT NULL DEFAULT '',
    salio_en INT UNSIGNED NULL,
    tramo TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'I..V (1-5)',
    ficha_instantanea JSON NULL COMMENT 'atributos/técnicas/estados al abrir (5.6)',
    PRIMARY KEY (id),
    KEY idx_tema (tema_id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'calendario_foro', "
CREATE TABLE IF NOT EXISTS {$P}calendario_foro (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha_foro_actual VARCHAR(60) NOT NULL DEFAULT '',
    ratio DECIMAL(4,2) NOT NULL DEFAULT 2.00 COMMENT '1 real = 2 on-roll',
    ultima_actualizacion_real INT UNSIGNED NOT NULL DEFAULT 0,
    avances JSON NULL COMMENT 'histórico de avances',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'historico_pp', "
CREATE TABLE IF NOT EXISTS {$P}historico_pp (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    tema_id INT UNSIGNED NULL,
    tramo TINYINT UNSIGNED NOT NULL DEFAULT 1,
    base_pp INT NOT NULL DEFAULT 0,
    factores JSON NULL COMMENT '7 factores de skill-cierre-temas',
    pp_otorgado INT NOT NULL DEFAULT 0,
    cantidad INT NOT NULL DEFAULT 0 COMMENT 'gasto (negativo) o ingreso (positivo) — F2.1',
    concepto VARCHAR(120) NOT NULL DEFAULT '',
    tramite_id INT UNSIGNED NULL,
    firmado_por INT UNSIGNED NULL,
    motivo TEXT NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F2.1 — historico_pp como libro general de PP (gastos y cierres). Idempotente.
ope7_add_col($db, 'historico_pp', 'cantidad', "INT NOT NULL DEFAULT 0 COMMENT 'gasto (negativo) o ingreso (positivo) — F2.1'", 'pp_otorgado');
ope7_add_col($db, 'historico_pp', 'concepto', "VARCHAR(120) NOT NULL DEFAULT ''", 'cantidad');
ope7_add_col($db, 'historico_pp', 'tramite_id', 'INT UNSIGNED NULL', 'concepto');

// ─────────────────────────────────────────────────────────────
// 5.7 — Técnicas y catálogo de efectos
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'catalogo_efectos', "
CREATE TABLE IF NOT EXISTS {$P}catalogo_efectos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    puerta_tier TINYINT UNSIGNED NOT NULL COMMENT 'T1..T5',
    slots TINYINT UNSIGNED NOT NULL DEFAULT 1,
    tipo_tecnica_permitido JSON NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'tecnicas', "
CREATE TABLE IF NOT EXISTS {$P}tecnicas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    tier TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-5',
    tipo ENUM('ofensiva','defensiva','apoyo','mixta') NOT NULL DEFAULT 'ofensiva',
    dominio_id INT UNSIGNED NULL,
    requisitos JSON NULL COMMENT 'atributos escalados, duales duplicados',
    efectos JSON NULL COMMENT 'multiplicador + efectos del catálogo',
    coste_pp INT UNSIGNED NOT NULL DEFAULT 0,
    pa TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '2 + tier',
    pe_pct TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT '% del máx',
    reposo TINYINT UNSIGNED NOT NULL DEFAULT 1,
    puerta_turno TINYINT UNSIGNED NOT NULL DEFAULT 0,
    origen ENUM('creacion','upgrade') NOT NULL DEFAULT 'creacion',
    nota_moderacion TEXT NULL COMMENT 'criterio de originalidad aplicado',
    activa TINYINT(1) NOT NULL DEFAULT 1,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.8 — Objetos, inventario, almacén y Meitou
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'objetos', "
CREATE TABLE IF NOT EXISTS {$P}objetos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    categoria ENUM('arma','armadura','escudo','consumible','herramienta','dial','material','municion') NOT NULL DEFAULT 'arma',
    calidad ENUM('inferior','comun','superior','wazamono','ryo','o','saijo') NULL,
    rareza ENUM('comun','poco_comun','raro','mercado_negro') NULL,
    grado TINYINT UNSIGNED NULL COMMENT 'veneno/estado I-III',
    efecto_json JSON NULL COMMENT 'daño, reducción, estado, cura %/PE %, condición',
    coste_pa VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'número o fórmula',
    cantidad_disponible INT NOT NULL DEFAULT 0,
    reutilizable TINYINT(1) NOT NULL DEFAULT 0,
    precio_base INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ancla 5.9',
    cupo_mundial TINYINT UNSIGNED NULL COMMENT '50/21/12',
    dureza TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ranuras TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-3',
    notas TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'inventario_personaje', "
CREATE TABLE IF NOT EXISTS {$P}inventario_personaje (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    objeto_id INT UNSIGNED NOT NULL,
    zona ENUM('arma1','arma2','armadura','escudo','cinturon','mochila','equipado') NOT NULL DEFAULT 'mochila',
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    integridad INT NULL,
    vinculado VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'nombre propio de Meitou',
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'almacen', "
CREATE TABLE IF NOT EXISTS {$P}almacen (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    objeto_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pj_obj (personaje_id, objeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'arma_meito', "
CREATE TABLE IF NOT EXISTS {$P}arma_meito (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    objeto_id INT UNSIGNED NOT NULL,
    nombre_propio VARCHAR(120) NOT NULL,
    portador_id INT UNSIGNED NULL,
    pasiva JSON NULL COMMENT 'Ryo 1 slot · O 2 · Saijo 3 (+ rotura de regla)',
    maldita TINYINT(1) NOT NULL DEFAULT 0,
    domada TINYINT(1) NOT NULL DEFAULT 0,
    penalizador JSON NULL,
    cupo ENUM('ryo','o','saijo') NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_objeto (objeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.9 — Economía: config, mercado, carteras, tiendas, transacciones
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'economia_config', "
CREATE TABLE IF NOT EXISTS {$P}economia_config (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    moneda VARCHAR(20) NOT NULL DEFAULT 'berries',
    banda_min DECIMAL(4,2) NOT NULL DEFAULT 0.50 COMMENT '0,5x',
    banda_max DECIMAL(4,2) NOT NULL DEFAULT 2.00 COMMENT '2x',
    margen_min DECIMAL(4,2) NOT NULL DEFAULT -0.20 COMMENT '-20%',
    margen_max DECIMAL(4,2) NOT NULL DEFAULT 0.30 COMMENT '+30%',
    stock_items TINYINT UNSIGNED NOT NULL DEFAULT 10,
    stock_consumibles TINYINT UNSIGNED NOT NULL DEFAULT 10,
    stock_armas TINYINT UNSIGNED NOT NULL DEFAULT 3,
    redondeo VARCHAR(20) NOT NULL DEFAULT 'decenas',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'precios_mercado', "
CREATE TABLE IF NOT EXISTS {$P}precios_mercado (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    zona_id INT UNSIGNED NOT NULL,
    objeto_id INT UNSIGNED NOT NULL,
    precio_actual INT UNSIGNED NOT NULL DEFAULT 0,
    factores JSON NULL COMMENT 'oferta/demanda/suceso',
    motivo TEXT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_foro VARCHAR(60) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_zona (zona_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'carteras', "
CREATE TABLE IF NOT EXISTS {$P}carteras (
    personaje_id INT UNSIGNED NOT NULL,
    cartera BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'robable',
    boveda BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'segura',
    PRIMARY KEY (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'cofre_tripulacion', "
CREATE TABLE IF NOT EXISTS {$P}cofre_tripulacion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tripulacion_id INT UNSIGNED NULL COMMENT 'se enlaza al crear la tripulación (5.21-ter)',
    berries BIGINT UNSIGNED NOT NULL DEFAULT 0,
    log JSON NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'tiendas', "
CREATE TABLE IF NOT EXISTS {$P}tiendas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dueno_id INT UNSIGNED NOT NULL,
    zona_id INT UNSIGNED NULL,
    tipo ENUM('oficio','reventa') NOT NULL DEFAULT 'oficio',
    local VARCHAR(160) NOT NULL DEFAULT '' COMMENT 'ref. 5.15/5.17 o narrativo',
    estado ENUM('activa','suspendida','cerrada') NOT NULL DEFAULT 'activa',
    capital BIGINT UNSIGNED NOT NULL DEFAULT 0,
    banda_margen VARCHAR(40) NOT NULL DEFAULT '',
    notas TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_dueno (dueno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'tienda_items', "
CREATE TABLE IF NOT EXISTS {$P}tienda_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tienda_id INT UNSIGNED NOT NULL,
    objeto_id INT UNSIGNED NOT NULL,
    precio_venta INT UNSIGNED NOT NULL DEFAULT 0,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    clasificacion ENUM('normal','belico') NOT NULL DEFAULT 'normal',
    origen ENUM('produccion','compra') NOT NULL DEFAULT 'compra',
    PRIMARY KEY (id),
    KEY idx_tienda (tienda_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'transacciones', "
CREATE TABLE IF NOT EXISTS {$P}transacciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    zona_id INT UNSIGNED NULL,
    vendedor_id INT UNSIGNED NOT NULL DEFAULT 0,
    comprador_id INT UNSIGNED NOT NULL DEFAULT 0,
    tipo_contraparte ENUM('jugador','npc') NOT NULL DEFAULT 'npc',
    objeto_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario INT UNSIGNED NOT NULL DEFAULT 0,
    tienda_id INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_vendedor (vendedor_id),
    KEY idx_comprador (comprador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.10 — Combate: estados, resoluciones, matices, acciones PA, turnos, sala
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'estados', "
CREATE TABLE IF NOT EXISTS {$P}estados (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    grado TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'I-III',
    categoria ENUM('fisico','mental','veneno','control','positivo') NOT NULL DEFAULT 'fisico',
    efecto JSON NULL,
    duracion VARCHAR(40) NOT NULL DEFAULT '',
    sacudida TEXT NULL,
    anti_spam TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 vez/combate/técnica',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'estados_activos', "
CREATE TABLE IF NOT EXISTS {$P}estados_activos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    combate_id INT UNSIGNED NOT NULL DEFAULT 0,
    tema_id INT UNSIGNED NOT NULL DEFAULT 0,
    personaje_id INT UNSIGNED NOT NULL,
    estado_id INT UNSIGNED NOT NULL,
    grado TINYINT UNSIGNED NOT NULL DEFAULT 1,
    valor_aplicado VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'para sacudidas',
    turnos_restantes TINYINT NOT NULL DEFAULT 0,
    origen VARCHAR(60) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'resoluciones_combate', "
CREATE TABLE IF NOT EXISTS {$P}resoluciones_combate (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    combate_id INT UNSIGNED NOT NULL,
    turno TINYINT UNSIGNED NOT NULL DEFAULT 0,
    atacante_id INT UNSIGNED NOT NULL,
    defensor_id INT UNSIGNED NOT NULL,
    tabla TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-4 + umbral',
    delta SMALLINT NOT NULL DEFAULT 0,
    banda VARCHAR(30) NOT NULL DEFAULT '',
    resultado VARCHAR(60) NOT NULL DEFAULT '',
    veredicto_narrativo TEXT NULL,
    matices JSON NULL,
    PRIMARY KEY (id),
    KEY idx_combate (combate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'matices_combate', "
CREATE TABLE IF NOT EXISTS {$P}matices_combate (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    efecto JSON NULL,
    tabla TINYINT UNSIGNED NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'acciones_pa', "
CREATE TABLE IF NOT EXISTS {$P}acciones_pa (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    categoria ENUM('movimiento','ataque','defensa','objeto','mente','gratuita') NOT NULL DEFAULT 'ataque',
    coste_pa VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'número o fórmula JSON',
    regla JSON NULL,
    notas TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'turnos_combate', "
CREATE TABLE IF NOT EXISTS {$P}turnos_combate (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    combate_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    turno TINYINT UNSIGNED NOT NULL DEFAULT 0,
    pa_total SMALLINT NOT NULL DEFAULT 0,
    pa_gastado SMALLINT NOT NULL DEFAULT 0,
    acciones JSON NULL,
    reserva SMALLINT NOT NULL DEFAULT 0,
    veredicto JSON NULL,
    PRIMARY KEY (id),
    KEY idx_combate (combate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'sala_combate', "
CREATE TABLE IF NOT EXISTS {$P}sala_combate (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tema_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL DEFAULT '',
    tipo ENUM('duelo','grupo','solitario','naval') NOT NULL DEFAULT 'duelo',
    estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
    max_combatientes TINYINT UNSIGNED NOT NULL DEFAULT 5,
    creado_por INT UNSIGNED NOT NULL DEFAULT 0,
    resuelto_por INT UNSIGNED NULL COMMENT 'staff que firma el veredicto (F2.3)',
    resuelto_fecha INT UNSIGNED NULL,
    nota_resolucion TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_tema (tema_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F2.3 — firma del veredicto de combate en la sala (idempotente).
ope7_add_col($db, 'sala_combate', 'resuelto_por', 'INT UNSIGNED NULL COMMENT "staff que firma el veredicto (F2.3)"', 'creado_por');
ope7_add_col($db, 'sala_combate', 'resuelto_fecha', 'INT UNSIGNED NULL', 'resuelto_por');
ope7_add_col($db, 'sala_combate', 'nota_resolucion', 'TEXT NULL', 'resuelto_fecha');

// ─────────────────────────────────────────────────────────────
// 5.11 — NPCs: capa oculta del primario, bestiario, apariciones
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'npc_primario', "
CREATE TABLE IF NOT EXISTS {$P}npc_primario (
    personaje_id INT UNSIGNED NOT NULL,
    personalidad TEXT NULL,
    triggers JSON NULL,
    intenciones_ocultas TEXT NULL,
    historia_completa TEXT NULL,
    decisiones JSON NULL,
    vinculos_mundo_vivo JSON NULL,
    notas_moderacion TEXT NULL,
    PRIMARY KEY (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'bestiario', "
CREATE TABLE IF NOT EXISTS {$P}bestiario (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    tipo ENUM('secundario','terciario') NOT NULL DEFAULT 'terciario',
    origen_faccion INT UNSIGNED NULL,
    zona VARCHAR(120) NOT NULL DEFAULT '',
    nivel TINYINT UNSIGNED NOT NULL DEFAULT 1,
    atributos JSON NULL COMMENT '8 valores efectivos',
    pv_max INT NOT NULL DEFAULT 0,
    pe_max INT NOT NULL DEFAULT 0,
    pa SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    acciones JSON NULL COMMENT 'coste PA + técnicas 5.7 referenciadas',
    defensas_usa JSON NULL,
    pseudo_personalidad JSON NULL COMMENT 'fases si jefe',
    nota_narrativa TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'npc_apariciones', "
CREATE TABLE IF NOT EXISTS {$P}npc_apariciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bestiario_id INT UNSIGNED NOT NULL,
    tema_id INT UNSIGNED NOT NULL,
    pv_actual INT NOT NULL DEFAULT 0,
    pe_actual INT NOT NULL DEFAULT 0,
    estados JSON NULL,
    manejado_por INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('activo','derrotado','retirado','reclutado') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (id),
    KEY idx_tema (tema_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.12 — Facciones, rangos, fama, subfacciones élite, sueldos
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'facciones', "
CREATE TABLE IF NOT EXISTS {$P}facciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    familia ENUM('pirata','institucional','criminal','civil','libre') NOT NULL DEFAULT 'libre',
    rango_inicial INT UNSIGNED NULL,
    tiene_sueldo TINYINT(1) NOT NULL DEFAULT 0,
    coeficientes_mv JSON NULL COMMENT 'peso por familia de acción',
    cupo_max INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'rangos_faccion', "
CREATE TABLE IF NOT EXISTS {$P}rangos_faccion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    faccion_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 1,
    requisitos JSON NULL,
    beneficios JSON NULL,
    cupo INT UNSIGNED NULL,
    es_cuspide TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_faccion (faccion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'faccion_personaje', "
CREATE TABLE IF NOT EXISTS {$P}faccion_personaje (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    faccion_id INT UNSIGNED NOT NULL,
    rango_id INT UNSIGNED NULL,
    fama_global_grado TINYINT UNSIGNED NULL,
    fama_infamia_expo INT NULL,
    rep_faccion INT NOT NULL DEFAULT 0,
    wanted_base BIGINT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F4.3 facciones — infiltración (13.7/13.8): capa oculta solo-staff.
// La lealtad VISIBLE es la falsa; la REAL queda guardada aquí hasta que
// la infiltración termina (se restaura) o se descubre.
ope7_add_col($db, 'faccion_personaje', 'infiltracion_faccion_id', "INT UNSIGNED NULL COMMENT 'lealtad real oculta (infiltración, solo-staff)'", 'rango_id');
ope7_add_col($db, 'faccion_personaje', 'infiltracion_rango_id', "INT UNSIGNED NULL COMMENT 'rango real oculto'", 'infiltracion_faccion_id');
ope7_add_col($db, 'faccion_personaje', 'infiltracion_activa', "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'infiltración en curso'", 'infiltracion_rango_id');

ope7_run($db, 'subfaccion_elite', "
CREATE TABLE IF NOT EXISTS {$P}subfaccion_elite (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL COMMENT 'Gorosei/Shichibukai',
    personaje_id INT UNSIGNED NOT NULL,
    concedida_por INT UNSIGNED NOT NULL DEFAULT 0,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'cambios_faccion', "
CREATE TABLE IF NOT EXISTS {$P}cambios_faccion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    tipo ENUM('alta','promocion','desercion','infiltracion','baja','concesion','revocacion') NOT NULL DEFAULT 'alta',
    desde_faccion_id INT UNSIGNED NULL,
    hasta_faccion_id INT UNSIGNED NULL,
    motivo TEXT NOT NULL,
    firmado_por INT UNSIGNED NOT NULL DEFAULT 0,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'sueldos', "
CREATE TABLE IF NOT EXISTS {$P}sueldos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    posts_del_mes INT UNSIGNED NOT NULL DEFAULT 0,
    monto BIGINT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('pendiente','pagado','sin_paga') NOT NULL DEFAULT 'pendiente',
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'npc_faccion', "
CREATE TABLE IF NOT EXISTS {$P}npc_faccion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    faccion_id INT UNSIGNED NOT NULL,
    rango_id INT UNSIGNED NULL,
    nombre VARCHAR(120) NOT NULL,
    tipo ENUM('escuadra','recurso','mentor') NOT NULL DEFAULT 'recurso',
    ficha JSON NULL,
    PRIMARY KEY (id),
    KEY idx_faccion (faccion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.13 — Bajo mundo: rumores, fuentes, red, operaciones, carteles
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'rumores', "
CREATE TABLE IF NOT EXISTS {$P}rumores (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NULL,
    tipo ENUM('suceso','tesoro','persona','faccion') NOT NULL DEFAULT 'suceso',
    contenido TEXT NOT NULL,
    veracidad ENUM('verdadero','dudoso','falso') NOT NULL DEFAULT 'dudoso' COMMENT 'solo-staff',
    fiabilidad ENUM('rumoroso','plausible','solido') NOT NULL DEFAULT 'rumoroso',
    alcance ENUM('local','regional','mundial') NOT NULL DEFAULT 'local',
    frescura ENUM('fresco','familiar','frio') NOT NULL DEFAULT 'fresco',
    ronda_origen INT UNSIGNED NOT NULL DEFAULT 0,
    creador_id INT UNSIGNED NOT NULL DEFAULT 0,
    precio_base INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('activo','contrastado','vendido','frio','retirado') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (id),
    KEY idx_isla (isla_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'fuentes_informacion', "
CREATE TABLE IF NOT EXISTS {$P}fuentes_informacion (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NOT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    nombre VARCHAR(120) NOT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT '',
    precio INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'red_espionaje', "
CREATE TABLE IF NOT EXISTS {$P}red_espionaje (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dueno_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL DEFAULT '',
    estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_dueno (dueno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'espias', "
CREATE TABLE IF NOT EXISTS {$P}espias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    red_id INT UNSIGNED NOT NULL,
    espia_id INT UNSIGNED NOT NULL DEFAULT 0,
    tipo ENUM('novato','avanzado','experimentado','supremo') NOT NULL DEFAULT 'novato',
    capacidad JSON NULL,
    coste INT UNSIGNED NOT NULL DEFAULT 0,
    mantenimiento INT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('activo','descubierto','retirado') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (id),
    KEY idx_red (red_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'rumor_operaciones', "
CREATE TABLE IF NOT EXISTS {$P}rumor_operaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rumor_id INT UNSIGNED NOT NULL,
    tipo ENUM('compra','venta','contraste','propagacion') NOT NULL DEFAULT 'compra',
    solicitante_id INT UNSIGNED NOT NULL DEFAULT 0,
    cobro INT UNSIGNED NOT NULL DEFAULT 0,
    motivo TEXT NULL,
    veredicto JSON NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_rumor (rumor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'carteles_recompensa', "
CREATE TABLE IF NOT EXISTS {$P}carteles_recompensa (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    cifra BIGINT UNSIGNED NOT NULL DEFAULT 0,
    paradero_publicado VARCHAR(160) NOT NULL DEFAULT '',
    fiabilidad_paradero ENUM('rumoroso','plausible','solido') NOT NULL DEFAULT 'plausible',
    estado ENUM('vigente','cobrado','retirado','frio') NOT NULL DEFAULT 'vigente',
    ronda_emision INT UNSIGNED NOT NULL DEFAULT 0,
    ronda_caducidad_paradero INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '3 rondas',
    emitido_por INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'rumor_isla_ronda', "
CREATE TABLE IF NOT EXISTS {$P}rumor_isla_ronda (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NOT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    contenido TEXT NULL,
    salida JSON NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.14 — Mundo Vivo: mares, islas, estado/histórico, rondas, matriz…
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'mares', "
CREATE TABLE IF NOT EXISTS {$P}mares (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
    region VARCHAR(60) NOT NULL DEFAULT '',
    peligrosidad_base TINYINT UNSIGNED NOT NULL DEFAULT 1,
    irt_base TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Blue 1 · Paraíso 2 · NM 3 · ZR 4 (5.16)',
    descripcion TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'islas', "
CREATE TABLE IF NOT EXISTS {$P}islas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mar_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    es_canon TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'nombre + ADN canon (principio 7)',
    descripcion TEXT NULL,
    modo_viaje VARCHAR(40) NOT NULL DEFAULT 'normal' COMMENT 'normal/skypiea/burbuja/…',
    utensilio_requerido VARCHAR(80) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'isla_estado', "
CREATE TABLE IF NOT EXISTS {$P}isla_estado (
    isla_id INT UNSIGNED NOT NULL,
    peligrosidad TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-50',
    afiliacion ENUM('local','gobierno','salvaje','mixta') NOT NULL DEFAULT 'local',
    fuerza_defensiva_nivel TINYINT UNSIGNED NOT NULL DEFAULT 1,
    quien_manda VARCHAR(120) NOT NULL DEFAULT '',
    guarnicion JSON NULL,
    fortificaciones JSON NULL,
    desarrollo VARCHAR(40) NOT NULL DEFAULT 'Aldea',
    poblacion_orden JSON NULL,
    recursos JSON NULL,
    oferta_demanda JSON NULL,
    clima_logpose VARCHAR(120) NOT NULL DEFAULT '',
    lugares_clave JSON NULL,
    sucesos JSON NULL,
    hitos JSON NULL,
    recompensas_tesoros JSON NULL,
    presencia_facciones JSON NULL,
    updated INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (isla_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'isla_estado_historico', "
CREATE TABLE IF NOT EXISTS {$P}isla_estado_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NOT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    campo VARCHAR(60) NOT NULL,
    de_valor VARCHAR(120) NULL,
    a_valor VARCHAR(120) NULL,
    motivo TEXT NULL,
    fuente ENUM('mision','tramite','conquista','suceso','arranque','ronda') NOT NULL DEFAULT 'ronda',
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_isla (isla_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'rondas', "
CREATE TABLE IF NOT EXISTS {$P}rondas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero INT UNSIGNED NOT NULL,
    inicio INT UNSIGNED NOT NULL DEFAULT 0,
    fin INT UNSIGNED NULL,
    estado ENUM('abierta','analisis','cerrada') NOT NULL DEFAULT 'abierta',
    dashboard JSON NULL,
    publicado_por INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'matriz_peso', "
CREATE TABLE IF NOT EXISTS {$P}matriz_peso (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    accion_id INT UNSIGNED NOT NULL DEFAULT 0,
    personaje_id INT UNSIGNED NOT NULL DEFAULT 0,
    ejes JSON NULL COMMENT 'nivel, mar, facción, escala, signo',
    peso DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT '0,5x-3x (interno)',
    motivo TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_ronda (ronda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'dashboard_acciones', "
CREATE TABLE IF NOT EXISTS {$P}dashboard_acciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    tema_id INT UNSIGNED NOT NULL DEFAULT 0,
    personaje_id INT UNSIGNED NOT NULL DEFAULT 0,
    accion TEXT NULL,
    categoria VARCHAR(40) NOT NULL DEFAULT '',
    peso DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    PRIMARY KEY (id),
    KEY idx_ronda (ronda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'recompensas_historico', "
CREATE TABLE IF NOT EXISTS {$P}recompensas_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    tipo ENUM('subida','bajada','cartel','mision','suceso') NOT NULL DEFAULT 'suceso',
    cantidad BIGINT NOT NULL DEFAULT 0,
    motivo TEXT NULL,
    firmado_por INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'sucesos', "
CREATE TABLE IF NOT EXISTS {$P}sucesos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    tipo VARCHAR(60) NOT NULL DEFAULT '',
    titulo VARCHAR(160) NOT NULL DEFAULT '',
    descripcion TEXT NULL,
    impacto JSON NULL COMMENT 'alimenta F_suceso de 5.9',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'historico_periodicos', "
CREATE TABLE IF NOT EXISTS {$P}historico_periodicos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    numero_edicion INT UNSIGNED NOT NULL DEFAULT 1,
    titulo VARCHAR(160) NOT NULL DEFAULT 'News Coo',
    html MEDIUMTEXT NULL,
    estado ENUM('borrador','publicado') NOT NULL DEFAULT 'borrador',
    publicado_por INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.15 — Conquista: conquistas, asedios, unidades, hordas, zonas
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'conquistas', "
CREATE TABLE IF NOT EXISTS {$P}conquistas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NOT NULL,
    zona_id INT UNSIGNED NULL,
    atacante_id INT UNSIGNED NOT NULL DEFAULT 0,
    bando_atacante VARCHAR(80) NOT NULL DEFAULT '',
    defensor_id INT UNSIGNED NULL,
    tipo ENUM('isla','zona') NOT NULL DEFAULT 'isla',
    fase ENUM('anuncio','asedio','resolucion','registro','ocupacion') NOT NULL DEFAULT 'anuncio',
    ronda_inicio INT UNSIGNED NOT NULL DEFAULT 0,
    rondas_asedio TINYINT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('activa','ganada','perdida','abandonada','tregua') NOT NULL DEFAULT 'activa',
    ganador_id INT UNSIGNED NULL COMMENT 'quien manda tras la resolucion',
    motivo TEXT NULL COMMENT 'motivo del registro (16.8, fuente conquista)',
    resuelta_ronda INT UNSIGNED NOT NULL DEFAULT 0,
    ultima_actividad_ronda INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '16.5: abandono por 2 rondas sin actividad',
    PRIMARY KEY (id),
    KEY idx_isla (isla_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'asedios', "
CREATE TABLE IF NOT EXISTS {$P}asedios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    conquista_id INT UNSIGNED NOT NULL,
    ronda INT UNSIGNED NOT NULL DEFAULT 0,
    acciones JSON NULL,
    desgaste JSON NULL,
    veredictos JSON NULL,
    PRIMARY KEY (id),
    KEY idx_conquista (conquista_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'unidades', "
CREATE TABLE IF NOT EXISTS {$P}unidades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('infanteria','elite','especialista') NOT NULL DEFAULT 'infanteria',
    coste INT UNSIGNED NOT NULL DEFAULT 0,
    mantenimiento INT UNSIGNED NOT NULL DEFAULT 0,
    capacidad JSON NULL,
    dueno_id INT UNSIGNED NOT NULL DEFAULT 0,
    isla_id INT UNSIGNED NOT NULL DEFAULT 0,
    conquista_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '16.7: unidades vinculadas a la conquista',
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('activa','retirada') NOT NULL DEFAULT 'activa',
    PRIMARY KEY (id),
    KEY idx_conquista (conquista_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'hordas', "
CREATE TABLE IF NOT EXISTS {$P}hordas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NOT NULL,
    conquista_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '16.7: horda vinculada a la conquista',
    contratada_por INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = la genera el Mundo Vivo',
    tamaño ENUM('mara','masa','marea') NOT NULL DEFAULT 'mara',
    fuerza TINYINT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('activa','disuelta') NOT NULL DEFAULT 'activa',
    veredicto_ronda JSON NULL,
    PRIMARY KEY (id),
    KEY idx_conquista (conquista_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'zonas', "
CREATE TABLE IF NOT EXISTS {$P}zonas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    isla_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    afiliacion ENUM('local','gobierno','salvaje','mixta') NOT NULL DEFAULT 'local',
    recursos JSON NULL,
    fuerza_defensiva JSON NULL,
    PRIMARY KEY (id),
    KEY idx_isla (isla_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Columnas de conquista añadidas después del CREATE inicial (idempotente).
ope7_add_col($db, 'conquistas', 'ganador_id', 'INT UNSIGNED NULL COMMENT "quien manda tras la resolucion"', 'defensor_id');
ope7_add_col($db, 'conquistas', 'motivo', "TEXT NULL COMMENT 'motivo del registro (16.8, fuente conquista)'", 'ganador_id');
ope7_add_col($db, 'conquistas', 'resuelta_ronda', 'INT UNSIGNED NOT NULL DEFAULT 0', 'rondas_asedio');
ope7_add_col($db, 'conquistas', 'ultima_actividad_ronda', 'INT UNSIGNED NOT NULL DEFAULT 0 COMMENT "16.5: abandono por 2 rondas sin actividad"', 'rondas_asedio');
ope7_add_col($db, 'unidades', 'conquista_id', 'INT UNSIGNED NOT NULL DEFAULT 0 COMMENT "16.7: unidades vinculadas a la conquista"', 'isla_id');
ope7_add_col($db, 'unidades', 'bando', "ENUM('atacante','defensor') NOT NULL DEFAULT 'atacante' COMMENT '16.7: bando que contrata la unidad'", 'conquista_id');
// Trámite 44 (venta/desguace/baja, 18.7): el barco vendido sale de flota —
// amplía el ENUM de `barcos.estado` (idempotente: solo si aún no incluye).
$q = $db->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mybb_ope_barcos' AND COLUMN_NAME = 'estado'");
$r = $q ? $q->fetch_assoc() : null;
if ($r && strpos((string) ($r['COLUMN_TYPE'] ?? ''), 'vendido') === false) {
    $db->query("ALTER TABLE mybb_ope_barcos MODIFY COLUMN `estado` ENUM('activo','danado_leve','danado_moderado','danado_grave','hundido','en_reparacion','vendido') NOT NULL DEFAULT 'activo'");
    echo "  [OK] barcos.estado ampliado con 'vendido' (trámite 44)\n";
} else {
    echo "  [ok ] barcos.estado ya incluye 'vendido'\n";
}
ope7_add_col($db, 'hordas', 'conquista_id', 'INT UNSIGNED NOT NULL DEFAULT 0 COMMENT "16.7: horda vinculada a la conquista"', 'isla_id');
ope7_add_col($db, 'hordas', 'contratada_por', 'INT UNSIGNED NOT NULL DEFAULT 0 COMMENT "0 = la genera el Mundo Vivo"', 'conquista_id');

// ─────────────────────────────────────────────────────────────
// 5.18/5.19 — Akumas y Haki (F5)
// ─────────────────────────────────────────────────────────────
// La fruta en el inventario es un objeto de tamaño (19.7, mediano 1 ranura):
// amplía el ENUM de `objetos.categoria` con 'akuma' (idempotente).
$q = $db->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mybb_ope_objetos' AND COLUMN_NAME = 'categoria'");
$r = $q ? $q->fetch_assoc() : null;
if ($r && strpos((string) ($r['COLUMN_TYPE'] ?? ''), 'akuma') === false) {
    $db->query("ALTER TABLE mybb_ope_objetos MODIFY COLUMN `categoria` ENUM('arma','armadura','escudo','consumible','herramienta','dial','material','municion','akuma') NOT NULL DEFAULT 'arma'");
    echo "  [OK] objetos.categoria ampliado con 'akuma' (5.18)\n";
} else {
    echo "  [ok ] objetos.categoria ya incluye 'akuma'\n";
}
// Afinidad natural de la tirada (19.7: −10 % PE en las técnicas de la fruta).
ope7_add_col($db, 'personajes', 'akuma_afinidad', "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'afinidad natural de la tirada aleatoria (−10 % PE, 5.18)'", 'akuma_id');

// ─────────────────────────────────────────────────────────────
// 5.16 — Navegación: travesías, oráculos, incidentes, transportes
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'travesias', "
CREATE TABLE IF NOT EXISTS {$P}travesias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tema_id INT UNSIGNED NULL COMMENT 'tema presente (5.6)',
    origen_isla_id INT UNSIGNED NOT NULL,
    destino_isla_id INT UNSIGNED NOT NULL,
    ruta JSON NULL COMMENT 'mares',
    barco_id INT UNSIGNED NULL,
    transporte_tipo VARCHAR(40) NULL,
    utensilio_id INT UNSIGNED NULL,
    tripulacion JSON NULL,
    irt TINYINT NOT NULL DEFAULT 0 COMMENT 'interno, solo-staff',
    oraculos JSON NULL,
    tiempo_disponible_h INT UNSIGNED NOT NULL DEFAULT 0,
    tiempo_on_roll VARCHAR(60) NOT NULL DEFAULT '',
    viveres_gastados INT NOT NULL DEFAULT 0,
    estado ENUM('planificada','en_travesia','resuelta','abortada','vencida') NOT NULL DEFAULT 'planificada',
    veredicto JSON NULL,
    PRIMARY KEY (id),
    KEY idx_tema (tema_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'oraculos_catalogo', "
CREATE TABLE IF NOT EXISTS {$P}oraculos_catalogo (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo VARCHAR(60) NOT NULL COMMENT 'Ala de tormenta/Asalto/Patrulla/Coloso/Maremoto/Remolino/Huracán',
    gravedad ENUM('menor','media','grave') NOT NULL DEFAULT 'menor',
    efectos JSON NULL COMMENT 'daño barco grado, víveres, desvío, encuentro',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'incidentes_travesia', "
CREATE TABLE IF NOT EXISTS {$P}incidentes_travesia (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    travesia_id INT UNSIGNED NOT NULL,
    oraculo_id INT UNSIGNED NULL,
    post_id INT UNSIGNED NULL,
    momento VARCHAR(60) NOT NULL DEFAULT '',
    resolucion JSON NULL,
    PRIMARY KEY (id),
    KEY idx_travesia (travesia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'transportes', "
CREATE TABLE IF NOT EXISTS {$P}transportes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo ENUM('civil','clandestino','gobierno') NOT NULL DEFAULT 'civil',
    tarifa JSON NULL COMMENT 'por mar (Blue/Paraíso/NM/ZR)',
    reglas_acceso JSON NULL COMMENT 'afiliación 5.12, recargo Wanted',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.17 — Barcos: ficha, tipos, maderas, módulos, reparaciones
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'tipos_barcos', "
CREATE TABLE IF NOT EXISTS {$P}tipos_barcos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    plazas JSON NULL,
    casco JSON NULL COMMENT 'por N1-N3',
    maniobra JSON NULL,
    ranuras JSON NULL,
    canones JSON NULL,
    mitigador_irt TINYINT NOT NULL DEFAULT 0 COMMENT '0 a -3',
    precio BIGINT UNSIGNED NOT NULL DEFAULT 0,
    madera_minima VARCHAR(40) NOT NULL DEFAULT '',
    es_faccion_npc TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'acorazado solo facciones/NPC',
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'maderas_casco', "
CREATE TABLE IF NOT EXISTS {$P}maderas_casco (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    mares JSON NULL COMMENT 'mares que habilita',
    precio BIGINT UNSIGNED NOT NULL DEFAULT 0,
    rareza ENUM('comun','poco_comun','raro','mercado_negro') NOT NULL DEFAULT 'comun',
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'modulos_barcos', "
CREATE TABLE IF NOT EXISTS {$P}modulos_barcos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    efecto JSON NULL,
    ranura TINYINT UNSIGNED NOT NULL DEFAULT 1,
    precio INT UNSIGNED NOT NULL DEFAULT 0,
    requisito_oficio VARCHAR(80) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'barcos', "
CREATE TABLE IF NOT EXISTS {$P}barcos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    tipo_id INT UNSIGNED NOT NULL,
    nivel ENUM('N1','N2','N3') NOT NULL DEFAULT 'N1',
    madera_id INT UNSIGNED NOT NULL,
    casco_pv INT UNSIGNED NOT NULL DEFAULT 0,
    pv_actual INT UNSIGNED NOT NULL DEFAULT 0,
    maniobra SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    armamento JSON NULL COMMENT 'cañones',
    espacio_max TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ranuras JSON NULL COMMENT 'módulos instalados',
    dueno_id INT UNSIGNED NOT NULL DEFAULT 0,
    tripulacion_id INT UNSIGNED NULL,
    estado ENUM('activo','danado_leve','danado_moderado','danado_grave','hundido','en_reparacion') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (id),
    KEY idx_dueno (dueno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'reparaciones', "
CREATE TABLE IF NOT EXISTS {$P}reparaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    barco_id INT UNSIGNED NOT NULL,
    grado ENUM('leve','moderado','grave') NOT NULL DEFAULT 'leve',
    materiales JSON NULL,
    coste INT UNSIGNED NOT NULL DEFAULT 0,
    oficio VARCHAR(60) NOT NULL DEFAULT '',
    veredicto JSON NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_barco (barco_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.18 — Akuma no Mi: catálogo, pool de tirada, despertares, histórico
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'akumas', "
CREATE TABLE IF NOT EXISTS {$P}akumas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_propio VARCHAR(120) NOT NULL,
    familia ENUM('paramecia','zoan','logia') NOT NULL DEFAULT 'paramecia',
    rareza ENUM('comun','ancestral','mitologica') NULL,
    tier TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-5',
    aspecto VARCHAR(160) NOT NULL DEFAULT '',
    mecanica_base JSON NULL COMMENT 'pasivas, límites, rupturas con condición',
    puertas JSON NULL COMMENT 'efectos 5.7 + no registrados con calibración',
    debilidades JSON NULL COMMENT 'enemigo natural',
    requisitos_portador JSON NULL,
    influencia_ficha JSON NULL COMMENT 'dotes/defectos — balanza a 0',
    despertar JSON NULL,
    precio_base BIGINT UNSIGNED NOT NULL DEFAULT 0,
    coste_pp JSON NULL COMMENT 'matriz de especificidad',
    portador_id INT UNSIGNED NULL COMMENT 'cupo único',
    origen ENUM('tirada','compra','recompensa') NULL,
    estado ENUM('sin_portador','con_portador','renacida') NOT NULL DEFAULT 'sin_portador',
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre_propio),
    UNIQUE KEY uq_portador (portador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'akuma_pool_tirada', "
CREATE TABLE IF NOT EXISTS {$P}akuma_pool_tirada (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tier_max TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT 'nv3+ T1-T2 · nv15+ T3 · nv30+ T4 · T5 nunca',
    mar_region VARCHAR(60) NOT NULL DEFAULT '',
    afinidad VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'sabor del Mundo Vivo',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'despertares', "
CREATE TABLE IF NOT EXISTS {$P}despertares (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    akuma_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    tramite_id INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_akuma (akuma_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'akuma_historico', "
CREATE TABLE IF NOT EXISTS {$P}akuma_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    akuma_id INT UNSIGNED NOT NULL,
    portador_id INT UNSIGNED NULL,
    via ENUM('tirada','compra','recompensa','renacimiento') NULL,
    coste JSON NULL,
    tipo_evento ENUM('obtencion','renacimiento','muerte') NOT NULL DEFAULT 'obtencion',
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_akuma (akuma_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.19 — Haki
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'haki', "
CREATE TABLE IF NOT EXISTS {$P}haki (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    tipo ENUM('armadura','mantra','conquistador') NOT NULL,
    nivel TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-5',
    usos_acumulados INT UNSIGNED NOT NULL DEFAULT 0,
    pp_invertidos INT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pj_tipo (personaje_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'haki_conquistador', "
CREATE TABLE IF NOT EXISTS {$P}haki_conquistador (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    intento_nivel TINYINT UNSIGNED NOT NULL COMMENT 'nv5/15/25/35/45',
    prob TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '3/8/15/25/40 %',
    exito TINYINT(1) NOT NULL DEFAULT 0,
    tramite_id INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'haki_historico', "
CREATE TABLE IF NOT EXISTS {$P}haki_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    tipo ENUM('armadura','mantra','conquistador') NOT NULL,
    nivel_desde TINYINT UNSIGNED NOT NULL DEFAULT 0,
    nivel_hasta TINYINT UNSIGNED NOT NULL DEFAULT 0,
    usos INT UNSIGNED NOT NULL DEFAULT 0,
    pp INT UNSIGNED NOT NULL DEFAULT 0,
    motivo TEXT NULL,
    tema_cierre_id INT UNSIGNED NULL,
    firmado_por INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.20 — Narradores y auto-narradas: misiones (6 bloques), tramos, participantes
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'misiones', "
CREATE TABLE IF NOT EXISTS {$P}misiones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria ENUM('faccion','reino_isla','profesional','bajo_mundo','especial') NOT NULL DEFAULT 'reino_isla',
    origen VARCHAR(160) NOT NULL DEFAULT '',
    isla_id INT UNSIGNED NULL,
    dificultad VARCHAR(40) NOT NULL DEFAULT '',
    duracion_rondas TINYINT UNSIGNED NOT NULL DEFAULT 1,
    identidad JSON NULL,
    condiciones JSON NULL COMMENT 'victoria/fracaso explícitas',
    escenas JSON NULL COMMENT '3 actos + NPCs + oráculos',
    recompensas JSON NULL COMMENT 'berries/PP/fama/objetos',
    requisitos JSON NULL,
    secretos_json JSON NULL COMMENT 'SOLO staff/narradores (permiso restringido)',
    estado ENUM('borrador','publicada','en_curso','cumplida','fracasada','abandonada','archivada') NOT NULL DEFAULT 'borrador',
    resultado TEXT NULL,
    motivo TEXT NULL,
    narrador_id INT UNSIGNED NULL COMMENT 'NULL = auto-narrada',
    en_tablon TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'mision_tramos', "
CREATE TABLE IF NOT EXISTS {$P}mision_tramos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mision_id INT UNSIGNED NOT NULL,
    tramo TINYINT UNSIGNED NOT NULL DEFAULT 1,
    acto TINYINT UNSIGNED NOT NULL DEFAULT 1,
    oraculo_id INT UNSIGNED NULL,
    texto MEDIUMTEXT NULL,
    posts_considerados JSON NULL,
    firma_id INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_mision (mision_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'mision_participantes', "
CREATE TABLE IF NOT EXISTS {$P}mision_participantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mision_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    entrada INT UNSIGNED NOT NULL DEFAULT 0,
    salida INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_mision (mision_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// F5.2 — flujo de la auto-narrada: el tema presente del trámite 52, el PJ que
// la solicitó y la fecha de apertura (el cron cierra abandonadas con él).
ope7_add_col($db, 'misiones', 'tema_id', "INT UNSIGNED NULL COMMENT 'tema presente de la misión (5.6, trámite 52)'", 'en_tablon');
ope7_add_col($db, 'misiones', 'solicitante_id', "INT UNSIGNED NULL COMMENT 'personaje que la solicitó (en curso)'", 'tema_id');
ope7_add_col($db, 'misiones', 'abierta_en', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'fecha real de apertura del tema'", 'solicitante_id');
ope7_add_col($db, 'misiones', 'oraculos', "JSON NULL COMMENT 'plan de oráculos por acto (5.16, motor de travesías)'", 'abierta_en');

// ─────────────────────────────────────────────────────────────
// 5.22 — Cibernética (implantes) y Familias Legendarias (linajes)
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'implantes', "
CREATE TABLE IF NOT EXISTS {$P}implantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    zona ENUM('extremidades','torso','cabeza') NOT NULL,
    nivel ENUM('N1','N2','N3') NOT NULL DEFAULT 'N1',
    puerta_nivel TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'nv10/20/35',
    requisitos JSON NULL COMMENT 'acumulativos (suma de todos los implantes)',
    ranuras JSON NULL COMMENT 'material/armamento/bonificador/habilidad',
    precios JSON NULL COMMENT 'instalación/PP/mantenimiento',
    defectos JSON NULL COMMENT 'balanza a 0',
    disponibilidad JSON NULL COMMENT 'isla/eventos',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'modificaciones_personaje', "
CREATE TABLE IF NOT EXISTS {$P}modificaciones_personaje (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    implante_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    ranuras JSON NULL,
    nivel ENUM('N1','N2','N3') NOT NULL DEFAULT 'N1',
    estado ENUM('activo','averiado','retirado') NOT NULL DEFAULT 'activo',
    daño JSON NULL,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'implante_historico', "
CREATE TABLE IF NOT EXISTS {$P}implante_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    modificacion_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    evento VARCHAR(80) NOT NULL DEFAULT '',
    motivo TEXT NULL,
    firmado_por INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'familias_legendarias', "
CREATE TABLE IF NOT EXISTS {$P}familias_legendarias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL COMMENT 'Línea D. / Los Vientomar / La Casa Cindral',
    dote VARCHAR(160) NOT NULL DEFAULT '',
    defecto VARCHAR(160) NOT NULL DEFAULT '' COMMENT 'La sangre llama',
    cupo TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT '3-5',
    lore TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'linaje_personaje', "
CREATE TABLE IF NOT EXISTS {$P}linaje_personaje (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    estado ENUM('activo','revocado') NOT NULL DEFAULT 'activo',
    motivo TEXT NULL,
    concedido_por INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_familia (familia_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.21 — Motor de trámites (núcleo transversal) + histórico
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'tramites', "
CREATE TABLE IF NOT EXISTS {$P}tramites (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'nº del catálogo (1-67)',
    tipo VARCHAR(80) NOT NULL DEFAULT '',
    estado ENUM('borrador','pendiente','prompt_listo','analizado','en_revision','revision_usuario','aceptado_usuario','publicado','rechazado','archivado') NOT NULL DEFAULT 'pendiente',
    solicitante_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'uid MyBB',
    personaje_id INT UNSIGNED NULL,
    motivo TEXT NULL,
    ids_json JSON NULL COMMENT 'contexto: tema/personaje/isla/rumor/cartel/barco/fruta',
    prompt MEDIUMTEXT NULL,
    resultado_json TEXT NULL COMMENT 'resultado IA (JSON, editable en la bandeja)',
    firma_staff INT UNSIGNED NULL,
    skill VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'skill del Anexo B si aplica',
    ciclo_version TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ciclo con usuario (3/13)',
    fecha_creacion INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_firma INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_solicitante (solicitante_id),
    KEY idx_estado (estado),
    KEY idx_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F1.3 — estados del ciclo con usuario (3/13). Idempotente: re-definir el ENUM
// completo es inocuo; cubre bases creadas antes de F1.3.
ope7_run($db, 'tramites+estados-ciclo', "
ALTER TABLE {$P}tramites
    MODIFY COLUMN estado ENUM('borrador','pendiente','prompt_listo','analizado','en_revision','revision_usuario','aceptado_usuario','publicado','rechazado','archivado') NOT NULL DEFAULT 'pendiente';
");

ope7_run($db, 'tramites_historico', "
CREATE TABLE IF NOT EXISTS {$P}tramites_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tramite_id INT UNSIGNED NOT NULL,
    estado VARCHAR(40) NOT NULL DEFAULT '',
    actor_id INT UNSIGNED NOT NULL DEFAULT 0,
    motivo TEXT NULL,
    datos JSON NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tramite (tramite_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// 5.21-bis — Muertes (reliquias, herencia)
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'muertes', "
CREATE TABLE IF NOT EXISTS {$P}muertes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    personaje_id INT UNSIGNED NOT NULL,
    tema_id INT UNSIGNED NULL,
    causa TEXT NULL,
    umbral_confirmado VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'PV ≤ -(VOL×2) o PE ≤ -RES',
    calidad ENUM('descuidada','digna','leyenda') NOT NULL DEFAULT 'digna',
    herencia JSON NULL COMMENT 'PP 60→1.000 · berries 5.000→1M × calidad',
    efectos_mundo JSON NULL,
    tramite_id INT UNSIGNED NULL,
    firmado_por INT UNSIGNED NULL,
    heredero_id INT UNSIGNED NULL COMMENT 'personaje que reclama la herencia (F2.1)',
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pj (personaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F2.1 — herencia al siguiente personaje: columna heredero_id (idempotente).
ope7_add_col($db, 'muertes', 'heredero_id', "INT UNSIGNED NULL COMMENT 'personaje que reclama la herencia (F2.1)'", 'firmado_por');

// F4.1 — precios por nivel N1-N3 del tipo de barco (5.17): `precio` guarda N1,
// `precios` el array completo [N1,N2,N3] (números cerrados del cap. 18 del Jugador).
ope7_add_col($db, 'tipos_barcos', 'precios', "JSON NULL COMMENT 'precio por nivel [N1,N2,N3] (F4.1)'", 'precio');

// F4.3-bis — narrativa del personaje: la ficha ya lee `desc_fisica`/`notas` desde
// `ope_personajes` (con fallback al JSON de `bio` legado); ahora son columnas reales
// que el wizard de creación escribe (descripción física, personalidad, historia, notas).
ope7_add_col($db, 'personajes', 'desc_fisica', 'TEXT NULL COMMENT "narrativa: descripción física (wizard paso 1)"', 'retrato');
ope7_add_col($db, 'personajes', 'notas', 'TEXT NULL COMMENT "notas libres del jugador (wizard paso 1)"', 'desc_fisica');

// ─────────────────────────────────────────────────────────────
// 5.21-ter — Tripulaciones
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'tripulaciones', "
CREATE TABLE IF NOT EXISTS {$P}tripulaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    bandera VARCHAR(160) NOT NULL DEFAULT '',
    proposito TEXT NULL,
    capitan_id INT UNSIGNED NOT NULL DEFAULT 0,
    barco_id INT UNSIGNED NULL,
    cofre_id INT UNSIGNED NULL,
    estado ENUM('activa','disuelta') NOT NULL DEFAULT 'activa',
    fundacion_tema_id INT UNSIGNED NULL,
    fundada_por INT UNSIGNED NOT NULL DEFAULT 0,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'tripulantes', "
CREATE TABLE IF NOT EXISTS {$P}tripulantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tripulacion_id INT UNSIGNED NOT NULL,
    personaje_id INT UNSIGNED NOT NULL,
    rol ENUM('capitan','miembro') NOT NULL DEFAULT 'miembro',
    espacio_ocupado TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'por raza (5.17)',
    fecha_ingreso INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_salida INT UNSIGNED NULL,
    estado ENUM('activo','salio') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (id),
    KEY idx_tripulacion (tripulacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

ope7_run($db, 'tripulacion_historico', "
CREATE TABLE IF NOT EXISTS {$P}tripulacion_historico (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tripulacion_id INT UNSIGNED NOT NULL,
    evento VARCHAR(80) NOT NULL DEFAULT '',
    motivo TEXT NULL,
    firmado_por INT UNSIGNED NULL,
    fecha INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tripulacion (tripulacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// F5.3 — aviso de disolución por <2 activos (22.9): primera detección → aviso
// con plazo para reclutar; segunda → disolución automática (hook de ronda).
ope7_add_col($db, 'tripulaciones', 'aviso_disolucion_en', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'fecha real del aviso de disolución (22.9)'", 'fundacion_tema_id');

// ─────────────────────────────────────────────────────────────
// Seeds base de F0 (idempotentes)
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'seed economia_config', "
INSERT INTO {$P}economia_config (id, moneda, banda_min, banda_max, margen_min, margen_max, stock_items, stock_consumibles, stock_armas, redondeo)
VALUES (1, 'berries', 0.50, 2.00, -0.20, 0.30, 10, 10, 3, 'decenas')
ON DUPLICATE KEY UPDATE moneda = VALUES(moneda);
");

ope7_run($db, 'seed calendario_foro', "
INSERT INTO {$P}calendario_foro (id, fecha_foro_actual, ratio, ultima_actualizacion_real)
SELECT 1, DATE_FORMAT(NOW(), '%e %M %Y'), 2.00, UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM {$P}calendario_foro WHERE id = 1);
");

// ─────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────
// F6.5 — Registro de ejecución de crones (vista del panel «Progresión»)
// permite al staff ver qué automatizó cada cron (última ejecución, acciones).
// ─────────────────────────────────────────────────────────────
ope7_run($db, 'cron_log', "
CREATE TABLE IF NOT EXISTS {$P}cron_log (
    cron VARCHAR(60) NOT NULL,
    ultima_run INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'timestamp de la última ejecución',
    acciones INT NOT NULL DEFAULT 0 COMMENT 'cuánto automatizó la última ejecución (conteo de acciones)',
    detalle TEXT NULL COMMENT 'resumen de la última ejecución',
    PRIMARY KEY (cron),
    KEY idx_run (ultima_run)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");


// Verificación
// ─────────────────────────────────────────────────────────────
echo "\n--- Verificación ---\n";
$count = 0;
$res = $db->query("SHOW TABLES LIKE '{$P}%'");
$list = array();
while ($row = $res->fetch_array()) {
    $list[] = $row[0];
}
sort($list);
foreach ($list as $t) {
    echo "  tabla: {$t}\n";
    $count++;
}
echo "  total mybb_ope_*: {$count}\n";

echo "\n=== DONE ===\n";
$db->close();
