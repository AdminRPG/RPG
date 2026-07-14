<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tirada extends Model
{
    protected $table = 'rol_tiradas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = ['id'];

    protected $fillable = [
        'personaje_id',
        'cantidad',
        'caras',
        'modificador',
        'resultados',
        'total',
        'tipo',
        'dificultad',
        'exito',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'caras' => 'integer',
        'modificador' => 'integer',
        'resultados' => 'array',
        'total' => 'integer',
        'dificultad' => 'integer',
        'exito' => 'boolean',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
