<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    // Nombre de la tabla
    protected $table = 'ruta';

    // Clave primaria personalizada
    protected $primaryKey = 'codruta';

    public $incrementing = true;
    protected $keyType = 'int';

    // Desactivar timestamps automáticos
    public $timestamps = false;

    // Campos que se pueden llenar masivamente (nombres exactos de la BD)
    protected $fillable = [
        'nombre',
        'limiteGeneral',
        'fechaCreacion',
        'icono',
        'tipo',
    ];

    // Relación con DetalleRuta


   public function detallesRuta()
{
    return $this->hasMany(DetalleRuta::class, 'ruta_codruta', 'codruta');
}

    
}