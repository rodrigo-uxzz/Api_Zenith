<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\Request;
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
            $psicologo = Psicologo::find($request->id_psicologo);

            $psicologo->update([
                'duracao_consulta' => $request->duracao_consulta,
                'intervalo_consulta' => $request->intervalo_consulta,
            ]);

            Agenda::where('id_psicologo', $request->id_psicologo)->delete();

            foreach ($request->agendas as $agenda) {
                Agenda::create([
                    'id_psicologo' => $request->id_psicologo,
                    'dia_semana' => $agenda['dia_semana'],
                    'hora_inicio' => $agenda['hora_inicio'],
                    'hora_fim' => $agenda['hora_fim'],
                    'status_agenda' => 'disponivel',
                ]);
            }

            DB::commit();

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

            $tempoConsulta = $psicologo->duracao_consulta;
            $intervalo = $psicologo->intervalo_consulta;
            $tempoTotal = $tempoConsulta + $intervalo;

            $horarios = [];

            foreach ($agendas as $agenda) {

                $hora_inicio = strtotime($agenda->hora_inicio);
                $hora_fim = strtotime($agenda->hora_fim);

                while ($hora_inicio + ($tempoConsulta * 60) <= $hora_fim) {

                    $horarios[] = date('H:i', $hora_inicio);
                    $hora_inicio = strtotime("+{$tempoTotal} minutes", $hora_inicio);
                }
            }
            $sessoes = Sessao::where('id_psicologo', $id_psicologo)
                ->where('data_sessao', $data)
                ->orderBy('hora_inicio')
                ->with('paciente.usuario')
                ->get()->mapWithKeys(function ($sessao) {
                    $hora = date('H:i', strtotime($sessao->hora_inicio));

                    return [$hora => $sessao];
                });

            $sessoesDisponiveis = [];

            foreach ($horarios as $horario) {

                if (isset($sessoes[$horario])) {
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

    public function detalhesConsulta($id_sessao)
    {
        try{
            $sessao = Sessao::where('id_sessao', $id_sessao)
            ->with('paciente.usuario')
            ->first();

            return response()->json([
                'sessao' => $sessao
            ]);

        }catch(\Exception $e){
            return response()->json([
                'error' => 'Erro ao buscar sessão',
                'message' => $e->getMessage(),
            ]);
        }
    }
}


//consultasDoDia?data=2026-04-13
