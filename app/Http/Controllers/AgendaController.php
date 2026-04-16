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

            $psicologo = Psicologo::find($id_psicologo);

            $tempoConsulta = $psicologo->duracao_consulta;
            $intervalo = $psicologo->intervalo_consulta;
            $tempoTotal = $tempoConsulta + $intervalo;

            foreach ($agendas as $agenda) {

                $hora_inicio = strtotime($agenda->hora_inicio);
                $hora_fim = strtotime($agenda->hora_fim);

                while ($hora_inicio + ($tempoConsulta * 60) <= $hora_fim) {

                    $hora = date('H:i', $hora_inicio);

                    $ocupado = Sessao::where('id_psicologo', $id_psicologo)
                        ->where('data_sessao', $data)
                        ->where('hora_inicio', $hora)
                        ->exists();

                    $horaFormatada = date('H:i:s', strtotime($hora));

                    $evento = Evento::where('id_psicologo', $id_psicologo)
                        ->where('data_inicio', '<=', $data)
                        ->where('data_fim', '>=', $data)
                        ->where(function ($queryEvento) use ($horaFormatada) {
                            $queryEvento->whereNull('hora_inicio')
                                ->orWhere(function ($queryHorario) use ($horaFormatada) {
                                    $queryHorario->where('Hora_inicio', '<=', $horaFormatada)
                                        ->where('Hora_fim', '>', $horaFormatada);
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
}
