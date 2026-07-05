<?php

namespace App\Controllers;

use App\Models\Cuenta;
use App\Models\Personaje;
use App\Models\FichaAtributo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FichaController
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

    public function listarMios(Request $request, Response $response): Response
    {
        $cuenta = $this->getCuenta($request);
        $personajes = Personaje::where('cuenta_id', $cuenta->id)->with('atributos')->get();

        return $this->json($response, [
            'success' => true,
            'data' => $personajes->toArray(),
        ]);
    }

    public function obtener(Request $request, Response $response, array $args): Response
    {
        $personaje = Personaje::with('atributos')->find((int) $args['id']);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Personaje no encontrado',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data' => $personaje->toArray(),
        ]);
    }

    public function crear(Request $request, Response $response): Response
    {
        $cuenta = $this->getCuenta($request);

        if (!$cuenta->puedeCrearPersonaje()) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Has alcanzado el maximo de slots disponibles',
            ], 400);
        }

        $body = json_decode((string) $request->getBody(), true);

        $existe = Personaje::where('cuenta_id', $cuenta->id)
            ->where('nombre', $body['nombre'])
            ->exists();

        if ($existe) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Ya tienes un personaje con ese nombre',
            ], 400);
        }

        $nextSlot = Personaje::where('cuenta_id', $cuenta->id)->max('slot_index') + 1;
        if ($nextSlot === null) $nextSlot = 0;

        $personaje = Personaje::create([
            'cuenta_id' => $cuenta->id,
            'nombre' => $body['nombre'],
            'raza' => $body['raza'] ?? null,
            'clase' => $body['clase'] ?? null,
            'edad' => $body['edad'] ?? null,
            'historia' => $body['historia'] ?? null,
            'avatar_url' => $body['avatar_url'] ?? null,
            'estado' => 'borrador',
            'activo' => false,
            'slot_index' => $nextSlot,
        ]);

        if (!empty($body['atributos'])) {
            foreach ($body['atributos'] as $clave => $valor) {
                FichaAtributo::create([
                    'personaje_id' => $personaje->id,
                    'clave' => $clave,
                    'valor' => (string) $valor,
                ]);
            }
        }

        return $this->json($response, [
            'success' => true,
            'data' => $personaje->fresh()->load('atributos')->toArray(),
        ], 201);
    }

    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);
        $personaje = Personaje::where('id', (int) $args['id'])
            ->where('cuenta_id', $cuenta->id)
            ->first();

        if (!$personaje) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Personaje no encontrado o no te pertenece',
            ], 404);
        }

        if ($personaje->estado !== 'borrador') {
            return $this->json($response, [
                'success' => false,
                'error' => 'Solo personajes en estado borrador pueden editarse',
            ], 400);
        }

        $body = json_decode((string) $request->getBody(), true);
        $fillable = ['nombre', 'raza', 'clase', 'edad', 'historia', 'avatar_url'];
        $updates = array_intersect_key($body, array_flip($fillable));
        $personaje->fill($updates);
        $personaje->save();

        if (!empty($body['atributos'])) {
            $personaje->atributos()->delete();
            foreach ($body['atributos'] as $clave => $valor) {
                FichaAtributo::create([
                    'personaje_id' => $personaje->id,
                    'clave' => $clave,
                    'valor' => (string) $valor,
                ]);
            }
        }

        return $this->json($response, [
            'success' => true,
            'data' => $personaje->fresh()->load('atributos')->toArray(),
        ]);
    }

    public function enviarARevision(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);
        $personaje = Personaje::where('id', (int) $args['id'])
            ->where('cuenta_id', $cuenta->id)
            ->first();

        if (!$personaje) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Personaje no encontrado o no te pertenece',
            ], 404);
        }

        if ($personaje->estado !== 'borrador') {
            return $this->json($response, [
                'success' => false,
                'error' => 'Solo personajes en borrador pueden enviarse a revision',
            ], 400);
        }

        $personaje->estado = 'pendiente';
        $personaje->save();

        return $this->json($response, [
            'success' => true,
            'data' => ['id' => $personaje->id, 'estado' => $personaje->estado],
        ]);
    }

    public function aprobar(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);
        $personaje = Personaje::find((int) $args['id']);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Personaje no encontrado',
            ], 404);
        }

        if ($personaje->estado !== 'pendiente') {
            return $this->json($response, [
                'success' => false,
                'error' => 'El personaje no esta pendiente de aprobacion',
            ], 400);
        }

        $personaje->estado = 'aprobado';
        $personaje->aprobado_por = $cuenta->mybb_user_id;
        $personaje->fecha_aprobacion = date('Y-m-d H:i:s');
        $personaje->save();

        return $this->json($response, [
            'success' => true,
            'data' => ['id' => $personaje->id, 'estado' => $personaje->estado],
        ]);
    }

    public function rechazar(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);
        $personaje = Personaje::find((int) $args['id']);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Personaje no encontrado',
            ], 404);
        }

        if ($personaje->estado !== 'pendiente') {
            return $this->json($response, [
                'success' => false,
                'error' => 'El personaje no esta pendiente de aprobacion',
            ], 400);
        }

        $personaje->estado = 'rechazado';
        $personaje->save();

        return $this->json($response, [
            'success' => true,
            'data' => ['id' => $personaje->id, 'estado' => $personaje->estado],
        ]);
    }
}
