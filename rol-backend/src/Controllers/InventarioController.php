<?php

namespace App\Controllers;

use App\Models\Item;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InventarioController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function listar(Request $request, Response $response, array $args): Response
    {
        $personajeId = (int) $args['personajeId'];

        $items = Item::where('personaje_id', $personajeId)->get();

        return $this->json($response, [
            'success' => true,
            'data' => $items->toArray(),
        ]);
    }

    public function usar(Request $request, Response $response, array $args): Response
    {
        $itemId = (int) $args['itemId'];
        $item = Item::find($itemId);

        if (!$item) {
            return $this->json($response, [
                'success' => false, 'error' => 'Item no encontrado',
            ], 404);
        }

        if ($item->cantidad <= 0) {
            return $this->json($response, [
                'success' => false, 'error' => 'No tienes este item',
            ], 400);
        }

        $item->cantidad--;
        $item->save();

        return $this->json($response, [
            'success' => true,
            'data' => $item->toArray(),
        ]);
    }

    public function agregarItem(Request $request, Response $response, array $args): Response
    {
        return $this->json($response, [
            'success' => false, 'error' => 'Not implemented',
        ], 501);
    }

    public function transferir(Request $request, Response $response): Response
    {
        return $this->json($response, [
            'success' => false, 'error' => 'Not implemented',
        ], 501);
    }
}
