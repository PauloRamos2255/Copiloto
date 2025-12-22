<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Empresa extends Model
{
    protected $table = 'empresa';
    protected $primaryKey = 'codempresa';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'observacion',
        'empresacol'
    ];


    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'empresa_codempresa', 'codempresa');
    }


}
