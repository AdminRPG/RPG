<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $table = 'rol_economia_transacciones';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = ['id'];

    protected $fillable = [
        'origen_personaje_id',
        'destino_personaje_id',
        'cantidad',
        'tipo',
        'descripcion',
        'narrador_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function origen()
    {
        return $this->belongsTo(Personaje::class, 'origen_personaje_id');
    }

    public function destino()
    {
        return $this->belongsTo(Personaje::class, 'destino_personaje_id');
    }
}
