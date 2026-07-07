<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'rol_equipo';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'personaje_id',
        'arma_basica',
        'objeto_personal',
        'ropa_pertenencias',
        'moneda',
    ];

    protected $casts = [
        'arma_basica' => 'array',
        'objeto_personal' => 'array',
        'ropa_pertenencias' => 'array',
        'moneda' => 'array',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
