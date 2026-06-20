<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use Illuminate\Http\Request;

class PixController extends Controller
{
    public function salvar(Request $request)
    {
        try {
            $request->validate([
                'pix_tipo' => 'required|in:cpf,cnpj,email,telefone,aleatoria',
                'pix_chave' => 'required|string|max:77',
                'pix_nome_recebedor' => 'required|string|max:25',
                'pix_cidade' => 'nullable|string|max:15',
            ]);

            $psicologo = auth()->user()->psicologo;

            $psicologo->update([
                'pix_tipo' => $request->pix_tipo,
                'pix_chave' => $request->pix_chave,
                'pix_nome_recebedor' => $request->pix_nome_recebedor,
                'pix_cidade' => $request->pix_cidade ?? 'SAO PAULO',
            ]);

            return response()->json([
                'message' => 'Dados Pix salvos com sucesso',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao salvar Pix',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function buscar()
    {
        try {
            $psicologo = auth()->user()->psicologo;

            return response()->json([
                'pix_tipo' => $psicologo->pix_tipo,
                'pix_chave' => $psicologo->pix_chave,
                'pix_nome_recebedor' => $psicologo->pix_nome_recebedor,
                'pix_cidade' => $psicologo->pix_cidade,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar Pix',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function dadosPagamento($id_pagamento)
    {
        try {
            $id_paciente = auth()->user()->paciente->id_paciente;

            $pagamento = Pagamento::where('id_pagamento', $id_pagamento)
                ->where('id_paciente', $id_paciente)
                ->with('sessao.psicologo')
                ->firstOrFail();

            $psicologo = $pagamento->sessao->psicologo;

            if (!$psicologo->pix_chave) {
                return response()->json([
                    'error' => 'Psicólogo ainda não cadastrou os dados Pix',
                ], 422);
            }

            return response()->json([
                'valor' => $pagamento->valor_total,
                'pix_tipo' => $psicologo->pix_tipo,
                'pix_chave' => $psicologo->pix_chave,
                'pix_nome_recebedor' => $psicologo->pix_nome_recebedor,
                'pix_cidade' => $psicologo->pix_cidade ?? 'SAO PAULO',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar dados de pagamento',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
