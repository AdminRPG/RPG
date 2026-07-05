<?php
// Front controller — punto de entrada único
require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

require __DIR__ . '/../src/Routes/api.php';

$app->run();
