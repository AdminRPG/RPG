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
        'valor',
    ];

    protected $casts = [
        'valor' => 'integer',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }

    public static function crearPorDefecto(int $personajeId): void
    {
        $stats = [
            'cuerpo'   => ['FUE', 'DES', 'VIG', 'AGI'],
            'mente'    => ['INT', 'ING', 'CON', 'PER'],
            'espiritu' => ['CAR', 'CTR', 'VOL', 'SEN'],
        ];

        foreach ($stats as $pilar => $keys) {
            foreach ($keys as $key) {
                self::create([
                    'personaje_id' => $personajeId,
                    'pilar' => $pilar,
                    'stat_key' => $key,
                    'valor' => 5,
                ]);
            }
        }
    }
}
