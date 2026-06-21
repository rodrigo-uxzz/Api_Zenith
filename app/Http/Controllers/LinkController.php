<?php

namespace App\Http\Controllers;

use App\Models\Sessao;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function salvarLinkFixo(Request $request)
    {
        try {
            $request->validate([
                'link_consulta' => 'required|url|max:500',
            ]);

            $psicologo = auth()->user()->psicologo;

            $psicologo->update([
                'link_consulta' => $request->link_consulta,
            ]);

            return response()->json([
                'message' => 'Link fixo salvo com sucesso',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao salvar link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function buscarLinkFixo()
    {
        try {
            $psicologo = auth()->user()->psicologo;

            return response()->json([
                'link_consulta' => $psicologo->link_consulta,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao buscar link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function salvarLinkSessao(Request $request, $id_sessao)
    {
        try {
            $request->validate([
                'link_sessao' => 'required|url|max:500',
            ]);

            $id_psicologo = auth()->user()->psicologo->id_psicologo;

            $sessao = Sessao::where('id_sessao', $id_sessao)
                ->where('id_psicologo', $id_psicologo)
                ->firstOrFail();

            $sessao->update([
                'link_sessao' => $request->link_sessao,
            ]);

            return response()->json([
                'message' => 'Link da sessão salvo com sucesso',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao salvar link da sessão',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function linkParaEntrar($id_sessao)
    {
        try {
            $id_paciente = auth()->user()->paciente->id_paciente;

            $sessao = Sessao::where('id_sessao', $id_sessao)
                ->where('id_paciente', $id_paciente)
                ->with('psicologo')
                ->firstOrFail();

            $link = $sessao->link_sessao ?: $sessao->psicologo->link_consulta;

            if (!$link) {
                return response()->json([
                    'error' => 'Nenhum link disponível para essa sessão',
                ], 422);
            }

            return response()->json([
                'link'   => $link,
                'tipo'   => $sessao->link_sessao ? 'especifico' : 'fixo',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao buscar link',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
