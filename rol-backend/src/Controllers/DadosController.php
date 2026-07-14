<?php

namespace App\Controllers;

use App\Models\Tirada;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DadosController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function tirar(Request $request, Response $response): Response
    {
        $body = json_decode((string) $request->getBody(), true);

        $cantidad = max(1, min(100, (int) ($body['cantidad'] ?? 1)));
        $caras = max(2, min(1000, (int) ($body['caras'] ?? 20)));
        $modificador = (int) ($body['modificador'] ?? 0);

        $resultados = [];
        $total = 0;

        for ($i = 0; $i < $cantidad; $i++) {
            $roll = random_int(1, $caras);
            $resultados[] = $roll;
            $total += $roll;
        }

        $total += $modificador;

        $tirada = Tirada::create([
            'cantidad' => $cantidad,
            'caras' => $caras,
            'modificador' => $modificador,
            'resultados' => $resultados,
            'total' => $total,
            'tipo' => $body['tipo'] ?? 'general',
        ]);

        return $this->json($response, [
            'success' => true,
            'data' => [
                'id' => $tirada->id,
                'cantidad' => $cantidad,
                'caras' => $caras,
                'modificador' => $modificador,
                'resultados' => $resultados,
                'total' => $total,
            ],
        ]);
    }

    public function historial(Request $request, Response $response, array $args): Response
    {
        $personajeId = (int) $args['personajeId'];

        $tiradas = Tirada::where('personaje_id', $personajeId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return $this->json($response, [
            'success' => true,
            'data' => $tiradas->toArray(),
        ]);
    }
}
