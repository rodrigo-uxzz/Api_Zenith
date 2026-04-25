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

    public function configurarAgenda(Request $request)
    {
        DB::beginTransaction();

        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;
            $psicologo = Psicologo::find($id_psicologo);

            $psicologo->update([
                'duracao_consulta' => $request->duracao_consulta ?? $psicologo->duracao_consulta,
                'intervalo_consulta' => $request->intervalo_consulta ?? $psicologo->intervalo_consulta,
            ]);

            Agenda::where('id_psicologo', $id_psicologo)->delete();

            foreach ($request->agendas as $agenda) {
                Agenda::create([
                    'id_psicologo' => $id_psicologo,
                    'dia_semana' => $agenda['dia_semana'],
                    'hora_inicio' => $agenda['hora_inicio'],
                    'hora_fim' => $agenda['hora_fim'],
                    'status_agenda' => 'disponivel',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Agenda configurada com sucesso',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao configurar agenda',
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
                ->where('status_sessao', '!=', 'cancelada')
                ->where('status_sessao', '!=', 'pendente')
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

// consultasDoDia?data=2026-04-13
