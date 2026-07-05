<?php

namespace App\Controllers;

use App\Models\Cuenta;
use App\Models\Personaje;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CuentaController
{
    private function getCuenta(Request $request): Cuenta
    {
        $userId = $request->getAttribute('user_id');
        return Cuenta::firstOrCreate(
            ['mybb_user_id' => $userId],
            ['max_slots' => 3, 'es_narrador' => false]
        );
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function miCuenta(Request $request, Response $response): Response
    {
        $cuenta = $this->getCuenta($request);
        $cuenta->load('personajes');
        return $this->json($response, [
            'success' => true,
            'data' => [
                'id' => $cuenta->id,
                'mybb_user_id' => $cuenta->mybb_user_id,
                'max_slots' => $cuenta->max_slots,
                'slots_disponibles' => $cuenta->slotsDisponibles(),
                'es_narrador' => $cuenta->es_narrador,
                'personajes' => $cuenta->personajes->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'nombre' => $p->nombre,
                        'estado' => $p->estado,
                        'activo' => $p->activo,
                        'slot_index' => $p->slot_index,
                    ];
                }),
            ],
        ]);
    }

    public function personajeActivo(Request $request, Response $response): Response
    {
        $cuenta = $this->getCuenta($request);
        $activo = $cuenta->personajeActivo;

        if (!$activo) {
            return $this->json($response, [
                'success' => false,
                'error' => 'no_active_character',
                'data' => null,
            ], 404);
        }

        $activo->load('atributos');
        return $this->json($response, [
            'success' => true,
            'data' => $activo->toArray(),
        ]);
    }

    public function establecerActivo(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);
        $personajeId = (int) $args['personajeId'];

        $personaje = Personaje::where('id', $personajeId)
            ->where('cuenta_id', $cuenta->id)
            ->first();

        if (!$personaje) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Personaje no encontrado o no te pertenece',
            ], 404);
        }

        if ($personaje->estado !== 'aprobado') {
            return $this->json($response, [
                'success' => false,
                'error' => 'Solo personajes aprobados pueden ser el activo',
            ], 400);
        }

        // Desactivar todos, activar el seleccionado
        Personaje::where('cuenta_id', $cuenta->id)->update(['activo' => false]);
        $personaje->activo = true;
        $personaje->save();

        return $this->json($response, [
            'success' => true,
            'data' => ['personaje_id' => $personaje->id, 'nombre' => $personaje->nombre],
        ]);
    }

    public function personajeActivoPublico(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $args['userId'];
        $cuenta = Cuenta::where('mybb_user_id', $userId)->first();

        if (!$cuenta) {
            return $this->json($response, ['success' => false, 'data' => null], 404);
        }

        $activo = $cuenta->personajeActivo;

        if (!$activo) {
            return $this->json($response, ['success' => false, 'data' => null], 404);
        }

        $activo->load('atributos');
        return $this->json($response, [
            'success' => true,
            'data' => $activo->toArray(),
        ]);
    }
}
