<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'codusuario' => 1,
            'empresa_codempresa' => 1,
            'nombre' => 'Paulo Ramos',
            'clave' => Hash::make('123456'),
            'tipo' => 'A',
        ]);
    }
}
