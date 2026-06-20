<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use App\Models\Sessao;
use Illuminate\Http\Request;

class AvaliacaoController extends Controller
{
    // Paciente envia ou atualiza sua avaliação
    public function avaliar(Request $request, $id_psicologo)
    {
        try {
            $request->validate([
                'nota' => 'required|integer|min:1|max:5',
            ]);

            $id_paciente = auth()->user()->paciente->id_paciente;

            $temSessao = Sessao::where('id_psicologo', $id_psicologo)
                ->where('id_paciente', $id_paciente)
                ->where('status_sessao', 'realizada')
                ->exists();

            if (!$temSessao) {
                return response()->json([
                    'error' => 'Você só pode avaliar psicólogos com quem teve sessões realizadas.',
                ], 403);
            }

            $avaliacao = Avaliacao::updateOrCreate(
                ['id_paciente' => $id_paciente, 'id_psicologo' => $id_psicologo],
                ['nota' => $request->nota]
            );

            return response()->json([
                'message'   => 'Avaliação enviada com sucesso',
                'avaliacao' => $avaliacao,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao enviar avaliação',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
