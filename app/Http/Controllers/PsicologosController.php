<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Evento;
use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\Request;

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

            $dia_semana = date('w', strtotime($data));

            $agendas = Agenda::where('id_psicologo', $id_psicologo)
                ->where('dia_semana', $dia_semana)
                ->where('status_agenda', 'disponivel')
                ->get();

            $psicologo = Psicologo::find($id_psicologo);

            if (! $psicologo) {
                return response()->json([
                    'error' => 'Psicólogo não encontrado',
                ], 404);
            }

            $tempoConsulta = (int) $psicologo->duracao_consulta;
            $intervalo = (int) $psicologo->intervalo_consulta;
            $tempoTotal = $tempoConsulta + $intervalo;

            if ($tempoTotal <= 0) {
                return response()->json([
                    'error' => 'Configuração de tempo inválida',
                ], 400);
            }

            $horarios = [];

            foreach ($agendas as $agenda) {

                if (! $agenda->hora_inicio || ! $agenda->hora_fim) {
                    continue;
                }

                $hora_inicio = strtotime(date('H:i', strtotime($agenda->hora_inicio)));
                $hora_fim = strtotime(date('H:i', strtotime($agenda->hora_fim)));

                if (! $hora_inicio || ! $hora_fim || $hora_inicio >= $hora_fim) {
                    continue;
                }

                $guard = 0;

                while (($hora_inicio + ($tempoConsulta * 60)) <= $hora_fim) {

                    if ($guard++ > 200) {
                        break;
                    }

                    $horarios[] = date('H:i', $hora_inicio);

                    $hora_inicio = strtotime("+{$tempoTotal} minutes", $hora_inicio);

                    if (! $hora_inicio) {
                        break;
                    }
                }
            }

            $sessoes = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', $data)
                ->whereIn('status_sessao', [
                    'agendada',
                    'cancelamento_solicitado',
                    'reagendamento_solicitado',
                ])
                ->orderBy('hora_inicio')
                ->with('paciente.usuario')
                ->get()
                ->mapWithKeys(function ($sessao) {
                    return [
                        date('H:i', strtotime($sessao->hora_inicio)) => $sessao,
                    ];
                });

            $sessoesDisponiveis = [];

            foreach ($horarios as $horario) {

                $horaFormatada = date('H:i:s', strtotime($horario));

                $evento = Evento::where('id_psicologo', $id_psicologo)
                    ->where(function ($q) use ($data) {
                        $q->where(function ($q2) use ($data) {
                            $q2->whereNotNull('data_fim')
                                ->where('data_inicio', '<=', $data)
                                ->where('data_fim', '>=', $data);
                        })
                            ->orWhere(function ($q2) use ($data) {
                                $q2->whereNotNull('data_fim')
                                    ->where('data_inicio', '<=', $data)
                                    ->where('data_fim', '>=', $data);
                            })
                            ->orWhere(function ($q2) use ($data) {
                                $q2->whereNull('data_fim')
                                    ->whereDate('data_inicio', $data);
                            })
                            ->orWhere(function ($q2) {
                                $q2->where('slug', 'almoco');
                            });
                    })
                    ->where(function ($q) use ($horaFormatada) {
                        $q->whereNull('hora_inicio')
                            ->orWhere(function ($q2) use ($horaFormatada) {
                                $q2->where('hora_inicio', '<=', $horaFormatada)
                                    ->where('hora_fim', '>', $horaFormatada);
                            });
                    })
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
        try{

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
                'reagendamentos' => $reagendamentos
            ], 200);

        }catch(\Exception $e){
            return response()->json([
                'error' => 'Erro ao buscar sessões pendentes',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// consultasDoDia?data=2026-04-13
