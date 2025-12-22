<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Segmento extends Model
{
    protected $table = 'segmento'; 
    protected $primaryKey = 'codsegmento'; 
    public $incrementing = false;          
    protected $keyType = 'int';            

 
    public $timestamps = true;

    protected $fillable = [
        'codsegmento',
        'nombre',
        'color',
        'cordenadas',
        'bounds',
    ];


    protected $casts = [
        'cordenadas' => 'array',
        'bounds' => 'array',
    ];

    public function getCordenadasAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function getBoundsAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setCordenadasAttribute($value)
    {
        $this->attributes['cordenadas'] = json_encode($value);
    }

    public function setBoundsAttribute($value)
    {
        $this->attributes['bounds'] = json_encode($value);
    }
}
