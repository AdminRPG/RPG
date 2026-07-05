<?php

namespace App\Controllers;

use App\Models\Personaje;
use App\Models\Cuenta;
use App\Models\PostPersonaje;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function vincularPersonaje(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $body = json_decode((string) $request->getBody(), true);

        $cuenta = Cuenta::where('mybb_user_id', $userId)->first();
        if (!$cuenta) {
            return $this->json($response, ['success' => false, 'error' => 'Cuenta no encontrada'], 404);
        }

        $personaje = Personaje::where('id', (int) ($body['personaje_id'] ?? 0))
            ->where('cuenta_id', $cuenta->id)
            ->where('activo', true)
            ->first();

        if (!$personaje) {
            return $this->json($response, ['success' => false, 'error' => 'Personaje activo no valido'], 400);
        }

        $mapping = PostPersonaje::create([
            'post_id' => (int) $body['post_id'],
            'personaje_id' => $personaje->id,
        ]);

        return $this->json($response, ['success' => true, 'data' => $mapping->toArray()], 201);
    }

    public function obtenerPersonaje(Request $request, Response $response, array $args): Response
    {
        $mapping = PostPersonaje::with('personaje.atributos')
            ->where('post_id', (int) $args['postId'])
            ->first();

        if (!$mapping) {
            return $this->json($response, ['success' => false, 'data' => null], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data' => $mapping->personaje->toArray(),
        ]);
    }
}
