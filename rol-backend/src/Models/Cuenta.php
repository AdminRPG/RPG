<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    protected $table = 'rol_cuentas';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'mybb_user_id',
        'max_slots',
        'es_narrador',
    ];

    protected $casts = [
        'es_narrador' => 'boolean',
    ];

    public function personajes()
    {
        return $this->hasMany(Personaje::class, 'cuenta_id');
    }

    public function personajeActivo()
    {
        return $this->hasOne(Personaje::class, 'cuenta_id')->where('activo', true)->where('estado', 'aprobado');
    }

    public function slotsDisponibles()
    {
        return $this->max_slots - $this->personajes()->count();
    }

    public function puedeCrearPersonaje()
    {
        return $this->slotsDisponibles() > 0;
    }
}
