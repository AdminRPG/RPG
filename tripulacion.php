<?php
/**
 * I-Forge · Tripulaciones
 * Gestiona tu tripulación: crear, unirte, ver la tuya o explorar otras.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tripulacion.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);
$loggedin = $uid > 0;

// Mock data para prototipo
$mi_tripulacion = null;
$tripulaciones = [
    ['nom'=>'Sombrero de Paja','cap'=>'Monkey D. Luffy','niv'=>980,'mie'=>10,'fac'=>'pirata','lema'=>'El rey de los piratas','bandera'=>'🏴‍☠️'],
    ['nom'=>'Heart','cap'=>'Trafalgar Law','niv'=>920,'mie'=>7,'fac'=>'pirata','lema'=>'La libertad del corazón','bandera'=>'⚕️'],
    ['nom'=>'Marine HQ','cap'=>'Akainu','niv'=>1100,'mie'=>50,'fac'=>'marine','lema'=>'Justicia absoluta','bandera'=>'⚓'],
    ['nom'=>'Kuja','cap'=>'Boa Hancock','niv'=>850,'mie'=>12,'fac'=>'pirata','lema'=>'Orgullo de Amazon Lily','bandera'=>'🐍'],
    ['nom'=>'Red Hair','cap'=>'Shanks','niv'=>1050,'mie'=>9,'fac'=>'pirata','lema'=>'La tripulación más libre','bandera'=>'🦊'],
    ['nom'=>'Revolucionario Este','cap'=>'Sabo','niv'=>'??','mie'=>15,'fac'=>'revolucionario','lema'=>'Por un mundo libre','bandera'=>'🔥'],
];

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tripulaciones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tripulacion">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Tripulaciones</b></div></div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Tripulaciones</h1>
      <span class="code">// navega con los tuyos</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$mi_tripulacion): ?>
  <section class="reveal trip-join">
    <div class="trip-join-card">
      <div class="trip-join-icon">🏴‍☠️</div>
      <div class="trip-join-text">
        <h2>Aún no tienes tripulación</h2>
        <p>Navegar solo está bien, pero una buena tripulación lo cambia todo. ¿Prefieres unirte a una ya existente o fundar la tuya propia?</p>
      </div>
      <div class="trip-join-actions">
        <a href="#" class="btn btn-hot">Crear tripulación</a>
        <a href="#" class="btn btn-ghost btn-sm">Explorar tripulaciones</a>
      </div>
    </div>
  </section>
<?php endif; ?>

  <section class="reveal">
    <div class="shead" style="margin-top:18px">
      <h2 style="font-size:1.2rem">Tripulaciones activas</h2>
      <span class="code">// <?php echo count($tripulaciones); ?> registradas</span>
      <span class="rule"></span>
    </div>
    <div class="trip-grid">
<?php foreach ($tripulaciones as $t): ?>
      <div class="trip-card fac-<?php echo $t['fac']; ?>">
        <div class="trip-card-head">
          <span class="trip-card-flag"><?php echo $t['bandera']; ?></span>
          <span class="ope-tag ope-tag-<?php echo $t['fac']; ?>"><?php echo ucfirst($t['fac']); ?></span>
        </div>
        <h3 class="trip-card-nom"><?php echo $t['nom']; ?></h3>
        <div class="trip-card-cap">
          <span class="trip-card-cap-l">Capitán</span>
          <span class="trip-card-cap-n"><?php echo $t['cap']; ?></span>
        </div>
        <p class="trip-card-lema">"<?php echo $t['lema']; ?>"</p>
        <div class="trip-card-stats">
          <div class="trip-card-stat">
            <b><?php echo $t['niv']; ?></b>
            <span>Nivel</span>
          </div>
          <div class="trip-card-stat">
            <b><?php echo $t['mie']; ?></b>
            <span>Miembros</span>
          </div>
        </div>
        <button class="btn btn-ghost btn-sm" style="width:100%;margin-top:8px">Ver tripulación</button>
      </div>
<?php endforeach; ?>
    </div>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

</body>
</html>
