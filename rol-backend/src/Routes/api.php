<?php

use Slim\Routing\RouteCollectorProxy;

$app->group('/api/v1', function (RouteCollectorProxy $group) {
    // Auth
    $group->post('/auth/refresh', 'App\Controllers\AuthController:refresh');

    // Personajes
    $group->get('/personajes/mios', 'App\Controllers\FichaController:listarMios');
    $group->get('/personajes/{id}', 'App\Controllers\FichaController:obtener');
    $group->post('/personajes', 'App\Controllers\FichaController:crear');
    $group->put('/personajes/{id}', 'App\Controllers\FichaController:actualizar');
    $group->post('/personajes/{id}/enviar', 'App\Controllers\FichaController:enviarARevision');
    $group->post('/personajes/{id}/aprobar', 'App\Controllers\FichaController:aprobar');
    $group->post('/personajes/{id}/rechazar', 'App\Controllers\FichaController:rechazar');

    // Inventario
    $group->get('/inventario/{personajeId}', 'App\Controllers\InventarioController:listar');
    $group->post('/inventario/{personajeId}/items', 'App\Controllers\InventarioController:agregarItem');
    $group->post('/inventario/transferir', 'App\Controllers\InventarioController:transferir');

    // Economía
    $group->get('/economia/{personajeId}/saldo', 'App\Controllers\EconomiaController:saldo');
    $group->post('/economia/transferir', 'App\Controllers\EconomiaController:transferir');
    $group->get('/economia/{personajeId}/historial', 'App\Controllers\EconomiaController:historial');

    // Dados
    $group->post('/dados/tirar', 'App\Controllers\DadosController:tirar');
    $group->get('/dados/historial/{hiloId}', 'App\Controllers\DadosController:historial');
})->add(new App\Middleware\AuthMiddleware())
  ->add(new App\Middleware\RateLimitMiddleware())
  ->add(new App\Middleware\CorsMiddleware());
