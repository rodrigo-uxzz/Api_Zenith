<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Especialidade;
use App\Models\Evento;
use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

            $psicologo = auth()->user()->psicologo;

            $pacientes = User::where('tipo_usuario', 'paciente')
                ->where('status_usuario', 'ativo')
                ->whereHas('paciente.sessoes', function ($query) use ($psicologo) {
                    $query->where('id_psicologo', $psicologo->id_psicologo);
                })
                ->with([
                    'paciente',
                    'paciente.sessoes',
                    'paciente.sessoes.pagamento', 

                ])
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

            $dia_semana = Carbon::parse($data)->dayOfWeek;

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

                $hora_inicio = Carbon::parse($agenda->hora_inicio);
                $hora_fim = Carbon::parse($agenda->hora_fim);

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
                    'cancelamentoPsicologo',
                    'reagendamentoPsicologo',
                ])
                ->orderBy('hora_inicio')
                ->with('paciente.usuario')
                ->with('psicologo')
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

                    $sessao = $sessoes[$horario];
                    $link = $sessao->link_sessao ?: $sessao->psicologo->link_consulta;

                    $sessoesDisponiveis[] = [
                        'hora_inicio' => $sessao->hora_inicio,
                        'status_sessao' => $sessao->status_sessao,
                        'link' => $link,
                        'sessao' => $sessao,
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

    public function consultasDaSemana(Request $request)
    {
        try {
            $id_psicologo = auth()->user()->psicologo->id_psicologo;
            $data = $request->data ?? now()->toDateString();
            $dataCarbon = Carbon::parse($data);

            $inicioSemana = $dataCarbon->copy()->startOfWeek(Carbon::SUNDAY);
            $fimSemana = $dataCarbon->copy()->endOfWeek(Carbon::SATURDAY);

            $psicologo = Psicologo::find($id_psicologo);
            $tempoConsulta = (int) $psicologo->duracao_consulta;
            $intervalo = (int) $psicologo->intervalo_consulta;
            $tempoTotal = $tempoConsulta + $intervalo;

            $semana = [];

            for ($dia = $inicioSemana->copy(); $dia->lte($fimSemana); $dia->addDay()) {

                $dataStr = $dia->toDateString();
                $diaSemana = $dia->dayOfWeek;

                $agendas = Agenda::where('id_psicologo', $id_psicologo)
                    ->where('dia_semana', $diaSemana)
                    ->where('status_agenda', 'disponivel')
                    ->where('data_inicio_vigencia', '<=', $dataStr)
                    ->where(function ($q) use ($dataStr) {
                        $q->whereNull('data_fim_vigencia')
                            ->orWhere('data_fim_vigencia', '>=', $dataStr);
                    })
                    ->get();

                $horarios = [];

                foreach ($agendas as $agenda) {
                    $hora_inicio = Carbon::parse($agenda->hora_inicio);
                    $hora_fim = Carbon::parse($agenda->hora_fim);

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
                    ->where('data_sessao', $dataStr)
                    ->whereIn('status_sessao', [
                        'agendada', 'realizada', 'pendente',
                        'cancelamento_solicitado', 'reagendamento_solicitado',
                        'cancelamentoPsicologo', 'reagendamentoPsicologo',
                    ])
                    ->orderBy('hora_inicio')
                    ->with('paciente.usuario', 'psicologo')
                    ->get()
                    ->mapWithKeys(fn ($s) => [
                        Carbon::parse($s->hora_inicio)->format('H:i') => $s,
                    ]);

                $sessoesDisponiveis = [];

                foreach ($horarios as $horario) {
                    $horaFormatada = Carbon::createFromFormat('H:i', $horario)->format('H:i:s');

                    $evento = Evento::where('id_psicologo', $id_psicologo)
                        ->where(function ($q) use ($dataStr) {
                            $q->where('data_inicio', '<=', $dataStr)
                                ->where(function ($q2) use ($dataStr) {
                                    $q2->whereNull('data_fim')
                                        ->orWhere('data_fim', '>=', $dataStr);
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
                        $sessao = $sessoes[$horario];
                        $link = $sessao->link_sessao ?: $sessao->psicologo->link_consulta;
                        $sessoesDisponiveis[] = [
                            'hora_inicio' => $sessao->hora_inicio,
                            'status_sessao' => $sessao->status_sessao,
                            'link' => $link,
                            'sessao' => $sessao,
                        ];
                    } else {
                        $sessoesDisponiveis[] = [
                            'hora_inicio' => $horario,
                            'status_sessao' => 'disponivel',
                            'sessao' => null,
                        ];
                    }
                }

                $semana[] = [
                    'data' => $dataStr,
                    'weekday' => $dia->locale('pt_BR')->isoFormat('dddd'), // ex: "segunda-feira"
                    'sessoes' => $sessoesDisponiveis,
                ];
            }

            return response()->json([
                'semana' => $semana,
                'inicio_semana' => $inicioSemana->toDateString(),
                'fim_semana' => $fimSemana->toDateString(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar consultas da semana',
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

    public function updateEspecialidades(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'especialidade_ids' => 'required|array',
                'especialidade_ids.*' => 'exists:especialidades,id_especialidade',
            ]);

            $user = auth()->user();

            $psicologo = Psicologo::where('id_usuario', $user->id_usuario)->firstOrFail();

            $psicologo->especialidades()->sync($request->especialidade_ids);

            DB::commit();

            return response()->json([
                'message' => 'Especialidades atualizadas com sucesso',
                'especialidades' => $psicologo->especialidades,
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao atualizar especialidades',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEspecialidade()
    {
        try {

            $user = auth()->user();

            $psicologo = Psicologo::where('id_usuario', $user->id_usuario)
                ->with('especialidades')
                ->get();

            return response()->json(
                $psicologo->especialidades
            );

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar especialidades',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEspecialidades()
    {
        try {

            $especialidades = Especialidade::all();

            return response()->json($especialidades);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar especialidades',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

// consultasDoDia?data=2026-04-13
