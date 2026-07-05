<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostPersonaje extends Model
{
    protected $table = 'rol_post_personaje';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'personaje_id',
    ];

    public function personaje()
    {
        return $this->belongsTo(Personaje::class, 'personaje_id');
    }
}
