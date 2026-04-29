<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Evento;
use App\Models\Psicologo;
use App\Models\Sessao;
use Illuminate\Http\Request;
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
                        ->whereIn('status_sessao', [
                            'pendente',
                            'agendada',
                            'cancelamento_solicitado',
                            'reagendamento_solicitado',
                        ])
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

    public function configurarAgenda(Request $request)
    {
        DB::beginTransaction();

        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;
            $psicologo = Psicologo::find($id_psicologo);

            $psicologo->update([
                'duracao_consulta' => 50,
                'intervalo_consulta' => 10,
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
}
