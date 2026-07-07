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
        'alias',
        'concept',
        'raza',
        'clase',
        'edad',
        'historia',
        'apariencia',
        'personalidad',
        'voz',
        'motivaciones',
        'arco_narrativo',
        'avatar_url',
        'estado',
        'activo',
        'slot_index',
        'pv_restantes',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'edad' => 'integer',
        'slot_index' => 'integer',
        'voz' => 'array',
        'motivaciones' => 'array',
        'arco_narrativo' => 'array',
        'pv_restantes' => 'integer',
    ];

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function atributos()
    {
        return $this->hasMany(FichaAtributo::class, 'personaje_id');
    }

    public function stats()
    {
        return $this->hasMany(Stat::class, 'personaje_id');
    }

    public function virtudes()
    {
        return $this->hasMany(Virtud::class, 'personaje_id');
    }

    public function defectos()
    {
        return $this->hasMany(Defecto::class, 'personaje_id');
    }

    public function equipo()
    {
        return $this->hasOne(Equipo::class, 'personaje_id');
    }

    public function relaciones()
    {
        return $this->hasMany(Relacion::class, 'personaje_id');
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

    public function calcularPA(): int
    {
        $stats = $this->stats->keyBy('stat_key');
        $agi = $stats->get('agilidad')?->valor ?? 1;
        $int = $stats->get('intelecto')?->valor ?? 1;
        $ing = $stats->get('ingenio')?->valor ?? 1;
        $car = $stats->get('carisma')?->valor ?? 1;
        return $agi + max($int, $ing, $car);
    }
}
