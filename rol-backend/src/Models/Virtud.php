<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Virtud extends Model
{
    protected $table = 'rol_virtudes';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'personaje_id',
        'nombre',
        'tipo',
        'coste_pv',
        'descripcion',
        'catalogo_id',
    ];

    protected $casts = [
        'coste_pv' => 'integer',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
