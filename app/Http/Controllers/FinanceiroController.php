<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceiroController extends Controller
{
    public function dashboardFinanceiro(Request $request)
    {
        try {

            $data = $request->data ?? now();

            $dataCarbon = Carbon::parse($data);

            $idPsicologo = auth()->user()->psicologo->id_psicologo;

            $pendentes = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo, $data) {

                $query->where('id_psicologo', $idPsicologo)
                    ->whereDate('data_sessao', $data);

            })
                ->where('status_pagamento', 'pendente')
                ->count();

            $pagas = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo, $data) {

                $query->where('id_psicologo', $idPsicologo)
                    ->whereDate('data_sessao', $data);

            })
                ->where('status_pagamento', 'pago')
                ->count();

            $faturamento = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo, $data) {

                $query->where('id_psicologo', $idPsicologo)
                    ->whereDate('data_sessao', $data);

            })
                ->where('status_pagamento', 'pago')
                ->sum('valor_total');

            $faturamento_mensal = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo) {
                $query->where('id_psicologo', $idPsicologo);
            })
                ->where('status_pagamento', 'pago')
                ->whereMonth('created_at', $dataCarbon->month)
                ->whereYear('created_at', $dataCarbon->year)
                ->sum('valor_total');

            return response()->json([
                'pendentes' => $pendentes,
                'pagas' => $pagas,
                'faturamento' => $faturamento,
                'faturamento_mensal' => $faturamento_mensal,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar dados',
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    public function listarPagamentos(Request $request)
    {

        try {

            $data = $request->data ?? now();

            $idPsicologo = auth()->user()->psicologo->id_psicologo;

            $pagamentos = Pagamento::with('paciente.usuario', 'sessao')
                ->whereHas('sessao', function ($query) use ($idPsicologo, $data) {

                    $query->where('id_psicologo', $idPsicologo)
                        ->whereDate('data_sessao', $data);

                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'pagamentos' => $pagamentos,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao listar pagamentos',
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    public function detalhesPagamento($id)
    {

        try {

            $idPsicologo = auth()->user()->psicologo->id_psicologo;

            $pagamento = Pagamento::with('paciente.usuario', 'sessao')
                ->whereHas('sessao', function ($query) use ($idPsicologo) {
                    $query->where('id_psicologo', $idPsicologo);
                })
                ->findOrFail($id);

            return response()->json([
                'pagamento' => $pagamento,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar detalhes do pagamento',
                'message' => $e->getMessage(),
            ], 500);
        }

    }

    public function marcarComoPago($id)
    {
        DB::beginTransaction();

        try {

            $idPsicologo = auth()->user()->psicologo->id_psicologo;

            $pagamento = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo) {
                $query->where('id_psicologo', $idPsicologo);
            })
                ->findOrFail($id);

            $pagamento->status_pagamento = 'pago';

            $pagamento->save();

            DB::commit();

            return response()->json([
                'message' => 'Pagamento atualizado com sucesso',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Erro ao comprovar pagamento',
                'message' => $e->getMessage(),
            ], 500);
        }

    }
}
