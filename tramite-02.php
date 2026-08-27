<?php
/**
 * One Piece: 7 Seas · Trámite 2 — Cierre de temas
 * -----------------------------------------------------------------------------
 * Ventanilla del jugador: formulario + enrutado al motor
 * (inc/ope_rol/tramites/paginas.php). Scope CSS: body.ope-pg-tramite.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramite-02.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
require_once MYBB_ROOT . 'inc/ope_rol/tramites/paginas.php';

ope7_tramite_pagina(2);
