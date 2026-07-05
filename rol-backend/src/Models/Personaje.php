<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personaje extends Model
{
    protected $table = 'rol_personajes';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'cuenta_id',
        'nombre',
        'raza',
        'clase',
        'edad',
        'historia',
        'avatar_url',
        'estado',
        'activo',
        'slot_index',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'edad' => 'integer',
        'slot_index' => 'integer',
    ];

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function atributos()
    {
        return $this->hasMany(FichaAtributo::class, 'personaje_id');
    }

    public function esDelUsuario(int $mybbUserId): bool
    {
        return $this->cuenta->mybb_user_id === $mybbUserId;
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAprobado($query)
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeDeCuenta($query, int $cuentaId)
    {
        return $query->where('cuenta_id', $cuentaId);
    }
}
