<?php
// Front controller — punto de entrada único
require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require __DIR__ . '/../src/Database/bootstrap.php';

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(function ($request, Throwable $exception, bool $displayErrorDetails) use ($app) {
    $payload = ['error' => $displayErrorDetails ? $exception->getMessage() : 'Internal Server Error'];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
});

require __DIR__ . '/../src/Routes/api.php';

$app->run();
