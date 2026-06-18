<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                    ->whereDate('data_sessao', $data)
                    ->where('status_sessao', '!=', 'cancelada')
                    ->where('status_sessao', '!=', 'recusada');

            })
                ->where('status_pagamento', 'pendente')
                ->count();

            $pagas = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo, $data) {

                $query->where('id_psicologo', $idPsicologo)
                    ->whereDate('data_sessao', $data)
                    ->where('status_sessao', '!=', 'cancelada')
                    ->where('status_sessao', '!=', 'recusada');

            })
                ->where('status_pagamento', 'pago')
                ->count();

            $faturamento = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo, $data) {

                $query->where('id_psicologo', $idPsicologo)
                    ->whereDate('data_sessao', $data);

            })
                ->where('status_pagamento', 'pago')
                ->sum('valor_total');

            $faturamento_mensal = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo, $dataCarbon) {
                $query->where('id_psicologo', $idPsicologo)
                    ->whereMonth('data_sessao', $dataCarbon->month)
                    ->whereYear('data_sessao', $dataCarbon->year);
            })
                ->where('status_pagamento', 'pago')
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
                        ->whereDate('data_sessao', $data)
                        ->where('status_sessao', '!=', 'cancelada')
                        ->where('status_sessao', '!=', 'recusada');

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

    public function anexarComprovante(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $request->validate([
                'comprovante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5048',
            ]);

            $pagamento = Pagamento::where('id_pagamento', $id)
            ->where('id_paciente', auth()->user()->paciente->id_paciente)
            ->firstOrFail();

            if ($pagamento->status_pagamento === 'pago') {
                return response()->json([
                    'error' => 'Pagamento já comprovado',
                ], 400);
            }

            if ($pagamento->comprovante) {
                Storage::disk('public')->delete($pagamento->comprovante);
            }

            $caminho = $request->file('comprovante')->store('comprovantes', 'public');

            $pagamento->comprovante = $caminho;
            $pagamento->status_pagamento = 'aguardando_confirmacao';
            $pagamento->save();

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => 'Comprovante anexado com sucesso',
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verComprovante($id)
    {
        try {

            $idPsicologo = auth()->user()->psicologo->id_psicologo;

            $pagamento = Pagamento::whereHas('sessao', function ($query) use ($idPsicologo) {
                $query->where('id_psicologo', $idPsicologo);
            })->findOrFail($id);

            if (! $pagamento->comprovante) {
                return response()->json([
                    'error' => 'Nenhum comprovante anexado',
                ], 404);
            }

            $url = Storage::disk('public')->url($pagamento->comprovante);

            return response()->json([
                'comprovante' => $url,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Erro ao buscar comprovante',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function pagamentoPendente()
    {
        try {
            $id_paciente = auth()->user()->paciente->id_paciente;

            // Busca sessões agendadas futuras do paciente
            $hoje = now()->toDateString();

            $pagamento = Pagamento::where('pagamento.id_paciente', $id_paciente)
                ->where('pagamento.status_pagamento', 'pendente')
                ->whereHas('sessao', function ($q) use ($hoje) {
                    $q->where('data_sessao', '>=', $hoje)
                    ->where('status_sessao', 'agendada');
                })
                ->with(['sessao.psicologo.usuario'])
                ->join('sessao', 'pagamento.id_sessao', '=', 'sessao.id_sessao')
                ->orderBy('sessao.data_sessao', 'asc')
                ->orderBy('sessao.hora_inicio', 'asc')
                ->select('pagamento.*')
                ->first();

            if (!$pagamento) {
                return response()->json([
                    'message' => 'Nenhum pagamento pendente encontrado',
                    'pagamento' => null,
                ], 200);
            }

            return response()->json([
                'pagamento' => $pagamento,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao buscar pagamento pendente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
