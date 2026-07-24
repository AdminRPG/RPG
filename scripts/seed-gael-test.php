<?php
/**
 * One Piece: Eternal · Seed: Gael Thorne
 * 
 * Personaje de prueba completo de One Piece: Eternal
 * para verificar los nuevos campos e integraciones de la ficha.
 * 
 * Uso: php scripts/seed-gael-test.php
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/_db-config.php';

// ── Verificar si ya existe ──
$check = $db->query("SELECT pid FROM mybb_rol_personajes WHERE nombre = 'Gael Thorne' LIMIT 1");

if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $pid = (int)$row['pid'];
    echo "Gael Thorne ya existe (pid={$pid}). Regenerando...\n";
    $db->query("DELETE FROM mybb_rol_personajes WHERE pid = {$pid}");
    $db->query("DELETE FROM mybb_rol_enlace WHERE pid = {$pid}");
    $db->query("DELETE FROM mybb_rol_renombre WHERE pid = {$pid}");
}

// 1. Stats JSON
$stats_json = '{"FUE":3,"AGI":5,"RES":3,"INT":3,"SIN":4,"PER":4,"CAR":3,"FE":3}';

// 2. Datos
$datos = json_encode([
    'raza_principal' => 'erune',
    'hibrido' => false,
    'elemento' => 'Viento',
    'arma' => 'daga',
    'enlace' => 'icarus',
    'faccion' => 'Gremio',
    'stats_base' => json_decode($stats_json, true),
    'stats_efectivas' => json_decode($stats_json, true),
], JSON_UNESCAPED_UNICODE);

// 3. Bio
$bio = json_encode([
    'historia' => "Gael Thorne nació en una pequeña isla agrícola de Phantagrande. Desde pequeño estuvo fascinado por las aeronaves que pasaban de largo rumbo al horizonte. A los 15 años, tras forjar un enlace con un pequeño pajarillo de fuego que llamó Ícarus, Gael dejó su aldea y subió a bordo de una fragata mercante como aprendiz de navegante. Ahora, con una daga en el cinturón y el viento a su favor, busca escribir su propia leyenda entre las nubes.",
    'apodo' => "Cazavientos",
    'edad' => "19",
    'genero' => "Masculino",
    'pb' => "Erune Skyfarer concept art",
    'desc_fisica' => "Un joven Erune de complexión ágil y esbelta. Sus características orejas de pelaje claro sobresalen entre su cabello castaño y alborotado. Viste ropa ligera de explorador: un chaleco de cuero verde sobre una camisa blanca, pantalones oscuros ajustados y botas de montar. Lleva una bufanda azul que ondea con el viento y una daga con empuñadura de bronce en su cinto.",
    'desc_psicologica' => "Gael es curioso, optimista y siempre ansioso por ver qué hay más allá del siguiente Skydom. A veces es demasiado confiado, lo que le mete en problemas, pero su agilidad y su ingenio le suelen salvar. Valora enormemente la libertad y no tolera a quienes imponen jaulas a otros.",
    'notas' => "Prefiere evitar el combate directo, pero si es necesario, confía en su agilidad y en los ataques rápidos en picado apoyados por el fuego de Ícarus.",
], JSON_UNESCAPED_UNICODE);

// 4. Inventario & Economía
$inventario = '{"encima":[],"almacen":[]}';
$economia = '{"rupies":2000}';

// 5. Inserción
$nombre = 'Gael Thorne';
$slug = 'gael-thorne';

$query = "INSERT INTO mybb_rol_personajes 
    (uid, nombre, slug, estado, es_npc, activo, nivel, avatar,
     stats_json, ps_gastados, stats_ganados,
     datos, datos_publicos, datos_internos,
     desc_fisica, from_fisico, personalidad,
     inventario, economia, bio,
     mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np,
     dateline, lastedit)
    VALUES (0, ?, ?, 'aprobado', 0, 1, 1, '',
            ?, 28, 0,
            ?, '{}', '{}',
            '', '', '',
            ?, ?, ?,
            'phantagrande', 'Puerto libre de Phantagrande',
            'Preparando los cabos de su nave para zarpar.',
            'Activo',
            UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

$stmt = $db->prepare($query);
$stmt->bind_param('sssssss', $nombre, $slug, $stats_json, $datos, $inventario, $economia, $bio);
$stmt->execute();
$pid = $db->insert_id;
$stmt->close();

// 6. Enlace (Nivel 1, 12 usos de summon)
$db->query("INSERT INTO mybb_rol_enlace (pid, criatura, nivel, usos, pp_gastado, updated_at) 
            VALUES ({$pid}, 'icarus', 1, 12, 0, NOW())");

// 7. Renombre (150 puntos -> Novato)
$db->query("INSERT INTO mybb_rol_renombre (pid, puntos, last_update) 
            VALUES ({$pid}, 150, UNIX_TIMESTAMP())");

echo "INSERT OK. Personaje creado con pid={$pid}\n";
echo "Recuerda abrir: http://localhost/iforge/ficha.php?pid={$pid}\n";
