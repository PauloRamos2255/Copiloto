<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    protected $table = 'evento';
    protected $primaryKey = 'codevento';
    public $timestamps = false;

    protected $fillable = [
        'codevento',
        'nombre',
        'inicio',
        'fin',
        'tipo',
        'detalle',
        'historicoViaje_codhistorioViaje'
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fin' => 'datetime',
    ];

    /**
     * Relación con Histórico de Viaje
     */
    public function historicoViaje(): BelongsTo
    {
        return $this->belongsTo(
            HistoricoViaje::class,
            'historicoViaje_codhistorioViaje',
            'codhistorioViaje'
        );
    }
}
