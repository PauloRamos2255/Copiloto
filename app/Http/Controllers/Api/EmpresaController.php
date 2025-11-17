<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Empresa;

class EmpresaController extends Controller
{

    public function index()
    {
        $empresas = Empresa::select(
            'codempresa as id',
            'nombre',
            'observacion',
            'empresacol as empresa_col'
        )
        ->withCount('usuarios')
        ->get();

        return response()->json([
            'ok' => true,
            'empresas' => $empresas
        ]);
    }

    public function listarUsuarios($idEmpresa)
    {
        $empresa = Empresa::find($idEmpresa);

        if (!$empresa) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $usuarios = $empresa->usuarios()->get();

        return response()->json($usuarios, 200);
    }

 
    public function store(Request $request)
    {
        $request->validate([
            'nombre'        => 'required|string|max:255',
            'observacion'   => 'nullable|string',
            'empresa_col'   => 'nullable|string|max:255'
        ]);

        $empresa = DB::transaction(function () use ($request) {
            return Empresa::create([
                'nombre'      => $request->nombre,
                'observacion' => $request->observacion,
                'empresacol'  => $request->empresa_col
            ]);
        });

        return response()->json([
            'ok' => true,
            'msg' => 'Creado correctamente',
            'empresa' => [
                'id'           => $empresa->codempresa,     
                'nombre'       => $empresa->nombre,
                'observacion'  => $empresa->observacion,
                'empresa_col'  => $empresa->empresacol      
            ]
        ]);
    }



    public function update(Request $request, $id)
    {
        $empresa = Empresa::find($id);

        if (!$empresa) {
            return response()->json(['ok' => false, 'msg' => 'No existe'], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'observacion' => 'nullable|string',
            'empresa_col' => 'nullable|string|max:50'
        ]);

        DB::transaction(function () use ($request, $empresa) {
            $empresa->update([
                'nombre'       => $request->nombre,
                'observacion'  => $request->observacion,
                'empresacol'   => $request->empresa_col
            ]);
        });

        $empresa->id = $empresa->codempresa;
        $empresa->empresa_col = $empresa->empresacol;

        return response()->json([
            'ok' => true,
            'msg' => 'Actualizado correctamente',
            'empresa' => $empresa
        ]);
    }

   


    public function destroy($id)
    {
        try {
            $empresa = Empresa::findOrFail($id);

            // Verificar si tiene usuarios
            if ($empresa->usuarios()->count() > 0) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'No se puede eliminar, tiene usuarios vinculados'
                ], 400);
            }

            $empresa->delete();

            return response()->json([
                'ok' => true,
                'msg' => 'Empresa eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
