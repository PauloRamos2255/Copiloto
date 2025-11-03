<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;

class UsuarioController extends Controller{
     public function index()
    {
        return response()->json([
            'success' => true,
            'usuarios' => Usuario::all()
        ]);
    }
}