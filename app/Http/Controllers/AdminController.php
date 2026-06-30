<?php

namespace App\Http\Controllers;

use App\Mail\ContaAprovadaMail;
use App\Mail\ContaReprovadaMail;
use App\Models\Paciente;
use App\Models\Psicologo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

            if (! $psicologo) {
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
            ])->withAvg('avaliacoes', 'nota')
                ->get();

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

            if (! $psicologo) {
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

            Mail::to($psicologo->usuario->email)->send(new ContaAprovadaMail($psicologo->usuario->nome));

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

            if (! $psicologo) {
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

            Mail::to($psicologo->usuario->email)->send(new ContaReprovadaMail($psicologo->usuario->nome));

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

            if (! $paciente) {
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
            $pacientes = Paciente::with([
                'usuario',
                'sessoes' => function ($query) {
                    $query->with('psicologo.usuario')
                        ->latest('data_sessao');
                },
            ])->get();

            $resultado = $pacientes->map(function ($paciente) {
                $sessaoRecente = $paciente->sessoes->first();
                $psicologo = $sessaoRecente?->psicologo ?? null;

                return [
                    'id_paciente' => $paciente->id_paciente,
                    'nome' => $paciente->usuario?->nome,
                    'cpf' => $paciente->usuario?->cpf,
                    'email' => $paciente->usuario?->email,
                    'telefone' => $paciente->usuario?->telefone,
                    'status_paciente' => $paciente->status_paciente,
                    'observacoes' => $paciente->observacoes,
                    'psicologo' => $psicologo ? [
                        'id_psicologo' => $psicologo->id_psicologo,
                        'nome' => $psicologo->usuario?->nome,
                    ] : null,
                    'ultima_sessao' => $sessaoRecente?->data_sessao,
                    'novo_este_mes' => $paciente->created_at?->isCurrentMonth(),
                ];
            });

            return response()->json([
                'dados' => [
                    'pacientes' => $resultado,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao listar pacientes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
