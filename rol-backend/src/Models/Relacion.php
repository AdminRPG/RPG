<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Relacion extends Model
{
    protected $table = 'rol_relaciones';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'personaje_id',
        'destino_personaje_id',
        'tipo',
        'descripcion',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }

    public function destino()
    {
        return $this->belongsTo(Personaje::class, 'destino_personaje_id');
    }
}
