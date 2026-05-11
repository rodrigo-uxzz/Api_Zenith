<?php

namespace App\Http\Controllers;

use App\Models\Sessao;
use Illuminate\Support\Facades\DB;

class PsicologoDashboardController extends Controller
{
    public function dashboard()
    {
        try {

            $id_psicologo = auth()->user()->psicologo->id_psicologo;

            //  CARDS
            $total_pacientes = Sessao::where('id_psicologo', $id_psicologo)
                ->distinct('id_paciente')
                ->count('id_paciente');

            $consultas_mes = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', '!=', 'cancelada')
                ->where('status_sessao', '!=', 'recusada')
                ->whereMonth('data_sessao', now()->month)
                ->whereYear('data_sessao', now()->year)
                ->count();

            $consultas_hoje = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', '!=', 'cancelada')
                ->where('status_sessao', '!=', 'recusada')
                ->whereDate('data_sessao', now())
                ->count();

            $faturamentoTotal = Sessao::where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'realizada')
                ->sum('valor');

            // GRÁFICO - FATURAMENTO
            $faturamento = Sessao::select(
                DB::raw('MONTH(data_sessao) as mes'),
                DB::raw('SUM(valor) as total')
            )
                ->where('id_psicologo', $id_psicologo)
                ->where('status_sessao', 'realizada')
                ->whereYear('data_sessao', now()->year)
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            $faturamento_mensal = collect(range(1, 12))->map(function ($mes) use ($faturamento) {

                $item = $faturamento->firstWhere('mes', $mes);

                return [
                    'mes' => $mes,
                    'total' => $item ? $item->total : 0,
                ];
            });

            // GRÁFICO - CONSULTAS
            $consultas = Sessao::select(
                DB::raw('DAYOFWEEK(data_sessao) as dia'),
                DB::raw('COUNT(*) as total')
            )
                ->where('id_psicologo', $id_psicologo)
                ->where('status_sessao', '!=', 'cancelada')
                ->where('status_sessao', '!=', 'recusada')
                ->whereBetween('data_sessao', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])
                ->groupBy('dia')
                ->orderBy('dia')
                ->get();

            $consultas_por_semana = collect(range(1, 7))->map(function ($dia) use ($consultas) {

                $item = $consultas->firstWhere('dia', $dia);

                return [
                    'dia' => $dia,
                    'total' => $item ? $item->total : 0,
                ];
            });

            return response()->json([
                'cards' => [
                    'faturamentoTotal' => $faturamentoTotal,
                    'consultas_hoje' => $consultas_hoje,
                    'consultas_mes' => $consultas_mes,
                    'total_pacientes' => $total_pacientes,
                ],
                'graficos' => [
                    'faturamento_mensal' => $faturamento_mensal,
                    'consultas_por_semana' => $consultas_por_semana,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao carregar dashboard',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
