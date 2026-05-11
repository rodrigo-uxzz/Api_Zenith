<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    //  PSICOLOGOS  //

    public function verPsicologo($id_psicologo)
    {
        try {
            $psicologo = Psicologo::with([
                'usuario',
                'abordagens',
                'especialidades',
                'atendimentos',
            ])->find($id_psicologo);

            if (!$psicologo) {
                return response()->json([
                    'error' => 'Psicólogo não encontrado',
                ], 404);
            }

            return response()->json([
                'psicologo' => $psicologo,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar psicólogo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarPsicologos()
    {
        try {
            $psicologos = Psicologo::with([
                'usuario',
                'abordagens',
                'especialidades',
                'atendimentos',
            ])->paginate(10);

            return response()->json([
                'psicologos' => $psicologos,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao listar psicólogos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function aprovarPsicologo($id_psicologo)
    {
        DB::beginTransaction();

        try {
            $psicologo = Psicologo::find($id_psicologo);

            if (!$psicologo) {
                return response()->json([
                    'error' => 'Psicólogo não encontrado',
                ], 404);
            }

            if ($psicologo->status_psicologo === 'aprovado') {
                return response()->json([
                    'error' => 'Psicólogo já está aprovado',
                ], 400);
            }

            $psicologo->status_psicologo = 'aprovado';
            $psicologo->save();

            DB::commit();

            return response()->json([
                'message' => 'Psicólogo aprovado com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao aprovar psicólogo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejeitarPsicologo($id_psicologo)
    {
        DB::beginTransaction();

        try {
            $psicologo = Psicologo::find($id_psicologo);

            if (!$psicologo) {
                return response()->json([
                    'error' => 'Psicólogo não encontrado',
                ], 404);
            }

            if ($psicologo->status_psicologo === 'recusado') {
                return response()->json([
                    'error' => 'Psicólogo já está recusado',
                ], 400);
            }

            $psicologo->status_psicologo = 'recusado';
            $psicologo->save();

            DB::commit();

            return response()->json([
                'message' => 'Psicólogo recusado com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao rejeitar psicólogo',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //  PACIENTES  //

    public function verPaciente($id_usuario)
    {
        try {
            $paciente = Paciente::where('id_usuario', $id_usuario)
                ->with('usuario')
                ->first();

            if (!$paciente) {
                return response()->json([
                    'error' => 'Paciente não encontrado',
                ], 404);
            }

            return response()->json([
                'paciente' => $paciente,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar paciente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarPacientes()
    {
        try {
            $pacientes = Paciente::with('usuario')->paginate(10);

            return response()->json([
                'pacientes' => $pacientes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao listar pacientes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
