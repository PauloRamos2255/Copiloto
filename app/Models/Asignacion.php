<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'asignacion';
    protected $primaryKey = 'codasignacion';
    public $timestamps = false;

    protected $fillable = [
        'ruta_codruta',
        'usuario_codusuario',
        'ultimaActualizacion',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_codusuario', 'codusuario');
    }


    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_codruta', 'codruta');
    }

   
}
