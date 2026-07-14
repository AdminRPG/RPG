<?php

namespace App\Services;

use App\Models\Personaje;
use App\Models\Stat;
use App\Models\Virtud;
use App\Models\Defecto;
use App\Models\Equipo;
use App\Models\Relacion;
use Illuminate\Database\Capsule\Manager as DB;

class CharacterCreationService
{
    const PUNTOS_VIRTUD_GRATIS = 6;
    const STAT_CAP_CREACION = 20;
    const STAT_MINIMO = 5;
    const PS_DEFAULT = 30;
    const PS_HUMANO = 40;

    const PILARES = ['cuerpo', 'mente', 'espiritu'];
    const STATS = [
        'cuerpo'   => ['FUE', 'DES', 'VIG', 'AGI'],
        'mente'    => ['INT', 'ING', 'CON', 'PER'],
        'espiritu' => ['CAR', 'CTR', 'VOL', 'SEN'],
    ];

    /**
     * Valida y crea un personaje completo con todos sus datos asociados.
     * @return array{success: bool, personaje?: Personaje, errors?: string[]}
     */
    public function crear(array $data, int $cuentaId): array
    {
        $errors = $this->validarDatos($data);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            DB::beginTransaction();

            $personaje = $this->crearPersonaje($data, $cuentaId);
            $this->crearStats($personaje->id, $data['stats'] ?? []);
            $this->crearVirtudes($personaje->id, $data['virtudes'] ?? []);
            $this->crearDefectos($personaje->id, $data['defectos'] ?? []);
            $this->crearEquipo($personaje->id, $data['equipo_inicial'] ?? []);
            $this->crearRelaciones($personaje->id, $data['relaciones'] ?? []);

            $pvRestantes = $this->calcularPVRestantes(
                $data['defectos'] ?? [],
                $data['virtudes'] ?? []
            );
            $personaje->pv_restantes = $pvRestantes;
            $personaje->save();

            DB::commit();

            return [
                'success' => true,
                'personaje' => $personaje->fresh()->load([
                    'stats', 'virtudes', 'defectos', 'equipo', 'relaciones',
                ]),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            error_log('CharacterCreationService error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Error interno al crear el personaje. Intentalo de nuevo.']];
        }
    }

    public function validarDatos(array $data): array
    {
        $errors = [];

        if (empty($data['nombre'] ?? '')) {
            $errors[] = 'El nombre del personaje es obligatorio.';
        }

        $this->validarStats($data['stats'] ?? [], $errors);
        $this->validarVirtudesYDefectos($data['defectos'] ?? [], $data['virtudes'] ?? [], $errors);

        return $errors;
    }

    public function validarStats(array $stats, array &$errors): void
    {
        $statsKeys = array_keys($stats);
        $expected = [];
        foreach (self::STATS as $pilar => $keys) {
            foreach ($keys as $k) {
                $expected[] = $k;
            }
        }

        $diff = array_diff($expected, $statsKeys);
        if (!empty($diff)) {
            $errors[] = 'Faltan stats requeridas: ' . implode(', ', $diff);
        }

        foreach ($stats as $key => $stat) {
            $valor = $stat['valor'] ?? $stat;
            if (!is_numeric($valor)) {
                $errors[] = "Stat '$key': valor no numerico.";
                continue;
            }

            $valor = (int) $valor;
            if ($valor < self::STAT_MINIMO) {
                $errors[] = "Stat '$key': valor {$valor} menor que el minimo (" . self::STAT_MINIMO . ").";
            }

            if ($valor > self::STAT_CAP_CREACION) {
                $errors[] = "Stat '$key': {$valor} supera el maximo de creacion (" . self::STAT_CAP_CREACION . ").";
            }
        }
    }

    public function validarVirtudesYDefectos(array $defectos, array $virtudes, array &$errors): void
    {
        $totalPV = self::PUNTOS_VIRTUD_GRATIS;
        $gastado = 0;

        foreach ($defectos as $d) {
            $pv = (int) ($d['pv_otorgados'] ?? 0);
            if ($pv <= 0) {
                $errors[] = "Defecto '{$d['nombre']}': pv_otorgados debe ser positivo.";
            }
            $totalPV += $pv;
        }

        foreach ($virtudes as $v) {
            $coste = (int) ($v['coste_pv'] ?? 0);
            if ($coste <= 0) {
                $errors[] = "Virtud '{$v['nombre']}': coste_pv debe ser positivo.";
            }
            $gastado += $coste;
        }

        if ($gastado > $totalPV) {
            $errors[] = "No tienes suficientes PV. Disponibles: $totalPV, gastados: $gastado.";
        }
    }

    public function crearPersonaje(array $data, int $cuentaId): Personaje
    {
        return Personaje::create([
            'cuenta_id' => $cuentaId,
            'nombre' => $data['nombre'],
            'alias' => $data['alias'] ?? null,
            'concept' => $data['concept'] ?? null,
            'raza' => $data['raza'] ?? null,
            'clase' => $data['clase'] ?? null,
            'edad' => $data['edad'] ?? null,
            'historia' => $data['historia']['pasado']['texto_completo'] ?? $data['historia'] ?? null,
            'apariencia' => isset($data['apariencia']) ? json_encode($data['apariencia']) : null,
            'personalidad' => isset($data['personalidad']) ? json_encode($data['personalidad']) : null,
            'voz' => isset($data['voz']) ? json_encode($data['voz']) : null,
            'motivaciones' => isset($data['motivaciones']) ? json_encode($data['motivaciones']) : null,
            'arco_narrativo' => isset($data['arco_narrativo']) ? json_encode($data['arco_narrativo']) : null,
            'avatar_url' => $data['avatar_url'] ?? null,
            'estado' => 'borrador',
            'activo' => false,
            'slot_index' => $this->nextSlotIndex($cuentaId),
        ]);
    }

    public function crearStats(int $personajeId, array $stats): void
    {
        if (empty($stats)) {
            Stat::crearPorDefecto($personajeId);
            return;
        }

        foreach ($stats as $key => $stat) {
            $pilar = $this->findPilarByStatKey($key);
            $valor = is_array($stat) ? (int) ($stat['valor'] ?? 5) : (int) $stat;

            Stat::create([
                'personaje_id' => $personajeId,
                'pilar' => $pilar,
                'stat_key' => $key,
                'valor' => $valor,
            ]);
        }
    }

    public function crearVirtudes(int $personajeId, array $virtudes): void
    {
        foreach ($virtudes as $v) {
            Virtud::create([
                'personaje_id' => $personajeId,
                'nombre' => $v['nombre'],
                'tipo' => $v['tipo'],
                'coste_pv' => $v['coste_pv'],
                'descripcion' => $v['descripcion'] ?? null,
                'catalogo_id' => $v['id'] ?? null,
            ]);
        }
    }

    public function crearDefectos(int $personajeId, array $defectos): void
    {
        foreach ($defectos as $d) {
            Defecto::create([
                'personaje_id' => $personajeId,
                'nombre' => $d['nombre'],
                'tipo' => $d['tipo'],
                'pv_otorgados' => $d['pv_otorgados'],
                'descripcion' => $d['descripcion'] ?? null,
                'catalogo_id' => $d['id'] ?? null,
            ]);
        }
    }

    public function crearEquipo(int $personajeId, array $equipo): void
    {
        Equipo::create([
            'personaje_id' => $personajeId,
            'arma_basica' => !empty($equipo['arma_basica']) ? json_encode($equipo['arma_basica']) : null,
            'objeto_personal' => !empty($equipo['objeto_personal']) ? json_encode($equipo['objeto_personal']) : null,
            'ropa_pertenencias' => !empty($equipo['ropa_pertenencias']) ? json_encode($equipo['ropa_pertenencias']) : null,
            'moneda' => !empty($equipo['moneda']) ? json_encode($equipo['moneda']) : null,
        ]);
    }

    public function crearRelaciones(int $personajeId, array $relaciones): void
    {
        foreach ($relaciones as $r) {
            Relacion::create([
                'personaje_id' => $personajeId,
                'destino_personaje_id' => $r['destino_personaje_id'],
                'tipo' => $r['tipo'],
                'descripcion' => $r['descripcion'] ?? null,
            ]);
        }
    }

    public function calcularPVRestantes(array $defectos, array $virtudes): int
    {
        $total = self::PUNTOS_VIRTUD_GRATIS;

        foreach ($defectos as $d) {
            $total += (int) ($d['pv_otorgados'] ?? 0);
        }

        foreach ($virtudes as $v) {
            $total -= (int) ($v['coste_pv'] ?? 0);
        }

        return max(0, $total);
    }

    public function findPilarByStatKey(string $key): string
    {
        foreach (self::STATS as $pilar => $keys) {
            if (in_array($key, $keys, true)) {
                return $pilar;
            }
        }
        throw new \InvalidArgumentException("Unknown stat key: '$key'");
    }

    public function nextSlotIndex(int $cuentaId): int
    {
        $max = Personaje::where('cuenta_id', $cuentaId)->max('slot_index');
        return ($max === null) ? 0 : $max + 1;
    }
}
