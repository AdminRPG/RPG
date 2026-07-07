<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
    protected $table = 'rol_stats';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'personaje_id',
        'pilar',
        'stat_key',
        'rango',
        'valor',
        'es_mejorada',
    ];

    protected $casts = [
        'es_mejorada' => 'boolean',
        'valor' => 'integer',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }

    public static function crearPorDefecto(int $personajeId): void
    {
        $stats = [
            'cuerpo'  => ['fuerza', 'destreza', 'vigor', 'agilidad'],
            'mente'   => ['intelecto', 'ingenio', 'concentracion', 'percepcion'],
            'espiritu'=> ['carisma', 'control', 'voluntad', 'sensibilidad'],
        ];

        foreach ($stats as $pilar => $keys) {
            foreach ($keys as $key) {
                self::create([
                    'personaje_id' => $personajeId,
                    'pilar' => $pilar,
                    'stat_key' => $key,
                    'rango' => 'F',
                    'valor' => 1,
                    'es_mejorada' => false,
                ]);
            }
        }
    }
}
