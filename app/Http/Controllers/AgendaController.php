<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Evento;
use App\Models\Psicologo;
use App\Models\Sessao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function horariosDisponiveis($id_psicologo, Request $request)
    {
        try {

            $data = $request->data;

            $dia_semana = date('w', strtotime($data));

            $agendas = Agenda::where('id_psicologo', $id_psicologo)
                ->where('dia_semana', $dia_semana)
                ->where('status_agenda', 'disponivel')
                ->get();

            if ($agendas->isEmpty()) {
                return response()->json([
                    'horarios' => [],
                    'message' => 'Nenhum horário disponível para este dia.',
                ]);
            }

            $horarios = [];

            $id_sessao = $request->id_sessao;
            $psicologo = Psicologo::find($id_psicologo);

            $tempoConsulta = 50;
            $intervalo = 10;

            // $tempoConsulta = $psicologo->duracao_consulta;
            // $intervalo = $psicologo->intervalo_consulta;
            $tempoTotal = $tempoConsulta + $intervalo;

            foreach ($agendas as $agenda) {


                $hora_inicio = strtotime($agenda->hora_inicio);
                $hora_fim = strtotime($agenda->hora_fim);

                while ($hora_inicio + ($tempoConsulta * 60) <= $hora_fim) {

                    $hora = date('H:i', $hora_inicio);

                    $ocupado = Sessao::where('id_psicologo', $id_psicologo)
                        ->where('data_sessao', $data)
                        ->where('hora_inicio', $hora)
                        ->where('status_sessao', '!=', 'cancelada')
                        ->when($id_sessao, function ($query) use ($id_sessao) {
                            $query->where('id_sessao', '!=', $id_sessao);
                        })
                        ->exists();

                    $horaFormatada = date('H:i:s', strtotime($hora));

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
                        ->where(function ($queryEvento) use ($horaFormatada) {
                            $queryEvento->whereNull('hora_inicio')
                                ->orWhere(function ($queryHorario) use ($horaFormatada) {
                                    $queryHorario->where('hora_inicio', '<=', $horaFormatada)
                                        ->where('hora_fim', '>', $horaFormatada);
                                });

                        })
                        ->exists();

                    if (! $ocupado && ! $evento) {
                        $horarios[] = $hora;
                    }

                    $hora_inicio = strtotime("+{$tempoTotal} minutes", $hora_inicio);
                }
            }

            return response()->json($horarios);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar horários disponíveis',
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    public function marcarEvento(Request $request)
    {

        DB::beginTransaction();
        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;

            $request->validate([
                'slug' => 'nullable|in:almoco,reuniao,bloqueio',
                'nome' => 'nullable|string|max:255',
                'descricao' => 'nullable|string',
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date|after_or_equal:data_inicio',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
            ]);

            if ($request->slug === 'almoco') {
                $request->merge([
                    'nome' => 'Almoço',
                    'data_inicio' => now()->toDateString(),
                    'data_fim' => null,
                ]);
            }

            if ($request->slug === 'almoco') {
                Evento::updateOrCreate(
                    [
                        'id_psicologo' => $id_psicologo,
                        'slug' => 'almoco',
                    ],
                    [
                        'slug' => 'almoco',
                        'nome' => 'Almoço',
                        'descricao' => null,
                        'data_inicio' => now()->toDateString(),
                        'data_fim' => null,
                        'hora_inicio' => $request->hora_inicio,
                        'hora_fim' => $request->hora_fim,
                    ]
                );
            } else {
                Evento::create([
                    'id_psicologo' => $id_psicologo,
                    'nome' => $request->nome,
                    'descricao' => $request->descricao,
                    'data_inicio' => $request->data_inicio,
                    'data_fim' => $request->data_fim,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fim' => $request->hora_fim,
                    'slug' => $request->slug,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Evento marcado com sucesso',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao marcar evento',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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
                ->where('status_sessao', '!==', 'cancelada')
                ->exists();

            if ($ocupado) {
                return response()->json([
                    'erro' => 'Horário já ocupado',
                ], 400);
            }

            $hora_fim = date('H:i', strtotime('+50 minutes', strtotime($hora_inicio)));

            Sessao::create([
                'id_psicologo' => $id_psicologo,
                'id_paciente' => $id_paciente,
                'data_sessao' => $data_sessao,
                'hora_inicio' => $hora_inicio,
                'hora_fim' => $hora_fim,
                'status_sessao' => 'agendada',
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
        DB::transaction();

        try{
            $sessao = Sessao::find($id_sessao);

            if(!$sessao){
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

            if($ocupado){
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
}

