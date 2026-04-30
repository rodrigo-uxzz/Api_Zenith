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

            if ($agendas->isEmpty()) {
                return response()->json([
                    'horarios' => [],
                    'message' => 'Nenhum horário disponível para este dia.',
                ]);
            }

            $horarios = [];
            $id_sessao = $request->id_sessao;

            $tempoConsulta = 50;
            $intervalo = 10;
            $tempoTotal = $tempoConsulta + $intervalo;

            foreach ($agendas as $agenda) {

                $hora_inicio = Carbon::createFromFormat('H:i:s', $agenda->hora_inicio);
                $hora_fim = Carbon::createFromFormat('H:i:s', $agenda->hora_fim);

                while ($hora_inicio->copy()->addMinutes($tempoConsulta)->lte($hora_fim)) {

                    $hora = $hora_inicio->format('H:i');
                    $horaFormatada = Carbon::createFromFormat('H:i', $hora)->format('H:i:s');

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
                        ->exists();

                    if (! $ocupado && ! $evento) {
                        $horarios[] = $hora;
                    }

                    $hora_inicio->addMinutes($tempoTotal);
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

            $request->validate([
                'agendas' => 'required|array|min:1',
                'agendas.*.dia_semana' => 'required|integer|min:0|max:6',
                'agendas.*.hora_inicio' => 'required|date_format:H:i',
                'agendas.*.hora_fim' => 'required|date_format:H:i|after:agendas.*.hora_inicio',
            ]);

            $ultimaSessao = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', '>=', now()->toDateString())
                ->whereIn('status_sessao', [
                    'pendente',
                    'agendada',
                    'realizada',
                    'reagendamento_solicitado',
                    'cancelamento_solicitado',
                ])
                ->orderBy('data_sessao', 'desc')
                ->orderBy('hora_fim', 'desc')
                ->first();

            if ($ultimaSessao) {
                $dataInicioCarbon = Carbon::parse($ultimaSessao->data_sessao)->addDay();
            } else {

                $dataInicioCarbon = now()->startOfDay();
            }

            $ultimaSessao = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', '>=', now()->toDateString())
                ->orderBy('data_sessao', 'desc')
                ->orderBy('hora_fim', 'desc')
                ->first();

            if ($ultimaSessao) {
                $inicioPermitido = Carbon::parse($ultimaSessao->data_sessao)->addDay();

                if ($dataInicioCarbon->lt($inicioPermitido)) {
                    $dataInicioCarbon = $inicioPermitido;
                }
            }

            Agenda::where('id_psicologo', $id_psicologo)
                ->whereNull('data_fim_vigencia')
                ->update([
                    'data_fim_vigencia' => $dataInicioCarbon->copy()->subDay()->format('Y-m-d'),
                ]);

            Psicologo::where('id_psicologo', $id_psicologo)->update([
                'intervalo_consulta' => 10,
                'duracao_consulta' => 50,
            ]);

            foreach ($request->agendas as $agenda) {
                Agenda::create([
                    'id_psicologo' => $id_psicologo,
                    'dia_semana' => $agenda['dia_semana'],
                    'data_inicio_vigencia' => $dataInicioCarbon->format('Y-m-d'),
                    'hora_inicio' => $agenda['hora_inicio'],
                    'hora_fim' => $agenda['hora_fim'],
                    'status_agenda' => 'disponivel',
                    'data_fim_vigencia' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Agenda atualizada com sucesso',
            ]);

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

                $agendaAtiva = Agenda::where('id_psicologo', $id_psicologo)
                    ->whereNull('data_fim_vigencia')
                    ->orderBy('data_inicio_vigencia', 'asc')
                    ->first();

                $dataInicioEvento = $agendaAtiva
                    ? $agendaAtiva->data_inicio_vigencia
                    : now()->toDateString();

                $request->merge([
                    'nome' => 'Almoço',
                    'data_inicio' => $dataInicioEvento,
                    'data_fim' => null,
                ]);
            }

            $data = $request->data_inicio ?? now()->toDateString();

            $conflito = Sessao::where('id_psicologo', $id_psicologo)
                ->whereDate('data_sessao', $data)
                ->whereIn('status_sessao', [
                    'pendente',
                    'agendada',
                    'reagendamento_solicitado',
                ])
                ->where(function ($q) use ($request) {
                    $q->where('hora_inicio', '<', $request->hora_fim)
                        ->where('hora_fim', '>', $request->hora_inicio);
                })
                ->exists();

            if ($conflito) {
                return response()->json([
                    'error' => 'Evento conflita com uma sessão existente',
                ], 400);
            }

            if ($request->slug === 'almoco') {

                $agendaAtiva = Agenda::where('id_psicologo', $id_psicologo)
                    ->whereNull('data_fim_vigencia')
                    ->orderBy('data_inicio_vigencia', 'asc')
                    ->first();

                $dataInicioEvento = $agendaAtiva
                    ? $agendaAtiva->data_inicio_vigencia
                    : now()->toDateString();

                Evento::where('id_psicologo', $id_psicologo)
                    ->where('slug', 'almoco')
                    ->where(function ($q) {
                        $q->whereNull('data_fim')
                            ->orWhere('data_fim', '>=', now()->toDateString());
                    })
                    ->update([
                        'data_fim' => Carbon::parse($dataInicioEvento)->subDay()->format('Y-m-d'),
                    ]);

                Evento::create([
                    'id_psicologo' => $id_psicologo,
                    'nome' => 'Almoço',
                    'data_inicio' => $dataInicioEvento,
                    'data_fim' => null,
                    'hora_inicio' => $request->hora_inicio,
                    'hora_fim' => $request->hora_fim,
                    'slug' => 'almoco',
                ]);

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
                'message' => 'Evento criado com sucesso',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao marcar evento',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
