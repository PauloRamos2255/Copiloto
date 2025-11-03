<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Segmento extends Model
{
    protected $table = 'segmento'; // nombre exacto de la tabla

    protected $primaryKey = 'codsegmento'; // clave primaria personalizada
    public $incrementing = false;          // no autoincremental
    protected $keyType = 'int';            // tipo de clave primaria

    public $timestamps = false;            // si no usas created_at/updated_at

    protected $fillable = [
        'codsegmento',
        'nombre',
        'color',
        'cordenadas',
        'bounds'
    ];

    protected $casts = [
        'cordenadas' => 'array', // automáticamente convierte JSON a array
        'bounds' => 'array',
    ];
}
