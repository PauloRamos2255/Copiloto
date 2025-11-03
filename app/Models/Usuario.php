<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuario'; // <- nombre correcto de la tabla
    protected $primaryKey = 'codusuario'; // ⚠️ reemplaza por tu clave primaria real
    public $incrementing = false; // si no es autoincremental
    protected $keyType = 'string'; // o 'int' según el tipo

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
}
