<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Evento;
use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SessaoController extends Controller
{
    public function agendarSessao(Request $request)
    {
        DB::beginTransaction();

        try {

            $id_psicologo = $request->id_psicologo;
            $id_paciente = $request->id_paciente;
            $data_sessao = $request->data_sessao;
            $hora_inicio = $request->hora_inicio;

            $ocupado = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', $data_sessao)
                ->where('hora_inicio', $hora_inicio)
                ->where('status_sessao', '!=', 'cancelada')
                ->exists();

            if ($ocupado) {
                return response()->json([
                    'erro' => 'Horário já ocupado',
                ], 400);
            }
            $dataSessao = Carbon::parse(
                $request->data_sessao.' '.$request->hora_inicio
            );

            $agora = Carbon::now();

            if ($agora->diffInHours($dataSessao, false) < 24) {
                return response()->json([
                    'error' => 'Só é possível solicitar agendamento com no mínimo 24h de antecedência',
                ], 400);
            }

            $hora_fim = date('H:i', strtotime('+50 minutes', strtotime($hora_inicio)));

            Sessao::create([
                'id_psicologo' => $id_psicologo,
                'id_paciente' => $id_paciente,
                'data_sessao' => $data_sessao,
                'hora_inicio' => $hora_inicio,
                'hora_fim' => $hora_fim,
                'status_sessao' => 'pendente',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sessão agendada com sucesso',
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao agendar sessão',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sessaoRealizada($id_sessao)
    {
        DB::beginTransaction();

        try {
            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'error' => 'Sessão não encontrada',
                ], 404);
            }

            $sessao->status_sessao = 'realizada';
            $sessao->save();

            DB::commit();

            return response()->json([
                'message' => 'Sessão conluída com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro concluir sessão',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancelarSessao($id_sessao)
    {
        DB::beginTransaction();

        try {
            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'error' => 'Sessão não encontrada',
                ], 404);
            }
            $dataSessao = Carbon::parse(
                $sessao->data_sessao.' '.$sessao->hora_inicio
            );

            $agora = Carbon::now();

            if ($agora->diffInHours($dataSessao, false) < 24) {
                return response()->json([
                    'error' => 'Só é possível cancelar com no mínimo 24h de antecedência',
                ], 400);
            }

            $sessao->status_sessao = 'cancelada';
            $sessao->save();

            DB::commit();

            return response()->json([
                'message' => 'Sessão cancelada com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao cancelar sessão',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reagendarSessao(Request $request, $id_sessao)
    {
        DB::beginTransaction();

        try {
            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'error' => 'Sessão não encontrada',
                ], 404);
            }

            $nova_data = $request->nova_data;
            $nova_hora = $request->nova_hora;

            $ocupado = Sessao::where('id_psicologo', $sessao->id_psicologo)
                ->where('data_sessao', $nova_data)
                ->where('hora_inicio', $nova_hora)
                ->where('id_sessao', '!=', $id_sessao)
                ->where('status_sessao', '!=', 'cancelada')
                ->exists();

            if ($ocupado) {
                return response()->json([
                    'error' => 'Horário ocupado',
                ], 400);
            }

            $hora_fim = date('H:i', strtotime('+50 minutes', strtotime($nova_hora)));

            $sessao->update([
                'data_sessao' => $nova_data,
                'hora_inicio' => $nova_hora,
                'hora_fim' => $hora_fim,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sessão reagendada com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao reagendar sessão',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function detalhesConsulta($id_sessao)
    {
        try {
            $sessao = Sessao::where('id_sessao', $id_sessao)
                ->with('paciente.usuario')
                ->with('psicologo.usuario')
                ->first();

            return response()->json([
                'sessao' => $sessao,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar sessão',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function aprovarSessao($id_sessao)
    {

        DB::beginTransaction();

        try {

            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'error' => 'Consulta não encontrada',
                ], 404);
            }

            // $dataSessao = Carbon::parse(
            //     $sessao->data_sessao.' '.$sessao->hora_inicio
            // );

            // $agora = Carbon::now();

            // if ($agora->diffInHours($dataSessao, false) < 24) {
            //     return response()->json([
            //         'error' => 'Só é possível cancelar com no mínimo 24h de antecedência',
            //     ], 400);
            // }

            $sessao->status_sessao = 'agendada';
            $sessao->save();

            DB::commit();

            return response()->json([
                'message' => 'Sessão aprovada com sucesso',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao aprovar sessão',
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    public function recusarSessao($id_sessao)
    {

        DB::beginTransaction();

        try {

            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'message' => 'Sessão não encontrada',
                ], 404);
            }

            $sessao->status_sessao = 'cancelada';
            $sessao->save();

            DB::commit();

            return response()->json([
                'message' => 'Sessão recusada com sucesso',
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao recusar sessão',
                'message' => $e->getMessage(),
            ], 500);
        }

    }
}
