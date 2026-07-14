<?php

use Slim\Routing\RouteCollectorProxy;

// Public routes — no auth required
$app->group('/api/v1', function (RouteCollectorProxy $group) {
    // Auth (no token needed)
    $group->post('/auth/login', 'App\Controllers\AuthController:login');
    $group->post('/auth/refresh', 'App\Controllers\AuthController:refresh');

    // Public: get active character for any user (used in postbit widget)
    $group->get('/personajes/activo/{userId}', 'App\Controllers\CuentaController:personajeActivoPublico');
});

// Auth-protected routes
$app->group('/api/v1', function (RouteCollectorProxy $group) {
    $group->post('/auth/logout', 'App\Controllers\AuthController:logout');

    // Cuenta (account-level data: slots, narrator, active character)
    $group->get('/cuenta/mi-cuenta', 'App\Controllers\CuentaController:miCuenta');
    $group->get('/cuenta/personaje-activo', 'App\Controllers\CuentaController:personajeActivo');
    $group->post('/cuenta/establecer-activo/{personajeId}', 'App\Controllers\CuentaController:establecerActivo');

    // Personajes (CRUD + approval flow)
    $group->get('/personajes/mios', 'App\Controllers\FichaController:listarMios');
    $group->get('/personajes/{id}', 'App\Controllers\FichaController:obtener');
    $group->post('/personajes', 'App\Controllers\FichaController:crear');
    $group->put('/personajes/{id}', 'App\Controllers\FichaController:actualizar');
    $group->post('/personajes/{id}/enviar', 'App\Controllers\FichaController:enviarARevision');
    $group->post('/personajes/{id}/aprobar', 'App\Controllers\FichaController:aprobar');
    $group->post('/personajes/{id}/rechazar', 'App\Controllers\FichaController:rechazar');

    // Post-character mapping
    $group->post('/posts/vincular', 'App\Controllers\PostController:vincularPersonaje');
    $group->get('/posts/{postId}/personaje', 'App\Controllers\PostController:obtenerPersonaje');

    // Inventario
    $group->get('/inventario/{personajeId}', 'App\Controllers\InventarioController:listar');
    $group->post('/inventario/{personajeId}/items', 'App\Controllers\InventarioController:agregarItem');
    $group->post('/inventario/usar/{itemId}', 'App\Controllers\InventarioController:usar');
    $group->post('/inventario/transferir', 'App\Controllers\InventarioController:transferir');

    // Economia
    $group->get('/economia/{personajeId}/saldo', 'App\Controllers\EconomiaController:saldo');
    $group->post('/economia/transferir', 'App\Controllers\EconomiaController:transferir');
    $group->get('/economia/{personajeId}/historial', 'App\Controllers\EconomiaController:historial');

    // Dados
    $group->post('/dados/tirar', 'App\Controllers\DadosController:tirar');
    $group->get('/dados/historial/{personajeId}', 'App\Controllers\DadosController:historial');
})->add(new App\Middleware\AuthMiddleware())
  ->add(new App\Middleware\RateLimitMiddleware());

// CORS middleware must be outermost — added last (Slim 4 ->add() prepends)
$app->add(new App\Middleware\CorsMiddleware());
