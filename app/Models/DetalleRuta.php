<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleRuta extends Model
{
    protected $table = 'detalleRuta';
    protected $primaryKey = 'iddetalleRuta';
    public $timestamps = true; // Usa created_at y updated_at

    protected $fillable = [
        'ruta_codruta',
        'segmento_codsegmento',
        'velocidadPermitida',
        'mensaje',
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_codruta', 'codruta');
    }

    public function segmento()
    {
        return $this->belongsTo(Segmento::class, 'segmento_codsegmento', 'codsegmento');
    }
}