<?php
/**
 * One Piece: 7 Seas · Trámite 6 — Producción de oficio (forja, cocina…)
 * -----------------------------------------------------------------------------
 * Ventanilla del jugador: formulario + enrutado al motor
 * (inc/ope_rol/tramites/paginas.php). Scope CSS: body.ope-pg-tramite.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramite-06.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
require_once MYBB_ROOT . 'inc/ope_rol/tramites/paginas.php';

ope7_tramite_pagina(6);
