<?php
/**
 * One Piece: 7 Seas · Creación de personaje (F1.1)
 * ------------------------------------------------------------------
 * Wizard en 8 pasos sobre el esquema mybb_ope_* (Anexo A.1):
 *   1 Identidad → 2 Raza/tribu → 3 Atributos (120, techo nv1 = 20)
 *   4 Dotes/defectos (balanza 0) → 5 Dominios (2 puntos) → 6 Rasgos (balanza 0)
 *   7 Idea de técnica inicial → 8 Resumen → enviar → trámite 3 (validación, IA + ciclo).
 * Las técnicas iniciales NO son gratuitas (decisión D1.4): la idea viaja como nota
 * del trámite y la técnica entra por el trámite 13 con PP.
 * Scope CSS: body.ope-pg-crear-personaje. Cero bloques <style> ni estilos inline estáticos.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}

// ── Cupo de personajes (rol_cuentas.slots vs ope_personajes) ──
$slots = 1;
if ($db->table_exists('rol_cuentas')) {
    $sq = $db->simple_select('rol_cuentas', 'slots', "uid = {$uid}", array('limit' => 1));
    $sr = $db->fetch_array($sq);
    $slots = max(1, (int) ($sr['slots'] ?? 1));
}
$uq = $db->simple_select('ope_personajes', 'COUNT(*) AS c', "uid = {$uid} AND estado != 'rechazado'");
$usados = (int) $db->fetch_field($uq, 'c');
$hay_hueco = $usados < $slots;

// ── Catálogos ──
$razas = array();
$rq = $db->simple_select('ope_razas', '*', 'activo = 1', array('order_by' => 'id'));
while ($r = $db->fetch_array($rq)) {
    $r['mods'] = $r['modificadores'] ? json_decode($r['modificadores'], true) : array();
    $razas[] = $r;
}
$tribus = array();
$rq = $db->simple_select('ope_tribus', '*', '', array('order_by' => 'raza_id, id'));
while ($r = $db->fetch_array($rq)) {
    $tribus[(int) $r['raza_id']][] = $r;
}
$dominios = array();
$rq = $db->simple_select('ope_dominios', '*', 'activo = 1', array('order_by' => 'tipo, id'));
while ($r = $db->fetch_array($rq)) {
    $dominios[] = $r;
}
$dotes = array();
$rq = $db->simple_select('ope_dotes', '*', 'activo = 1', array('order_by' => 'tipo, id'));
while ($r = $db->fetch_array($rq)) {
    $dotes[] = $r;
}
$defectos = array();
$rq = $db->simple_select('ope_defectos', '*', 'activo = 1', array('order_by' => 'id'));
while ($r = $db->fetch_array($rq)) {
    $defectos[] = $r;
}
$rasgos = array();
$rq = $db->simple_select('ope_rasgos', '*', 'activo = 1', array('order_by' => 'tipo, id'));
while ($r = $db->fetch_array($rq)) {
    $rasgos[] = $r;
}
$dote_nombre = array();
foreach ($dotes as $dt) {
    $dote_nombre[(int) $dt['id']] = $dt['nombre'];
}
$defecto_nombre = array();
foreach ($defectos as $df) {
    $defecto_nombre[(int) $df['id']] = $df['nombre'];
}

$ATR = array(
    'fue'  => array('FUE', 'Fuerza'),
    'des'  => array('DES', 'Destreza'),
    'agi'  => array('AGI', 'Agilidad'),
    'res'  => array('RES', 'Resistencia'),
    'per'  => array('PER', 'Percepción'),
    'inte' => array('INT', 'Intelecto'),
    'car'  => array('CAR', 'Carisma'),
    'vol'  => array('VOL', 'Voluntad'),
);

// ── Procesado POST ──
$errores = array();
$avisos  = array();
$preview = false;
$d = array(
    'nombre' => '', 'retrato' => '',
    'desc_fisica' => '', 'personalidad' => '', 'historia' => '', 'notas' => '',
    'raza_id' => 0, 'raza_hibrida_id' => 0, 'tribu_id' => 0,
    'fue' => 0, 'des' => 0, 'agi' => 0, 'res' => 0, 'per' => 0, 'inte' => 0, 'car' => 0, 'vol' => 0,
    'dotes' => array(), 'defectos' => array(), 'rasgos' => array(),
    'dominios' => array(), 'idea_tecnica' => '',
);
$SEL_RAZA = array();
$sec_resumen = null;

if ($mybb->request_method === 'post') {
    $accion = (string) $mybb->get_input('accion');

    $d['nombre'] = trim((string) $mybb->get_input('nombre'));
    $d['retrato'] = trim((string) $mybb->get_input('retrato'));
    $d['desc_fisica'] = trim((string) $mybb->get_input('desc_fisica'));
    $d['personalidad'] = trim((string) $mybb->get_input('personalidad'));
    $d['historia'] = trim((string) $mybb->get_input('historia'));
    $d['notas'] = trim((string) $mybb->get_input('notas'));
    $d['raza_id'] = (int) $mybb->get_input('raza_id', 1);
    $d['raza_hibrida_id'] = (int) $mybb->get_input('raza_hibrida_id', 1);
    $d['tribu_id'] = (int) $mybb->get_input('tribu_id', 1);
    foreach (array_keys($ATR) as $k) {
        $d[$k] = max(0, (int) $mybb->get_input($k, 1));
    }
    $d['dotes'] = array_map('intval', (array) $mybb->get_input('dotes', 2));
    $d['defectos'] = array_map('intval', (array) $mybb->get_input('defectos', 2));
    $d['rasgos'] = array_map('intval', (array) $mybb->get_input('rasgos', 2));
    foreach ((array) $mybb->get_input('dominios', 2) as $did => $nv) {
        $nv = (int) $nv;
        if ($nv > 0) {
            $d['dominios'][(int) $did] = $nv;
        }
    }
    $d['idea_tecnica'] = trim((string) $mybb->get_input('idea_tecnica'));

    // Validación previa de formulario.
    if ($d['nombre'] === '') {
        $errores[] = 'El nombre del personaje es obligatorio.';
    }
    if ($d['raza_id'] < 1) {
        $errores[] = 'Debes elegir una raza.';
    } elseif ($d['raza_hibrida_id'] > 0 && $d['raza_hibrida_id'] === $d['raza_id']) {
        $errores[] = 'Un híbrido necesita dos razas distintas.';
    }
    $suma_atr = 0;
    foreach (array_keys($ATR) as $k) {
        $suma_atr += $d[$k];
    }
    if ($suma_atr !== 120) {
        $errores[] = "La suma de atributos es {$suma_atr}; el presupuesto de creación es exactamente 120.";
    }

    // Validación del sistema (balanzas, techos, híbridos, tribus, dominios).
    $ficha_probe = array(
        'nivel' => 1,
        'raza_id' => $d['raza_id'], 'raza_hibrida_id' => $d['raza_hibrida_id'], 'tribu_id' => $d['tribu_id'],
        'fue' => $d['fue'], 'des' => $d['des'], 'agi' => $d['agi'], 'res' => $d['res'],
        'per' => $d['per'], 'inte' => $d['inte'], 'car' => $d['car'], 'vol' => $d['vol'],
    );
    $val = ope7_pj_validar_ficha($ficha_probe, array(
        'dotes' => $d['dotes'], 'defectos' => $d['defectos'],
        'rasgos' => $d['rasgos'], 'dominios' => $d['dominios'], 'es_creacion' => true,
    ));
    $errores = array_merge($errores, $val['errores']);
    $avisos  = $val['avisos'];

    // Parejas espejo dote↔defecto (5.2) desde el catálogo.
    foreach ($dotes as $dt) {
        if (!in_array((int) $dt['id'], $d['dotes'], true)) {
            continue;
        }
        $incomp = $dt['incompatibilidades'] ? json_decode($dt['incompatibilidades'], true) : array();
        foreach ((array) $incomp as $nom_def) {
            foreach ($d['defectos'] as $did) {
                if (isset($defecto_nombre[$did]) && $defecto_nombre[$did] === $nom_def) {
                    $errores[] = "La dote «{$dt['nombre']}» y el defecto «{$nom_def}» son pareja espejo incompatible (5.2).";
                }
            }
        }
    }
    foreach ($defectos as $df) {
        if (!in_array((int) $df['id'], $d['defectos'], true)) {
            continue;
        }
        $incomp = $df['incompatibilidades'] ? json_decode($df['incompatibilidades'], true) : array();
        foreach ((array) $incomp as $nom_dote) {
            foreach ($d['dotes'] as $dot_id) {
                if (isset($dote_nombre[$dot_id]) && $dote_nombre[$dot_id] === $nom_dote) {
                    $errores[] = "El defecto «{$df['nombre']}» y la dote «{$nom_dote}» son pareja espejo incompatible (5.2).";
                }
            }
        }
    }

    $sec_resumen = ope7_pj_secundarios($ficha_probe);
    foreach ($razas as $r) {
        $SEL_RAZA[(int) $r['id']] = $r;
    }

    if ($accion === 'enviar' && empty($errores) && $hay_hueco) {
        // Slug único (si el nombre ya existe, se numera: nombre-2, nombre-3…).
        $slug = ope7_slug($d['nombre']);
        $slug_base = $slug;
        $i = 2;
        for (;;) {
            $chk = $db->simple_select('ope_personajes', 'id', "slug = '" . $db->escape_string($slug) . "'", array('limit' => 1));
            if ($db->num_rows($chk) === 0) {
                break;
            }
            $slug = $slug_base . '-' . $i++;
        }
        $pid = ope7_pj_guardar(array(
            'uid' => $uid, 'nombre' => $d['nombre'], 'slug' => $slug, 'estado' => 'borrador',
            'estado_vida' => 'activa', 'es_NPC' => 0, 'nivel' => 1,
            'raza_id' => $d['raza_id'], 'raza_hibrida_id' => $d['raza_hibrida_id'] > 0 ? $d['raza_hibrida_id'] : null,
            'tribu_id' => $d['tribu_id'] > 0 ? $d['tribu_id'] : null,
            'fue' => $d['fue'], 'des' => $d['des'], 'agi' => $d['agi'], 'res' => $d['res'],
            'per' => $d['per'], 'inte' => $d['inte'], 'car' => $d['car'], 'vol' => $d['vol'],
            'puntos_comprados' => 0, 'pp_saldo' => 0, // 7.1: acumulado desde el último nivel (0 al crear)
            'retrato' => $d['retrato'] !== '' ? $d['retrato'] : null,
            'desc_fisica' => $d['desc_fisica'], 'personalidad' => $d['personalidad'],
            'historia' => $d['historia'], 'notas' => $d['notas'],
        ));
        if ($pid > 0) {
            foreach ($d['dotes'] as $dot_id) {
                $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'dote_id' => $dot_id, 'origen' => 'creacion', 'fecha' => TIME_NOW));
            }
            foreach ($d['defectos'] as $def_id) {
                $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'defecto_id' => $def_id, 'origen' => 'creacion', 'fecha' => TIME_NOW));
            }
            foreach ($d['rasgos'] as $rg_id) {
                $db->insert_query('ope_personaje_rasgos', array('personaje_id' => $pid, 'rasgo_id' => $rg_id, 'origen' => 'creacion', 'karma_acumulado' => 0, 'estado' => 'activo', 'contador_contradicciones' => 0));
            }
            foreach ($d['dominios'] as $dom_id => $nv) {
                $db->insert_query('ope_dominios_personaje', array('personaje_id' => $pid, 'dominio_id' => $dom_id, 'nivel' => $nv, 'origen' => 'creacion'));
            }
            ope7_pj_set_activo($uid, 'ope', $pid);

            // F2.1: herencia de personajes muertos (trámite 62) → el nuevo hereda.
            $heredado = function_exists('ope7_pj_heredar') ? ope7_pj_heredar($uid, $pid) : array('aplicadas' => 0);

            $palabras = function ($t) { return count(preg_split('/\s+/u', trim((string) $t), -1, PREG_SPLIT_NO_EMPTY)); };
            $resumen_ficha = "Personaje: {$d['nombre']} (nivel 1)\n"
                . "Raza: " . ($SEL_RAZA[$d['raza_id']]['nombre'] ?? '?')
                . ($d['raza_hibrida_id'] > 0 ? ' × ' . ($SEL_RAZA[$d['raza_hibrida_id']]['nombre'] ?? '?') : '')
                . "\nAtributos base: FUE {$d['fue']} DES {$d['des']} AGI {$d['agi']} RES {$d['res']} PER {$d['per']} INT {$d['inte']} CAR {$d['car']} VOL {$d['vol']}\n"
                . "Secundarios: PV {$sec_resumen['pv']} · PE {$sec_resumen['pe']} · PA {$sec_resumen['pa']} · Vel {$sec_resumen['velocidad']} m/s\n"
                . "Narrativa (palabras): descripción física " . $palabras($d['desc_fisica']) . ' · personalidad ' . $palabras($d['personalidad'])
                . ' · historia ' . $palabras($d['historia']) . ' · notas ' . $palabras($d['notas']) . "\n"
                . "Idea de técnica inicial: " . ($d['idea_tecnica'] !== '' ? $d['idea_tecnica'] : '(ninguna)');

            $tr = ope7_tramite_crear($uid, $pid, 3, 'Validación de ficha de creación', array('personaje_id' => $pid), array(
                'resumen' => $resumen_ficha
                    . ((int) ($heredado['aplicadas'] ?? 0) > 0
                        ? "\nHerencia reclamada: +" . (int) ($heredado['pp'] ?? 0) . " PP y +" . number_format((int) ($heredado['berries'] ?? 0)) . " ฿ (" . (int) $heredado['aplicadas'] . " muerte(s))."
                        : ''),
                'idea_tecnica' => $d['idea_tecnica'],
            ));
            header('Location: ' . $bburl . '/tramites.php?ok=1' . ($tr['ok'] ? '' : '&sintramite=1'));
            exit;
        }
        $errores[] = 'No se pudo guardar el personaje (error interno).';
    }

    if ($accion === 'enviar' && !$hay_hueco) {
        $errores[] = 'Has alcanzado tu cupo de personajes.';
    }
    $preview = $accion !== '';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Crear personaje</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-crear-personaje">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/personajes.php">Personajes</a><span class="sep">›</span><b>Crear personaje</b>
</div></div>
<div class="wrap">

<?php if (!$hay_hueco): ?>
  <div class="shead"><h1>Sin plazas</h1><span class="code">F1 · CUENTA</span><span class="rule"></span></div>
  <div class="pj-empty">
    <div class="anvil"><svg viewBox="0 0 24 24"><path d="M12 3v18M5 8l7-5 7 5M5 8l-2 9 9 4 9-4-2-9M7 21h10"/></svg></div>
    <div class="big">Tu cuenta está al completo</div>
    <p>Ya has creado todos los personajes que permite tu cuenta. Para crear otro, contacta con el staff para ampliar tu cupo de plazas.</p>
    <div class="acts"><a class="btn" href="<?php echo $bburl; ?>/personajes.php">Volver a personajes</a></div>
  </div>
<?php elseif (!empty($errores)): ?>
  <div class="flash warn"><b>La ficha no pasa la validación todavía.</b> Corrige lo marcado y vuelve a enviarla:
    <ul><?php foreach ($errores as $e): ?><li><?php echo htmlspecialchars_uni($e); ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

  <div class="shead"><h1>Crear personaje</h1><span class="code">7 SEAS · F1</span><span class="rule"></span></div>
  <p class="wiz-lead">Tu primera decisión de identidad. El sistema reparte <b>120 puntos</b> de atributos, una <b>balanza de dotes/defectos a 0</b>, <b>2 puntos de dominio</b> y una <b>balanza de rasgos a 0</b>. Nada se publica sin la validación del staff (trámite 3).</p>

  <form method="post" action="crear-personaje.php" id="wizForm" novalidate>
    <input type="hidden" name="accion" id="accionInput" value="">

    <div class="wiz-progress" id="wizProgress">
      <div class="wiz-step-dot on" data-step="1"><span class="n">1</span>Identidad</div>
      <div class="wiz-step-dot" data-step="2"><span class="n">2</span>Raza y tribu</div>
      <div class="wiz-step-dot" data-step="3"><span class="n">3</span>Atributos</div>
      <div class="wiz-step-dot" data-step="4"><span class="n">4</span>Dotes y defectos</div>
      <div class="wiz-step-dot" data-step="5"><span class="n">5</span>Dominios</div>
      <div class="wiz-step-dot" data-step="6"><span class="n">6</span>Rasgos</div>
      <div class="wiz-step-dot" data-step="7"><span class="n">7</span>Técnica inicial</div>
      <div class="wiz-step-dot" data-step="8"><span class="n">8</span>Resumen y envío</div>
    </div>

    <div class="wiz-forge">
      <aside class="wiz-preview">
        <div class="wiz-card">
          <div class="wiz-card-art">
            <div class="wiz-card-ph" id="cardPh">◈</div>
            <div class="wiz-card-veil"></div>
            <div class="wiz-card-lv"><span>Nivel</span><b>1</b></div>
          </div>
          <div class="wiz-card-meta">
            <div class="wiz-card-name" id="cardName">Sin nombre</div>
            <div class="wiz-card-chips">
              <span class="wiz-chip" id="cardRace">Sin raza</span>
              <span class="wiz-chip dn" id="cardTribe"></span>
            </div>
            <div class="wiz-card-stats">
              <?php foreach ($ATR as $k => $lab): ?>
              <div class="wiz-st"><span><?php echo $lab[0]; ?></span><b id="card-<?php echo $k; ?>">0</b></div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="wiz-card-foot">
            <div class="wiz-card-line"><b>PV</b><span id="cardPv">—</span></div>
            <div class="wiz-card-line"><b>PE</b><span id="cardPe">—</span></div>
            <div class="wiz-card-line"><b>PA</b><span id="cardPa">—</span></div>
          </div>
        </div>
      </aside>

      <div class="wiz-main">

        <section class="wiz-step on" data-step="1">
          <div class="plate">
            <div class="plate-h"><span class="t">Identidad</span><span class="c">Paso 1</span></div>
            <div class="plate-b">
              <div class="grid2">
                <div class="field">
                  <label class="flabel" for="nombre">Nombre del personaje</label>
                  <input type="text" id="nombre" name="nombre" maxlength="120" value="<?php echo htmlspecialchars_uni($d['nombre']); ?>" placeholder="Cómo te llamas en el mar">
                  <span class="fl-hint">Tu nombre on-rol. Sin apellidos obligatorios: uno basta.</span>
                </div>
                <div class="field">
                  <label class="flabel" for="retrato">Retrato (URL de imagen, opcional)</label>
                  <input type="text" id="retrato" name="retrato" maxlength="255" value="<?php echo htmlspecialchars_uni($d['retrato']); ?>" placeholder="https://…">
                </div>
              </div>
              <h3 class="wiz-sub">Narrativa</h3>
              <div class="grid2">
                <div class="field">
                  <label class="flabel" for="desc_fisica">Descripción física</label>
                  <textarea id="desc_fisica" name="desc_fisica" maxlength="2000" class="historia-textarea" placeholder="Estatura, complexión, marcas, cicatrices, cómo vistes…"><?php echo htmlspecialchars_uni($d['desc_fisica']); ?></textarea>
                  <span class="fl-hint">Cómo te ven los demás. El staff la usará para tu ficha.</span>
                </div>
                <div class="field">
                  <label class="flabel" for="personalidad">Personalidad</label>
                  <textarea id="personalidad" name="personalidad" maxlength="2000" class="historia-textarea" placeholder="Temperamento, manías, miedos, cómo reaccionas…"><?php echo htmlspecialchars_uni($d['personalidad']); ?></textarea>
                  <span class="fl-hint">Quién eres cuando no hay cámaras. Conecta con tus rasgos del paso 6.</span>
                </div>
                <div class="field">
                  <label class="flabel" for="historia">Historia</label>
                  <textarea id="historia" name="historia" maxlength="5000" class="historia-textarea" placeholder="Tu pasado antes de zarpar: origen, sueño, por qué estás en el mar…"><?php echo htmlspecialchars_uni($d['historia']); ?></textarea>
                  <span class="fl-hint">Tu pasado on-rol. Sin spoilers del futuro: eso se juega.</span>
                </div>
                <div class="field">
                  <label class="flabel" for="notas">Notas (opcional)</label>
                  <textarea id="notas" name="notas" maxlength="2000" class="historia-textarea" placeholder="Detalles para el staff, dudas de coherencia, inspiraciones…"><?php echo htmlspecialchars_uni($d['notas']); ?></textarea>
                  <span class="fl-hint">Lo que quieras que el staff sepa en la validación del trámite 3.</span>
                </div>
              </div>
            </div>
          </div>
          <div class="wiz-nav"><button type="button" class="btn btn-hot" data-next="2">Siguiente · Raza y tribu</button></div>
        </section>

        <section class="wiz-step" data-step="2">
          <div class="plate">
            <div class="plate-h"><span class="t">Raza y tribu</span><span class="c">Paso 2</span></div>
            <div class="plate-b">
              <div class="rc-body">
                <div class="race-grid">
                  <?php foreach ($razas as $r): if ((int) $r['es_hibrido'] === 1) continue; ?>
                  <label class="race-card" data-mods="<?php echo htmlspecialchars_uni(json_encode($r['mods'] ?: new stdClass(), JSON_UNESCAPED_UNICODE)); ?>">
                    <input type="radio" name="raza_id" value="<?php echo (int) $r['id']; ?>" data-raza="<?php echo (int) $r['id']; ?>" <?php echo $d['raza_id'] === (int) $r['id'] ? 'checked' : ''; ?>>
                    <div class="rc-name"><?php echo htmlspecialchars_uni($r['nombre']); ?></div>
                    <div class="rc-resumen"><?php echo htmlspecialchars_uni($r['lore'] ? mb_substr($r['lore'], 0, 110) . '…' : ''); ?></div>
                    <div class="rc-pas">
                      <?php
                      $mods_txt = array();
                      foreach ($ATR as $k => $lab) {
                          $mv = (int) ($r['mods'][$k] ?? 0);
                          if ($mv !== 0) {
                              $mods_txt[] = $lab[0] . ' ' . ($mv > 0 ? '+' : '') . $mv;
                          }
                      }
                      echo $mods_txt ? '<b>Raciales:</b> ' . implode(' · ', $mods_txt) : '<b>Raciales:</b> ninguna (el lienzo en blanco)';
                      ?>
                    </div>
                  </label>
                  <?php endforeach; ?>
                </div>

                <div class="field">
                  <label class="flabel"><input type="checkbox" id="hybridChk" class="wiz-check" <?php echo $d['raza_hibrida_id'] > 0 ? 'checked' : ''; ?>> Soy híbrido (dos sangres)</label>
                  <span class="fl-hint">Media de modificadores de ambas razas (mitades a favor del jugador) y las <b>primarias</b> de las dos. Sin secundarias, sin tribu.</span>
                  <div class="field dn" id="hybridSel">
                    <label class="flabel" for="raza_hibrida_id">Segunda raza progenitora</label>
                    <select id="raza_hibrida_id" name="raza_hibrida_id">
                      <option value="0">— elige la segunda raza —</option>
                      <?php foreach ($razas as $r): if ((int) $r['es_hibrido'] === 1) continue; ?>
                      <option value="<?php echo (int) $r['id']; ?>" <?php echo $d['raza_hibrida_id'] === (int) $r['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="field">
                  <label class="flabel" for="tribu_id">Tribu (solo sangre pura, sustituye tu racial secundaria)</label>
                  <select id="tribu_id" name="tribu_id">
                    <option value="0">— sin tribu —</option>
                    <?php foreach ($tribus as $rid => $lista): ?>
                      <?php foreach ($lista as $tb): ?>
                      <option value="<?php echo (int) $tb['id']; ?>" data-raza="<?php echo (int) $rid; ?>" <?php echo $d['tribu_id'] === (int) $tb['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars_uni($tb['nombre']); ?> — <?php echo htmlspecialchars_uni($tb['racial_nombre']); ?></option>
                      <?php endforeach; ?>
                    <?php endforeach; ?>
                  </select>
                  <span class="fl-hint">La tribu reemplaza tu secundaria estándar por la del linaje. No cambia atributos ni primaria.</span>
                </div>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="1">← Identidad</button>
            <button type="button" class="btn btn-hot" data-next="3">Siguiente · Atributos</button>
          </div>
        </section>

        <section class="wiz-step" data-step="3">
          <div class="plate">
            <div class="plate-h"><span class="t">Atributos</span><span class="c">Paso 3 · 120 puntos</span></div>
            <div class="plate-b">
              <p class="fl-hint fl-hint-lg">Reparte tu <b>presupuesto de 120 puntos</b>. Techo por atributo en nivel 1: <b>20</b>. Los bonus raciales se suman por encima del techo y no gastan puntos (los verás reflejados en la carta y en el resumen).</p>
              <div class="sum-total">Puntos restantes: <span id="attrRest">0</span> / 120</div>
              <div class="grid2">
                <?php foreach ($ATR as $k => $lab): ?>
                <div class="field wiz-row">
                  <label class="flabel wiz-label"><?php echo $lab[1]; ?></label>
                  <button type="button" class="stat-minus" data-atr="<?php echo $k; ?>" aria-label="Bajar <?php echo $lab[1]; ?>">−</button>
                  <input type="number" class="ps-input" name="<?php echo $k; ?>" id="atr-<?php echo $k; ?>" min="0" max="20" value="<?php echo (int) $d[$k]; ?>" data-atr="<?php echo $k; ?>">
                  <button type="button" class="stat-plus" data-atr="<?php echo $k; ?>" aria-label="Subir <?php echo $lab[1]; ?>">+</button>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="2">← Raza y tribu</button>
            <button type="button" class="btn btn-hot" data-next="4">Siguiente · Dotes y defectos</button>
          </div>
        </section>

        <section class="wiz-step" data-step="4">
          <div class="plate">
            <div class="plate-h"><span class="t">Dotes y defectos</span><span class="c">Paso 4 · balanza 0</span></div>
            <div class="plate-b">
              <div class="sum-total">Balanza: <span id="balanzaDotes">0</span> (debe ser 0)</div>
              <p class="fl-hint fl-hint-lg">Las dotes suman +1…+5 y los defectos −1…−5: juntos deben dar <b>exactamente 0</b>. Los híbridos solo toman dotes generales y de su raza dominante; las dotes «solo puro» quedan bloqueadas para ellos.</p>
              <h3 class="wiz-sub">Dotes generales</h3>
              <div class="bonus-grid">
                <?php foreach ($dotes as $dt): if ($dt['tipo'] !== 'general') continue; ?>
                <label class="bonus-chip" title="<?php echo htmlspecialchars_uni((string) $dt['efecto']); ?>">
                  <input type="checkbox" name="dotes[]" value="<?php echo (int) $dt['id']; ?>" data-pts="<?php echo (int) $dt['puntuacion']; ?>" <?php echo in_array((int) $dt['id'], $d['dotes'], true) ? 'checked' : ''; ?>>
                  <?php echo htmlspecialchars_uni($dt['nombre']); ?> <b>(+<?php echo (int) $dt['puntuacion']; ?>)</b>
                </label>
                <?php endforeach; ?>
              </div>
              <h3 class="wiz-sub">Dotes raciales</h3>
              <div class="bonus-grid" id="dotesRaciales">
                <?php foreach ($dotes as $dt): if ($dt['tipo'] !== 'racial') continue; ?>
                <label class="bonus-chip" data-raza="<?php echo (int) $dt['raza_id']; ?>" data-pura="<?php echo (int) $dt['requiere_raza_pura']; ?>" title="<?php echo htmlspecialchars_uni((string) $dt['efecto']); ?>">
                  <input type="checkbox" name="dotes[]" value="<?php echo (int) $dt['id']; ?>" data-pts="<?php echo (int) $dt['puntuacion']; ?>" <?php echo in_array((int) $dt['id'], $d['dotes'], true) ? 'checked' : ''; ?>>
                  <?php echo htmlspecialchars_uni($dt['nombre']); ?> <b>(+<?php echo (int) $dt['puntuacion']; ?>)</b>
                </label>
                <?php endforeach; ?>
              </div>
              <h3 class="wiz-sub">Defectos</h3>
              <div class="bonus-grid">
                <?php foreach ($defectos as $df): ?>
                <label class="bonus-chip" title="<?php echo htmlspecialchars_uni((string) $df['efecto']); ?>">
                  <input type="checkbox" name="defectos[]" value="<?php echo (int) $df['id']; ?>" data-pts="<?php echo (int) $df['puntuacion']; ?>" <?php echo in_array((int) $df['id'], $d['defectos'], true) ? 'checked' : ''; ?>>
                  <?php echo htmlspecialchars_uni($df['nombre']); ?> <b>(<?php echo (int) $df['puntuacion']; ?>)</b>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="3">← Atributos</button>
            <button type="button" class="btn btn-hot" data-next="5">Siguiente · Dominios</button>
          </div>
        </section>

        <section class="wiz-step" data-step="5">
          <div class="plate">
            <div class="plate-h"><span class="t">Dominios</span><span class="c">Paso 5 · 2 puntos</span></div>
            <div class="plate-b">
              <div class="sum-total">Puntos de dominio: <span id="domPts">0</span> / 2</div>
              <p class="fl-hint fl-hint-lg">Reparte tus <b>2 puntos de creación</b>: dos dominios a nivel 1 (Opción A) o uno a nivel 2 (Opción B). Los bélicos abren tiers de técnica; los oficios, capacidades con su atributo rey.</p>
              <div class="grid2">
                <?php foreach ($dominios as $dm): ?>
                <div class="field wiz-row">
                  <label class="flabel wiz-label">
                    <?php echo htmlspecialchars_uni($dm['nombre']); ?>
                    <span class="fl-hint wiz-hint-0"><?php echo $dm['tipo'] === 'belico' ? 'Bélico' : 'Oficio'; ?> · Rey: <?php echo strtoupper($dm['atributo_rey']); ?></span>
                  </label>
                  <select name="dominios[<?php echo (int) $dm['id']; ?>]" class="ps-input wiz-dom" data-dom="<?php echo (int) $dm['id']; ?>">
                    <option value="0" <?php echo empty($d['dominios'][(int) $dm['id']]) ? 'selected' : ''; ?>>—</option>
                    <option value="1" <?php echo ($d['dominios'][(int) $dm['id']] ?? 0) === 1 ? 'selected' : ''; ?>>Nv 1</option>
                    <option value="2" <?php echo ($d['dominios'][(int) $dm['id']] ?? 0) === 2 ? 'selected' : ''; ?>>Nv 2</option>
                  </select>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="4">← Dotes y defectos</button>
            <button type="button" class="btn btn-hot" data-next="6">Siguiente · Rasgos</button>
          </div>
        </section>

        <section class="wiz-step" data-step="6">
          <div class="plate">
            <div class="plate-h"><span class="t">Rasgos de personalidad</span><span class="c">Paso 6 · balanza 0</span></div>
            <div class="plate-b">
              <div class="sum-total">Balanza de rasgos: <span id="balanzaRasgos">0</span> (debe ser 0)</div>
              <p class="fl-hint fl-hint-lg">Los rasgos no tocan ninguna cifra: describen <b>quién eres cuando juegas</b>. Suma 0 exacto y sin parejas antagónicas (se bloquean solas). Se recomienda al menos 1 positivo y 1 negativo.</p>
              <div class="bonus-grid">
                <?php foreach ($rasgos as $rg): $pareja = (int) $rg['pareja_incompatible_id']; ?>
                <label class="bonus-chip" title="<?php echo htmlspecialchars_uni($rg['descripcion']); ?>">
                  <input type="checkbox" name="rasgos[]" value="<?php echo (int) $rg['id']; ?>" data-pts="<?php echo (int) $rg['puntuacion']; ?>" data-pareja="<?php echo $pareja; ?>" <?php echo in_array((int) $rg['id'], $d['rasgos'], true) ? 'checked' : ''; ?>>
                  <?php echo htmlspecialchars_uni($rg['nombre']); ?> <b>(<?php echo $rg['puntuacion'] > 0 ? '+' : ''; ?><?php echo (int) $rg['puntuacion']; ?>)</b>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="5">← Dominios</button>
            <button type="button" class="btn btn-hot" data-next="7">Siguiente · Técnica inicial</button>
          </div>
        </section>

        <section class="wiz-step" data-step="7">
          <div class="plate">
            <div class="plate-h"><span class="t">Idea de técnica inicial</span><span class="c">Paso 7 · opcional</span></div>
            <div class="plate-b">
              <div class="field">
                <label class="flabel" for="idea_tecnica">Describe tu idea (opcional)</label>
                <textarea id="idea_tecnica" name="idea_tecnica" maxlength="1000" class="historia-textarea" placeholder="Ej.: una patada giratoria que incendia la pierna con cada giro…"><?php echo htmlspecialchars_uni($d['idea_tecnica']); ?></textarea>
                <span class="fl-hint">Las técnicas <b>no son gratuitas</b> (decisión confirmada): se construyen por el trámite 13 con puntos de progreso (T1 = 60 PP). Esta idea viaja con tu ficha a la validación para que el staff la tenga en cuenta cuando la tramites.</span>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="6">← Rasgos</button>
            <button type="button" class="btn btn-hot" data-next="8">Siguiente · Resumen</button>
          </div>
        </section>

        <section class="wiz-step" data-step="8">
          <div class="plate">
            <div class="plate-h"><span class="t">Resumen y envío</span><span class="c">Paso 8</span></div>
            <div class="plate-b">
              <p class="fl-hint fl-hint-lg">El sistema recalcula tus secundarios con las fórmulas confirmadas (desglose base + racial). Al enviar, tu ficha queda como <b>borrador</b> y abre el <b>trámite 3</b> (validación de ficha, con ciclo: podrás pedir cambios).</p>
              <div class="grid2">
                <div class="field">
                  <label class="flabel">Secundarios calculados (nivel 1)</label>
                  <?php if ($sec_resumen): ?>
                  <div class="wiz-card-line"><b>Vida (PV)</b><span><?php echo $sec_resumen['pv']; ?></span></div>
                  <div class="wiz-card-line"><b>Energía (PE)</b><span><?php echo $sec_resumen['pe']; ?></span></div>
                  <div class="wiz-card-line"><b>PA por turno</b><span><?php echo $sec_resumen['pa']; ?></span></div>
                  <div class="wiz-card-line"><b>Velocidad</b><span><?php echo $sec_resumen['velocidad']; ?> m/s</span></div>
                  <div class="wiz-card-line"><b>Sprint</b><span><?php echo $sec_resumen['sprint']; ?> m/s</span></div>
                  <div class="wiz-card-line"><b>Salto</b><span><?php echo $sec_resumen['salto_v']; ?> m (v) · <?php echo $sec_resumen['salto_h']; ?> m (h)</span></div>
                  <div class="wiz-card-line"><b>Carga</b><span><?php echo $sec_resumen['carga']; ?> kg</span></div>
                  <div class="wiz-card-line"><b>Resistencia pasiva</b><span><?php echo $sec_resumen['resistencia_pasiva']; ?></span></div>
                  <div class="wiz-card-line"><b>Lanzamiento</b><span><?php echo $sec_resumen['lanzamiento']; ?> m</span></div>
                  <div class="wiz-card-line"><b>Recuperación</b><span><?php echo $sec_resumen['recuperacion']; ?>%/h</span></div>
                  <?php else: ?>
                  <span class="fl-hint">Pulsa «Ver resumen» para calcular tus secundarios y comprobar la ficha antes de enviarla.</span>
                  <?php endif; ?>
                </div>
                <div class="field">
                  <label class="flabel">Informe de validación</label>
                  <?php if ($preview): ?>
                    <?php if (empty($errores)): ?>
                      <div class="flash ok flash-0"><b>Ficha válida.</b> Todo suma: 120 puntos, balanzas a 0, reparto de dominios correcto.</div>
                    <?php else: ?>
                      <div class="flash warn flash-0"><b><?php echo count($errores); ?> problema(s) detectados:</b>
                        <ul><?php foreach ($errores as $e): ?><li><?php echo htmlspecialchars_uni($e); ?></li><?php endforeach; ?></ul>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="fl-hint">Pulsa «Ver resumen» para comprobar la ficha antes de enviarla.</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="wiz-nav">
            <button type="button" class="btn btn-ghost" data-prev="7">← Técnica inicial</button>
            <button type="button" class="btn" id="btnPreview">Ver resumen</button>
            <button type="submit" class="btn btn-hot" id="btnEnviar">Enviar a validación</button>
          </div>
        </section>

      </div>
    </div>
  </form>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
(function () {
  var accionInput = document.getElementById('accionInput');
  var steps = Array.prototype.slice.call(document.querySelectorAll('.wiz-step'));
  var dots = Array.prototype.slice.call(document.querySelectorAll('.wiz-step-dot'));
  var ATRS = ['fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol'];
  var RAZAS_MODS = {};
  document.querySelectorAll('label.race-card').forEach(function (c) {
    var id = c.querySelector('input[name="raza_id"]').value;
    try { RAZAS_MODS[id] = JSON.parse(c.getAttribute('data-mods') || '{}'); } catch (e) { RAZAS_MODS[id] = {}; }
  });

  function go(n) {
    n = Math.max(1, Math.min(8, n));
    steps.forEach(function (s) { s.classList.toggle('on', +s.getAttribute('data-step') === n); });
    dots.forEach(function (dd) {
      var sn = +dd.getAttribute('data-step');
      dd.classList.toggle('on', sn === n);
      dd.classList.toggle('done', sn < n);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  dots.forEach(function (dd) { dd.addEventListener('click', function () { go(+dd.getAttribute('data-step')); }); });
  document.querySelectorAll('[data-next]').forEach(function (b) { b.addEventListener('click', function () { go(+b.getAttribute('data-next')); }); });
  document.querySelectorAll('[data-prev]').forEach(function (b) { b.addEventListener('click', function () { go(+b.getAttribute('data-prev')); }); });
  document.getElementById('btnPreview').addEventListener('click', function () { accionInput.value = 'preview'; document.getElementById('wizForm').submit(); });
  document.getElementById('btnEnviar').addEventListener('click', function () { accionInput.value = 'enviar'; });

  function attrSum() {
    var s = 0;
    ATRS.forEach(function (k) { s += parseInt(document.getElementById('atr-' + k).value || '0', 10); });
    return s;
  }
  function razaMods() {
    var r = document.querySelector('input[name="raza_id"]:checked');
    var base = r ? (RAZAS_MODS[r.value] || {}) : {};
    var hib = document.getElementById('hybridChk').checked;
    var h = document.getElementById('raza_hibrida_id');
    if (hib && h && h.value !== '0' && RAZAS_MODS[h.value]) {
      var out = {};
      ATRS.forEach(function (k) {
        var a = parseInt(base[k] || '0', 10);
        var b = parseInt(RAZAS_MODS[h.value][k] || '0', 10);
        out[k] = Math.ceil((a + b) / 2);
      });
      return out;
    }
    return base;
  }
  function refreshAttr() {
    var rest = 120 - attrSum();
    document.getElementById('attrRest').textContent = rest;
    var mods = razaMods();
    ATRS.forEach(function (k) {
      var inp = document.getElementById('atr-' + k);
      var v = parseInt(inp.value || '0', 10);
      inp.parentElement.querySelector('.stat-plus').disabled = v >= 20 || rest <= 0;
      inp.parentElement.querySelector('.stat-minus').disabled = v <= 0;
      var card = document.getElementById('card-' + k);
      if (card) card.textContent = v + (parseInt(mods[k] || '0', 10) !== 0 ? '+' + mods[k] : '');
    });
    refreshCardSec();
  }
  document.querySelectorAll('.stat-plus').forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.getAttribute('data-atr');
      var inp = document.getElementById('atr-' + k);
      if (attrSum() >= 120) return;
      var v = parseInt(inp.value || '0', 10) + 1;
      if (v > 20) return;
      inp.value = v;
      refreshAttr();
    });
  });
  document.querySelectorAll('.stat-minus').forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.getAttribute('data-atr');
      var inp = document.getElementById('atr-' + k);
      inp.value = Math.max(0, parseInt(inp.value || '0', 10) - 1);
      refreshAttr();
    });
  });
  ATRS.forEach(function (k) { document.getElementById('atr-' + k).addEventListener('input', refreshAttr); });

  function sumOf(name) {
    var s = 0;
    document.querySelectorAll('input[name="' + name + '"]:checked').forEach(function (c) { s += parseInt(c.getAttribute('data-pts') || '0', 10); });
    return s;
  }
  function refreshBalanza() {
    document.getElementById('balanzaDotes').textContent = sumOf('dotes[]') + sumOf('defectos[]');
    document.getElementById('balanzaRasgos').textContent = sumOf('rasgos[]');
    document.querySelectorAll('input[name="rasgos[]"]').forEach(function (c) {
      var pareja = c.getAttribute('data-pareja');
      if (!pareja || pareja === '0') return;
      var p = document.querySelector('input[name="rasgos[]"][value="' + pareja + '"]');
      if (p) p.disabled = c.checked;
    });
  }
  document.querySelectorAll('input[name="dotes[]"], input[name="defectos[]"], input[name="rasgos[]"]').forEach(function (c) {
    c.addEventListener('change', refreshBalanza);
  });

  function refreshDom() {
    var s = 0;
    document.querySelectorAll('select[data-dom]').forEach(function (sel) { s += parseInt(sel.value || '0', 10); });
    document.getElementById('domPts').textContent = s;
  }
  document.querySelectorAll('select[data-dom]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var s = 0;
      document.querySelectorAll('select[data-dom]').forEach(function (x) { s += parseInt(x.value || '0', 10); });
      if (s > 2) { sel.value = '0'; }
      refreshDom();
    });
  });

  function razaSel() {
    var r = document.querySelector('input[name="raza_id"]:checked');
    return r ? +r.value : 0;
  }
  function refreshRaza() {
    var r = razaSel();
    var hib = document.getElementById('hybridChk').checked;
    document.getElementById('hybridSel').classList.toggle('dn', !hib);
    document.querySelectorAll('#dotesRaciales .bonus-chip').forEach(function (chip) {
      var chipRaza = +chip.getAttribute('data-raza');
      var soloPuro = +chip.getAttribute('data-pura') === 1;
      var visible = !hib && chipRaza === r;
      chip.style.display = visible ? '' : 'none';
      var cb = chip.querySelector('input');
      if (!visible && cb.checked) cb.checked = false;
      cb.disabled = hib && soloPuro;
    });
    var tribSel = document.getElementById('tribu_id');
    Array.prototype.forEach.call(tribSel.options, function (o) {
      var oRaza = +o.getAttribute('data-raza') || 0;
      o.disabled = hib || oRaza !== r;
    });
    if (tribSel.selectedIndex >= 0 && tribSel.options[tribSel.selectedIndex].disabled) tribSel.value = '0';
    var raceName = r ? document.querySelector('input[name="raza_id"][value="' + r + '"]').closest('.race-card').querySelector('.rc-name').textContent.trim() : 'Sin raza';
    document.getElementById('cardRace').textContent = raceName;
    var tribOpt = tribSel.options[tribSel.selectedIndex];
    var tribTxt = (tribOpt && !tribOpt.disabled && +tribSel.value > 0) ? tribOpt.textContent.split(' — ')[0].trim() : '';
    var tribeEl = document.getElementById('cardTribe');
    tribeEl.classList.toggle('dn', !tribTxt);
    tribeEl.textContent = tribTxt;
    refreshAttr();
  }
  document.querySelectorAll('input[name="raza_id"]').forEach(function (r) { r.addEventListener('change', refreshRaza); });
  document.getElementById('hybridChk').addEventListener('change', refreshRaza);
  document.getElementById('raza_hibrida_id').addEventListener('change', refreshRaza);
  document.getElementById('tribu_id').addEventListener('change', refreshRaza);

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  document.getElementById('nombre').addEventListener('input', function () {
    document.getElementById('cardName').textContent = this.value.trim() !== '' ? esc(this.value) : 'Sin nombre';
  });

  function refreshCardSec() {
    var g = function (k) { return parseInt(document.getElementById('atr-' + k).value || '0', 10); };
    var mods = razaMods();
    var fue = g('fue') + (parseInt(mods.fue || '0', 10));
    var agi = g('agi') + (parseInt(mods.agi || '0', 10));
    var pv = Math.round(100 + (g('res') + (parseInt(mods.res || '0', 10))) * 6 + fue * 2 + (g('vol') + (parseInt(mods.vol || '0', 10))) * 1 + 0.5);
    var pe = Math.round(50 + (g('vol') + (parseInt(mods.vol || '0', 10))) * 4 + (g('inte') + (parseInt(mods.inte || '0', 10))) * 3 + (g('car') + (parseInt(mods.car || '0', 10))) * 1 + 0.4);
    var pa = Math.round(6 + agi / 10 + 1 / 5);
    document.getElementById('cardPv').textContent = pv;
    document.getElementById('cardPe').textContent = pe;
    document.getElementById('cardPa').textContent = pa;
  }

  refreshAttr(); refreshBalanza(); refreshDom(); refreshRaza(); refreshCardSec();
})();
</script>
</body>
</html>
