<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoViaje extends Model
{
    protected $table = 'historicoViaje';
    protected $primaryKey = 'codhistorioViaje';
    public $timestamps = false;

    // Constantes para estados
    public const ESTADO_INICIADO = 'I';
    public const ESTADO_FINALIZADO = 'F';
    public const ESTADO_PAUSADO = 'P';

    protected $fillable = [
        'codhistorioViaje',
        'asignacion_codAsignacion',
        'inicio',
        'fin', 
        'estado'
    ];


    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
    ];

    /**
     * Relación con la asignación
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class, 'asignacion_codAsignacion', 'codasignacion');
    }
}
