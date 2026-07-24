<?php
/**
 * One Piece: Eternal · Acceso directo al Catálogo de NPCs
 * Redirige a la sección de Catálogo de NPCs en Biblioteca de Lore.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'catalogo-npcs.php');
require_once './global.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
header('Location: ' . $bburl . '/biblioteca-lore.php#npcs');
exit;
