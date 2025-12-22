<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Actualizacion extends Model
{
    protected $table = 'actualizacion';

    protected $primaryKey = 'codactualizacion'; 

    protected $fillable = [
        'inicio',
        'fin',
        'estado',
        'usuario_codusuario',
    ];

    public $timestamps = false;

   
    protected $dates = [
        'inicio',
        'fin',
    ];

    public function setInicioAttribute($value)
    {
        $this->attributes['inicio'] = Carbon::createFromTimestampMs($value);
    }

    public function setFinAttribute($value)
    {
        $this->attributes['fin'] = Carbon::createFromTimestampMs($value);
    }
}
