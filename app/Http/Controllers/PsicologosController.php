<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\User;
use Illuminate\Http\Request;
use Ramsey\Collection\Exception\UnsupportedOperationException;

use function Laravel\Prompts\error;

class PsicologosController extends Controller
{
    public function verPaciente($id)
    {
        try{
            $paciente = Paciente::where('id_usuario', $id)->first();

            if(!$paciente){
                return response()->json([
                   'error' => 'Paciente não encontrado' 
                ], 404);
            }

            $user = User::find($paciente->id_usuario);

            return response()->json([
                'user' => $user,
                'paciente' => $paciente,

            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Erro ao buscar Paciente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarPacientes()
    {
        try{
            $pacientes = User::where('tipo_usuario', 'paciente')
            ->where('status_usuario', 'ativo')
            ->with('paciente')
            ->get();
            
            return response()->json([
                'pacientes' => $pacientes
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Erro ao listar pacientes',
                'message' => $e->getMessage()
            ], 500);
        }
        
    }
}
