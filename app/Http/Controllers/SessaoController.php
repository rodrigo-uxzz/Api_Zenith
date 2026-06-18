<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use App\Models\Psicologo;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Sessao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PushNotificationService;

class SessaoController extends Controller
{
    public function agendarSessao(Request $request)
    {
        DB::beginTransaction();

        try {

            $id_paciente = auth()->user()->paciente->id_paciente;

            $id_psicologo = $request->id_psicologo;
            $data_sessao = $request->data_sessao;
            $hora_inicio = $request->hora_inicio;

            $psicologo = Psicologo::find($request->id_psicologo);

            $ocupado = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', $data_sessao)
                ->where('hora_inicio', $hora_inicio)
                ->whereIn('status_sessao', [
                    'pendente',
                    'agendada',
                    'cancelamento_solicitado',
                    'reagendamento_solicitado',
                    'cancelamentoPsicologo',
                    'reagendamentoPsicologo',
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

            $hora_fim = Carbon::parse($hora_inicio)
                ->addMinutes(50)
                ->format('H:i:s');

            $sessao = Sessao::create([
                'id_psicologo' => $id_psicologo,
                'id_paciente' => $id_paciente,
                'data_sessao' => $data_sessao,
                'hora_inicio' => $hora_inicio,
                'hora_fim' => $hora_fim,
                'status_sessao' => 'pendente',
                'valor' => $psicologo->preco_sessao,
            ]);

            $pagamento = Pagamento::create([
                'id_sessao' => $sessao->id_sessao,
                'id_paciente' => $id_paciente,
                'id_psicologo' => $id_psicologo,
                'valor_total' => $psicologo->preco_sessao,
                'status_pagamento' => 'pendente',
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

            $motivo = $request->motivo;

            if (! $motivo) {
                return response()->json([
                    'error' => 'Informe o motivo do cancelamento',
                ], 400);
            }

            if ($sessao->status_sessao !== 'agendada') {
                return response()->json([
                    'error' => 'Ação não permitida para esse status',
                ], 400);
            }

            $dataSessao = Carbon::parse($sessao->data_sessao.' '.$sessao->hora_inicio);

            if (Carbon::now()->diffInHours($dataSessao, false) < 24) {
                return response()->json([
                    'error' => 'Só é possível solicitar cancelamento com no mínimo 24h de antecedência',
                ], 400);
            }

            $sessao->status_sessao = 'cancelamentoPsicologo';
            $sessao->observacoes = $motivo;
            $sessao->save();

            $paciente = Paciente::find($sessao->id_paciente);

            if ($paciente) {

                $usuario = User::find($paciente->id_usuario);

                if ($usuario) {

                    app(
                        \App\Http\Controllers\PushNotificationService::class
                    )->send(
                        $usuario,
                        'Cancelamento solicitado',
                        'O psicólogo solicitou o cancelamento da sessão.',
                        [
                            'id_sessao' => $sessao->id_sessao,
                            'tipo' => 'cancelamento'
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Solicitação de cancelamento enviada com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao solicitar cancelamento',
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

            $ocupado = Sessao::where('id_psicologo', $sessao->id_psicologo)
                ->where('data_sessao', $nova_data)
                ->where('hora_inicio', $nova_hora)
                ->where('id_sessao', '!=', $id_sessao)
                ->whereIn('status_sessao', [
                    'pendente',
                    'agendada',
                    'cancelamento_solicitado',
                    'reagendamento_solicitado',
                    'cancelamentoPsicologo',
                    'reagendamentoPsicologo',

                ])
                ->exists();

            if ($ocupado) {
                return response()->json([
                    'error' => 'Horário já ocupado',
                ], 400);
            }

            $sessao->status_sessao = 'reagendamentoPsicologo';
            $sessao->data_solicitada = $nova_data;
            $sessao->hora_solicitada = $nova_hora;
            $sessao->save();

            $paciente = Paciente::find($sessao->id_paciente);

            if ($paciente) {

                $usuario = User::find($paciente->id_usuario);

                if ($usuario) {

                    app(
                        \App\Http\Controllers\PushNotificationService::class
                    )->send(
                        $usuario,
                        'Reagendamento solicitado',
                        'O psicólogo propôs uma nova data para a sessão.',
                        [
                            'id_sessao' => $sessao->id_sessao,
                            'tipo' => 'reagendamento'
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Solicitação de reagendamento enviada com sucesso',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao solicitar reagendamento',
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

            $statusAnterior = $sessao->status_sessao;


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
                $sessao->observacoes = null;

            } elseif ($sessao->status_sessao === 'reagendamento_solicitado') {

                $sessao->data_sessao = $sessao->data_solicitada;
                $sessao->hora_inicio = $sessao->hora_solicitada;
                $sessao->hora_fim = Carbon::parse($sessao->hora_solicitada)
                    ->addMinutes(50)
                    ->format('H:i');
                $sessao->data_solicitada = null;
                $sessao->hora_solicitada = null;

                $sessao->status_sessao = 'agendada';
            } elseif ($sessao->status_sessao === 'cancelamentoPsicologo') {

                $sessao->status_sessao = 'cancelada';
                $sessao->observacoes = null;

            } elseif ($sessao->status_sessao === 'reagendamentoPsicologo') {

                $sessao->data_sessao = $sessao->data_solicitada;
                $sessao->hora_inicio = $sessao->hora_solicitada;
                $sessao->hora_fim = Carbon::parse($sessao->hora_solicitada)
                    ->addMinutes(50)
                    ->format('H:i');
                $sessao->data_solicitada = null;
                $sessao->hora_solicitada = null;

                $sessao->status_sessao = 'agendada';
            } else {
                return response()->json([
                    'error' => 'Ação não permitida para esse status',
                ], 400);
            }

            $sessao->save();

            // Sistema de Notificação não mexer!!
            $paciente = Paciente::find($sessao->id_paciente);

            if ($paciente) {

                $usuario = User::find($paciente->id_usuario);

                if ($usuario) {

                    $titulo = '';
                    $mensagem = '';

                    switch ($statusAnterior) {

                        case 'pendente':
                            $titulo = 'Sessão aprovada';
                            $mensagem = 'Sua solicitação de sessão foi aprovada.';
                            break;

                        case 'cancelamento_solicitado':
                            $titulo = 'Cancelamento aprovado';
                            $mensagem = 'Sua solicitação de cancelamento foi aprovada.';
                            break;

                        case 'reagendamento_solicitado':
                            $titulo = 'Reagendamento aprovado';
                            $mensagem = 'Sua solicitação de reagendamento foi aprovada.';
                            break;
                    }

                    app(
                        \App\Http\Controllers\PushNotificationService::class
                    )->send(
                        $usuario,
                        $titulo,
                        $mensagem,
                        [
                            'id_sessao' => $sessao->id_sessao,
                            'tipo' => $statusAnterior
                        ]
                    );
                    }
                }
            // Notificação acaba aqui

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

            } elseif ($sessao->status_sessao === 'cancelamentoPsicologo') {

                $sessao->status_sessao = 'agendada';

            } elseif ($sessao->status_sessao === 'reagendamentoPsicologo') {

                $sessao->data_solicitada = null;
                $sessao->hora_solicitada = null;

                $sessao->status_sessao = 'agendada';

            } else {
                return response()->json([
                    'error' => 'Ação não permitida para esse status',
                ], 400);
            }

            $sessao->observacoes = $motivo;
            $sessao->save();

            $paciente = Paciente::find($sessao->id_paciente);

            if ($paciente) {
                $usuario = User::find($paciente->id_usuario);

                if ($usuario) {
                    app(
                        \App\Http\Controllers\PushNotificationService::class
                    )->send(
                        $usuario,
                        'Solicitação recusada',
                        'O psicólogo recusou sua solicitação.',
                        [
                            'id_sessao' => $sessao->id_sessao
                        ]
                    );
                }
            }


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

    public function solicitarCancelamento(Request $request, $id_sessao)
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
            $motivo = $request->motivo;

            if (! $motivo) {
                return response()->json([
                    'error' => 'O motivo é obrigatório',
                ], 400);
            }

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
            $sessao->observacoes = $motivo;
            $sessao->save();

            $psicologo = Psicologo::find($sessao->id_psicologo);
            $usuario = User::find($psicologo->id_usuario);

            app(
                \App\Http\Controllers\PushNotificationService::class
            )->send(
                $usuario,
                'Cancelamento solicitado',
                'O Psicólogo solicitou o cancelamento da sessão.',
                [
                    'id_sessao' => $sessao->id_sessao
                ]
            );

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

            $psicologo = Psicologo::find($sessao->id_psicologo);
            $usuario = User::find($psicologo->id_usuario);

            app(
                \App\Http\Controllers\PushNotificationService::class
            )->send(
                $usuario,
                'Reagendamento solicitado',
                'O Psicólogo solicitou um reagendamento.',
                [
                    'id_sessao' => $sessao->id_sessao
                ]
            );

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
