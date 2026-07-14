<?php

namespace App\Controllers;

use App\Models\Personaje;
use App\Models\Transaccion;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EconomiaController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function saldo(Request $request, Response $response, array $args): Response
    {
        $personajeId = (int) $args['personajeId'];
        $personaje = Personaje::find($personajeId);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false, 'error' => 'Personaje no encontrado',
            ], 404);
        }

        $ingresos = Transaccion::where('destino_personaje_id', $personajeId)->sum('cantidad');
        $egresos = Transaccion::where('origen_personaje_id', $personajeId)->sum('cantidad');
        $saldo = $ingresos - $egresos;

        return $this->json($response, [
            'success' => true,
            'data' => [
                'personaje_id' => $personajeId,
                'saldo' => $saldo,
            ],
        ]);
    }

    public function transferir(Request $request, Response $response): Response
    {
        $body = json_decode((string) $request->getBody(), true);
        $origenId = (int) ($body['origen_personaje_id'] ?? 0);
        $destinoId = (int) ($body['destino_personaje_id'] ?? 0);
        $cantidad = (int) ($body['cantidad'] ?? 0);

        if ($origenId <= 0 || $destinoId <= 0 || $cantidad <= 0) {
            return $this->json($response, [
                'success' => false, 'error' => 'Datos de transferencia invalidos',
            ], 400);
        }

        if ($origenId === $destinoId) {
            return $this->json($response, [
                'success' => false, 'error' => 'No puedes transferirte a ti mismo',
            ], 400);
        }

        $origen = Personaje::find($origenId);
        $destino = Personaje::find($destinoId);

        if (!$origen || !$destino) {
            return $this->json($response, [
                'success' => false, 'error' => 'Personaje no encontrado',
            ], 404);
        }

        $ingresos = Transaccion::where('destino_personaje_id', $origenId)->sum('cantidad');
        $egresos = Transaccion::where('origen_personaje_id', $origenId)->sum('cantidad');
        $saldo = $ingresos - $egresos;

        if ($saldo < $cantidad) {
            return $this->json($response, [
                'success' => false, 'error' => 'Saldo insuficiente',
            ], 400);
        }

        $transaccion = Transaccion::create([
            'origen_personaje_id' => $origenId,
            'destino_personaje_id' => $destinoId,
            'cantidad' => $cantidad,
            'tipo' => 'transferencia',
            'descripcion' => $body['descripcion'] ?? null,
        ]);

        return $this->json($response, [
            'success' => true,
            'data' => $transaccion->toArray(),
        ], 201);
    }

    public function historial(Request $request, Response $response, array $args): Response
    {
        $personajeId = (int) $args['personajeId'];

        $transacciones = Transaccion::where('origen_personaje_id', $personajeId)
            ->orWhere('destino_personaje_id', $personajeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->json($response, [
            'success' => true,
            'data' => $transacciones->toArray(),
        ]);
    }
}
