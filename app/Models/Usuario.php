<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuario'; 
    protected $primaryKey = 'codusuario'; 
    public $incrementing = true; 
   protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'codusuario',
        'empresa_codempresa',
        'nombre',
        'clave',
        'tipo',
        'ultimoIngreso',
        'identificador'
    ];

    protected $hidden = ['clave'];

    public function getAuthPassword()
    {
        return $this->clave;
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_codempresa', 'codempresa');
    }

     public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'usuario_codusuario', 'codusuario');
    }

}
