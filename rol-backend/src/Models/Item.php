<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'rol_items';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = ['id'];

    protected $fillable = [
        'personaje_id',
        'nombre',
        'tipo',
        'descripcion',
        'cantidad',
        'metadata',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'metadata' => 'array',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
