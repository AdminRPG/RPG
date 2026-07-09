<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-npc.php');
require_once './global.php';
$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca NPC</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca NPC</b></div></div>
<div class="wrap"><section class="reveal"><div class="shead"><h1>Biblioteca NPC</h1><span class="code">// personajes no jugadores</span><span class="rule"></span></div></section>
<section class="reveal"><div class="plate"><div class="plate-b"><p class="bib-empty">Próximamente.</p></div></div></section></div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
</body></html>