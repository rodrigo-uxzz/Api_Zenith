<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Psicologo;
use App\Models\User;

class PacienteController extends Controller
{
    public function verPsicologo($id)
    {

        try{
            $psicolgo = Psicologo::where('id_usuario', $id)
            ->where('status_psicologo', 'aprovado')
            ->with([
                'abordagens',
                'especialidades',
                'atendimentos',
            ])
            ->first();

            if (!$psicolgo) {
                return response()->json([
                    'error' => 'Psicólogo não encontrado'
                ], 404);
            }

            $user = User::find($psicolgo->id_usuario);

            return response()->json([
                'user' => $user,
                'psicologo' => $psicolgo
            ], 200);
            
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Erro ao buscar psicólogo',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function listarPsicologos()
    {
        try {
            $psicologos = User::where('tipo_usuario', 'psicologo')
            ->where('status_usuario', 'ativo')
            ->with([
                'psicologo',
                'psicologo.abordagens',
                'psicologo.especialidades',
                'psicologo.atendimentos',
            ])
            ->whereHas('psicologo', function ($query) {
                $query->where('status_psicologo', 'aprovado');
            })
            ->get();

            return response()->json([
                'psicologos' => $psicologos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao listar psicólogos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
