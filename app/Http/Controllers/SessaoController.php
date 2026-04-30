<?php

namespace App\Http\Controllers;

use App\Models\Sessao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
                ->whereIn('status_sessao', [
                    'pendente',
                    'agendada',
                    'cancelamento_solicitado',
                    'reagendamento_solicitado',
                ])
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

            if ($dataSessao->isPast()) {
                return response()->json([
                    'error' => 'Não é possível agendar para uma data passada',
                ], 400);
            }

            if ($agora->diffInHours($dataSessao, false) < 24) {
                return response()->json([
                    'error' => 'Só é possível solicitar agendamento com no mínimo 24h de antecedência',
                ], 400);
            }

            $hora_fim = Carbon::createFromFormat('H:i:s', $hora_inicio)
                ->addMinutes(50)
                ->format('H:i:s');

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

    public function cancelarSessao(Request $request, $id_sessao)
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

            $motivo = $request->motivo;

            if (! $motivo) {
                return response()->json([
                    'error' => 'Informe o motivo do cancelamento',
                ], 400);
            }

            $sessao->observacoes = $motivo;

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

            $novaDataSessao = Carbon::parse($nova_data.' '.$nova_hora);

            if ($novaDataSessao->isPast()) {
                return response()->json([
                    'error' => 'Data inválida',
                ], 400);
            }

            if (Carbon::now()->diffInHours($novaDataSessao, false) < 24) {
                return response()->json([
                    'error' => 'Reagendamento precisa de 24h de antecedência',
                ], 400);
            }

            $ocupado = Sessao::where('id_psicologo', $sessao->id_psicologo)
                ->where('data_sessao', $nova_data)
                ->where('hora_inicio', $nova_hora)
                ->where('id_sessao', '!=', $id_sessao)
                ->whereIn('status_sessao', [
                    'pendente',
                    'agendada',
                    'cancelamento_solicitado',
                    'reagendamento_solicitado',
                ])
                ->exists();

            if ($ocupado) {
                return response()->json([
                    'error' => 'Horário ocupado',
                ], 400);
            }

            $hora_fim = Carbon::createFromFormat('H:i', $nova_hora)
                ->addMinutes(50)
                ->format('H:i');

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

            $dataSessao = Carbon::parse(
                $sessao->data_sessao.' '.$sessao->hora_inicio
            );

            if ($dataSessao->isPast()) {
                return response()->json([
                    'error' => 'Não é possível aprovar uma sessão que já passou',
                ], 400);
            }

            if ($sessao->status_sessao === 'pendente') {

                $sessao->status_sessao = 'agendada';

            } elseif ($sessao->status_sessao === 'cancelamento_solicitado') {

                $sessao->status_sessao = 'cancelada';

            } elseif ($sessao->status_sessao === 'reagendamento_solicitado') {

                $sessao->data_sessao = $sessao->data_solicitada;
                $sessao->hora_inicio = $sessao->hora_solicitada;
                $sessao->hora_fim = Carbon::createFromFormat('H:i', $sessao->hora_solicitada)
                    ->addMinutes(50)
                    ->format('H:i');
                $sessao->data_solicitada = null;
                $sessao->hora_solicitada = null;

                $sessao->status_sessao = 'agendada';
            }

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

    public function recusarSessao(Request $request, $id_sessao)
    {

        DB::beginTransaction();

        try {

            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'message' => 'Sessão não encontrada',
                ], 404);
            }

            $motivo = $request->motivo;

            if (! $motivo) {
                return response()->json([
                    'error' => 'O motivo é obrigatório',
                ], 400);
            }

            if ($sessao->status_sessao === 'pendente') {

                $sessao->status_sessao = 'recusada';

            } elseif ($sessao->status_sessao === 'cancelamento_solicitado') {

                $sessao->status_sessao = 'agendada';

            } elseif ($sessao->status_sessao === 'reagendamento_solicitado') {

                $sessao->data_solicitada = null;
                $sessao->hora_solicitada = null;

                $sessao->status_sessao = 'agendada';

            }

            $sessao->observacoes = $motivo;
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

    public function solicitarCancelamento($id_sessao)
    {

        DB::beginTransaction();

        try {

            $sessao = Sessao::find($id_sessao);
            if (! $sessao) {
                return response()->json([
                    'error' => 'Sessão não encontrada',
                ], 404);
            }

            $id_paciente = auth()->user()->paciente->id_paciente;

            if ($sessao->id_paciente != $id_paciente) {
                return response()->json([
                    'error' => 'Não autorizado',
                ], 403);
            }

            if ($sessao->status_sessao !== 'agendada') {
                return response()->json([
                    'error' => 'Ação não permitida para esse status',
                ], 400);
            }

            $sessao->status_sessao = 'cancelamento_solicitado';
            $sessao->save();

            DB::commit();

            return response()->json([
                'message' => 'Solicitação enviada',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao solicitar cancelamento',
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    public function solicitarReagendamento(Request $request, $id_sessao)
    {

        DB::beginTransaction();

        try {

            $sessao = Sessao::find($id_sessao);

            if (! $sessao) {
                return response()->json([
                    'error' => 'Sessão não encontrada',
                ], 404);
            }

            $id_paciente = auth()->user()->paciente->id_paciente;

            if ($sessao->id_paciente != $id_paciente) {
                return response()->json([
                    'error' => 'Não autorizado',
                ], 403);
            }

            if ($sessao->status_sessao !== 'agendada') {
                return response()->json([
                    'error' => 'Ação não permitida para esse status',
                ], 400);
            }

            $nova_data = $request->nova_data;
            $nova_hora = $request->nova_hora;

            $novaDataSessao = Carbon::parse($nova_data.' '.$nova_hora);

            if ($novaDataSessao->isPast()) {
                return response()->json([
                    'error' => 'Data inválida',
                ], 400);
            }

            if (Carbon::now()->diffInHours($novaDataSessao, false) < 24) {
                return response()->json([
                    'error' => 'Reagendamento precisa de 24h de antecedência',
                ], 400);
            }

            $sessao->status_sessao = 'reagendamento_solicitado';
            $sessao->data_solicitada = $nova_data;
            $sessao->hora_solicitada = $nova_hora;
            $sessao->save();

            DB::commit();

            return response()->json([
                'message' => 'Solicitação enviada',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao solicitar agendamento',
                'message' => $e->getMessage(),
            ], 500);

        }

    }
}
