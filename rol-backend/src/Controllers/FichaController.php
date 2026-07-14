<?php

namespace App\Controllers;

use App\Models\Cuenta;
use App\Models\Personaje;
use App\Models\Stat;
use App\Models\Virtud;
use App\Models\Defecto;
use App\Models\Equipo;
use App\Models\Relacion;
use App\Models\FichaAtributo;
use App\Services\CharacterCreationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FichaController
{
    private const LOAD_RELATIONS = ['stats', 'virtudes', 'defectos', 'equipo', 'relaciones.destino', 'atributos'];

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
        $personajes = Personaje::where('cuenta_id', $cuenta->id)
            ->with(self::LOAD_RELATIONS)
            ->get()
            ->each(fn ($p) => $p->setAttribute('pa_calculado', $p->calcularPA()));

        return $this->json($response, ['success' => true, 'data' => $personajes->toArray()]);
    }

    public function obtener(Request $request, Response $response, array $args): Response
    {
        $personaje = Personaje::with(self::LOAD_RELATIONS)->find((int) $args['id']);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false, 'error' => 'Personaje no encontrado',
            ], 404);
        }

        $personaje->setAttribute('pa_calculado', $personaje->calcularPA());

        return $this->json($response, ['success' => true, 'data' => $personaje->toArray()]);
    }

    public function crear(Request $request, Response $response): Response
    {
        $cuenta = $this->getCuenta($request);

        if (!$cuenta->puedeCrearPersonaje()) {
            return $this->json($response, [
                'success' => false, 'error' => 'Has alcanzado el maximo de slots disponibles',
            ], 400);
        }

        $body = json_decode((string) $request->getBody(), true);

        $existe = Personaje::where('cuenta_id', $cuenta->id)
            ->where('nombre', $body['nombre'] ?? '')
            ->exists();

        if ($existe) {
            return $this->json($response, [
                'success' => false, 'error' => 'Ya tienes un personaje con ese nombre',
            ], 400);
        }

        $service = new CharacterCreationService();
        $result = $service->crear($body, $cuenta->id);

        if (!$result['success']) {
            return $this->json($response, [
                'success' => false, 'errors' => $result['errors'],
            ], 400);
        }

        $personaje = $result['personaje'];
        $personaje->setAttribute('pa_calculado', $personaje->calcularPA());

        return $this->json($response, [
            'success' => true, 'data' => $personaje->toArray(),
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
                'success' => false, 'error' => 'Personaje no encontrado o no te pertenece',
            ], 404);
        }

        if ($personaje->estado !== 'borrador') {
            return $this->json($response, [
                'success' => false, 'error' => 'Solo personajes en borrador pueden editarse',
            ], 400);
        }

        $body = json_decode((string) $request->getBody(), true);

        $service = new CharacterCreationService();
        $errors = $service->validarDatos($body);
        if (!empty($errors)) {
            return $this->json($response, ['success' => false, 'errors' => $errors], 400);
        }

        $this->actualizarPersonaje($personaje, $body);
        $this->reemplazarStats($personaje->id, $body['stats'] ?? []);
        $this->reemplazarVirtudes($personaje->id, $body['virtudes'] ?? []);
        $this->reemplazarDefectos($personaje->id, $body['defectos'] ?? []);
        $this->reemplazarEquipo($personaje->id, $body['equipo_inicial'] ?? []);
        $this->reemplazarRelaciones($personaje->id, $body['relaciones'] ?? []);

        $personaje->save();
        $personaje->setAttribute('pa_calculado', $personaje->calcularPA());

        return $this->json($response, [
            'success' => true, 'data' => $personaje->fresh()->load(self::LOAD_RELATIONS)->toArray(),
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
                'success' => false, 'error' => 'Personaje no encontrado o no te pertenece',
            ], 404);
        }

        if ($personaje->estado !== 'borrador') {
            return $this->json($response, [
                'success' => false, 'error' => 'Solo personajes en borrador pueden enviarse a revision',
            ], 400);
        }

        $personaje->estado = 'pendiente';
        $personaje->save();

        return $this->json($response, [
            'success' => true, 'data' => ['id' => $personaje->id, 'estado' => $personaje->estado],
        ]);
    }

    public function aprobar(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);

        if (!$cuenta->es_narrador) {
            return $this->json($response, [
                'success' => false, 'error' => 'No tienes permisos para aprobar personajes',
            ], 403);
        }

        $personaje = Personaje::find((int) $args['id']);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false, 'error' => 'Personaje no encontrado',
            ], 404);
        }

        if ($personaje->estado !== 'pendiente') {
            return $this->json($response, [
                'success' => false, 'error' => 'El personaje no esta pendiente de aprobacion',
            ], 400);
        }

        $personaje->estado = 'aprobado';
        $personaje->aprobado_por = $cuenta->mybb_user_id;
        $personaje->fecha_aprobacion = date('Y-m-d H:i:s');
        $personaje->save();

        return $this->json($response, [
            'success' => true, 'data' => ['id' => $personaje->id, 'estado' => $personaje->estado],
        ]);
    }

    public function rechazar(Request $request, Response $response, array $args): Response
    {
        $cuenta = $this->getCuenta($request);

        if (!$cuenta->es_narrador) {
            return $this->json($response, [
                'success' => false, 'error' => 'No tienes permisos para rechazar personajes',
            ], 403);
        }

        $personaje = Personaje::find((int) $args['id']);

        if (!$personaje) {
            return $this->json($response, [
                'success' => false, 'error' => 'Personaje no encontrado',
            ], 404);
        }

        if ($personaje->estado !== 'pendiente') {
            return $this->json($response, [
                'success' => false, 'error' => 'El personaje no esta pendiente de aprobacion',
            ], 400);
        }

        $personaje->estado = 'rechazado';
        $personaje->save();

        return $this->json($response, [
            'success' => true, 'data' => ['id' => $personaje->id, 'estado' => $personaje->estado],
        ]);
    }

    // --- Helpers de actualización ---

    private function actualizarPersonaje(Personaje $p, array $data): void
    {
        $fillable = ['nombre', 'alias', 'concept', 'raza', 'clase', 'edad', 'avatar_url'];
        $updates = array_intersect_key($data, array_flip($fillable));

        if (isset($data['historia'])) {
            $updates['historia'] = is_array($data['historia'])
                ? ($data['historia']['pasado']['texto_completo'] ?? json_encode($data['historia']))
                : $data['historia'];
        }
        if (isset($data['apariencia'])) $updates['apariencia'] = is_string($data['apariencia']) ? $data['apariencia'] : json_encode($data['apariencia']);
        if (isset($data['personalidad'])) $updates['personalidad'] = is_string($data['personalidad']) ? $data['personalidad'] : json_encode($data['personalidad']);
        if (isset($data['voz'])) $updates['voz'] = json_encode($data['voz']);
        if (isset($data['motivaciones'])) $updates['motivaciones'] = json_encode($data['motivaciones']);
        if (isset($data['arco_narrativo'])) $updates['arco_narrativo'] = json_encode($data['arco_narrativo']);

        $p->fill($updates);
    }

    private function reemplazarStats(int $personajeId, array $stats): void
    {
        if (empty($stats)) return;
        Stat::where('personaje_id', $personajeId)->delete();
        (new CharacterCreationService())->crearStats($personajeId, $stats);
    }

    private function reemplazarVirtudes(int $personajeId, array $virtudes): void
    {
        Virtud::where('personaje_id', $personajeId)->delete();
        (new CharacterCreationService())->crearVirtudes($personajeId, $virtudes);
    }

    private function reemplazarDefectos(int $personajeId, array $defectos): void
    {
        Defecto::where('personaje_id', $personajeId)->delete();
        (new CharacterCreationService())->crearDefectos($personajeId, $defectos);
    }

    private function reemplazarEquipo(int $personajeId, array $equipo): void
    {
        Equipo::where('personaje_id', $personajeId)->delete();
        (new CharacterCreationService())->crearEquipo($personajeId, $equipo);
    }

    private function reemplazarRelaciones(int $personajeId, array $relaciones): void
    {
        Relacion::where('personaje_id', $personajeId)->delete();
        (new CharacterCreationService())->crearRelaciones($personajeId, $relaciones);
    }
}
