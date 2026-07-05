<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaAtributo extends Model
{
    protected $table = 'rol_ficha_atributos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'personaje_id',
        'clave',
        'valor',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
