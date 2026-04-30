<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Evento;
use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PsicologosController extends Controller
{
    public function verPaciente($id)
    {
        try {
            $paciente = Paciente::where('id_usuario', $id)->first();

            if (! $paciente) {
                return response()->json([
                    'error' => 'Paciente não encontrado',
                ], 404);
            }

            $user = User::find($paciente->id_usuario);

            return response()->json([
                'user' => $user,
                'paciente' => $paciente,

            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar Paciente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarPacientes()
    {
        try {
            $pacientes = User::where('tipo_usuario', 'paciente')
                ->where('status_usuario', 'ativo')
                ->with('paciente')
                ->get();

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

    public function consultasDoDia(Request $request)
    {
        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;
            $data = $request->data;

            $dia_semana = Carbon::parse($data, 'America/Sao_Paulo')->dayOfWeek;

            $agendas = Agenda::where('id_psicologo', $id_psicologo)
                ->where('dia_semana', $dia_semana)
                ->where('status_agenda', 'disponivel')
                ->where('data_inicio_vigencia', '<=', $data)
                ->where(function ($q) use ($data) {
                    $q->whereNull('data_fim_vigencia')
                        ->orWhere('data_fim_vigencia', '>=', $data);
                })
                ->get();

            $psicologo = Psicologo::find($id_psicologo);

            $tempoConsulta = (int) $psicologo->duracao_consulta;
            $intervalo = (int) $psicologo->intervalo_consulta;
            $tempoTotal = $tempoConsulta + $intervalo;

            $horarios = [];

            foreach ($agendas as $agenda) {

                $hora_inicio = Carbon::createFromFormat('H:i:s', $agenda->hora_inicio);
                $hora_fim = Carbon::createFromFormat('H:i:s', $agenda->hora_fim);

                if (! $hora_inicio || ! $hora_fim || $hora_inicio >= $hora_fim) {
                    continue;
                }

                while (true) {

                    $fimConsulta = $hora_inicio->copy()->addMinutes($tempoConsulta);

                    if ($fimConsulta->gt($hora_fim)) {
                        break;
                    }

                    $horarios[] = $hora_inicio->format('H:i');

                    $hora_inicio->addMinutes($tempoTotal);
                }

            }

            $sessoes = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', $data)
                ->whereIn('status_sessao', [
                    'agendada',
                    'realizada',
                    'pendente',
                    'cancelamento_solicitado',
                    'reagendamento_solicitado',
                ])
                ->orderBy('hora_inicio')
                ->with('paciente.usuario')
                ->get()
                ->mapWithKeys(function ($sessao) {
                    return [
                        Carbon::parse($sessao->hora_inicio)->format('H:i') => $sessao,
                    ];
                });

            $sessoesDisponiveis = [];

            foreach ($horarios as $horario) {

                $horaFormatada = Carbon::createFromFormat('H:i', $horario)->format('H:i:s');

                $evento = Evento::where('id_psicologo', $id_psicologo)
                    ->where(function ($q) use ($data) {
                        $q->where('data_inicio', '<=', $data)
                            ->where(function ($q2) use ($data) {
                                $q2->whereNull('data_fim')
                                    ->orWhere('data_fim', '>=', $data);
                            });
                    })
                    ->where(function ($q) use ($horaFormatada) {
                        $q->whereNull('hora_inicio')
                            ->orWhere(function ($q2) use ($horaFormatada) {
                                $q2->where('hora_inicio', '<=', $horaFormatada)
                                    ->where('hora_fim', '>', $horaFormatada);
                            });
                    })
                    ->orderBy('data_inicio', 'desc')
                    ->first();

                if ($evento) {

                    $sessoesDisponiveis[] = [
                        'hora_inicio' => $horario,
                        'status_sessao' => 'bloqueado',
                        'tipo' => 'evento',
                        'slug' => $evento->slug,
                        'evento' => $evento,
                    ];

                } elseif (isset($sessoes[$horario])) {

                    $sessoesDisponiveis[] = [
                        'hora_inicio' => $sessoes[$horario]->hora_inicio,
                        'status_sessao' => $sessoes[$horario]->status_sessao,
                        'sessao' => $sessoes[$horario],
                    ];

                } else {

                    $sessoesDisponiveis[] = [
                        'hora_inicio' => $horario,
                        'status_sessao' => 'disponivel',
                        'sessao' => null,
                    ];
                }
            }

            return response()->json([
                'sessoes' => $sessoesDisponiveis,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar consultas do dia',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sessoesPendentes()
    {
        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;

            $pendentes = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'pendente')
                ->with('paciente.usuario')
                ->orderBy('data_sessao')
                ->orderBy('hora_inicio')
                ->get();

            $cancelamentos = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'cancelamento_solicitado')
                ->with('paciente.usuario')
                ->orderBy('data_sessao')
                ->orderBy('hora_inicio')
                ->get();

            $reagendamentos = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'reagendamento_solicitado')
                ->with('paciente.usuario')
                ->orderBy('data_sessao')
                ->orderBy('hora_inicio')
                ->get();

            return response()->json([
                'pendentes' => $pendentes,
                'cancelamentos' => $cancelamentos,
                'reagendamentos' => $reagendamentos,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar sessões pendentes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function historicoSessoes()
    {
        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;

            $realizadas = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'realizada')
                ->with('paciente.usuario')
                ->orderBy('data_sessao', 'desc')
                ->orderBy('hora_inicio', 'desc')
                ->get();

            $cancelamentos = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'cancelada')
                ->with('paciente.usuario')
                ->orderBy('data_sessao', 'desc')
                ->orderBy('hora_inicio', 'desc')
                ->get();

            return response()->json([
                'realizadas' => $realizadas,
                'cancelamentos' => $cancelamentos,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar histótico de sessões',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// consultasDoDia?data=2026-04-13
