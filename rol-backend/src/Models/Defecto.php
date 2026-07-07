<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Defecto extends Model
{
    protected $table = 'rol_defectos';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'personaje_id',
        'nombre',
        'tipo',
        'pv_otorgados',
        'descripcion',
        'catalogo_id',
    ];

    protected $casts = [
        'pv_otorgados' => 'integer',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
