<?php

namespace App\Http\Controllers;

use App\Models\Psicologo;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        try {

            //  CARDS
            $usuarios_total = User::count();

            $psicologos_total = Psicologo::count();

            $sessoes_realizadas = Sessao::where('status_sessao', 'realizada')->count();

            $psicologos_pendentes = Psicologo::where('status_psicologo', 'pendente')->count();

            $receita_total = Sessao::where('status_sessao', 'realizada')->sum('valor');

            // GRÁFICO - USUÁRIOS POR MÊS
            $usuarios_por_mes = User::select(
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('COUNT(*) as total')
            )
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            // GRÁFICO - PSICÓLOGOS POR MÊS
            $psicologos_por_mes = Psicologo::select(
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('COUNT(*) as total')
            )
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            // GRÁFICO - SESSÕES (agendadas vs realizadas)
            $sessoes = Sessao::select(
                DB::raw('MONTH(data_sessao) as mes'),
                DB::raw("SUM(CASE WHEN status_sessao = 'agendada' THEN 1 ELSE 0 END) as agendadas"),
                DB::raw("SUM(CASE WHEN status_sessao = 'realizada' THEN 1 ELSE 0 END) as realizadas")
            )
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            $sessoes_por_mes = collect(range(1, 12))->map(function ($mes) use ($sessoes) {

                $item = $sessoes->firstWhere('mes', $mes);

                return [
                    'mes' => $mes,
                    'agendadas' => $item ? $item->agendadas : 0,
                    'realizadas' => $item ? $item->realizadas : 0,
                ];
            });
            
            // GRÁFICO - FATURAMENTO
            $faturamento = Sessao::select(
                DB::raw('MONTH(data_sessao) as mes'),
                DB::raw('SUM(valor) as total')
            )
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

            return response()->json([
                'cards' => [
                    'usuarios_total' => $usuarios_total,
                    'psicologos_total' => $psicologos_total,
                    'sessoes_realizadas' => $sessoes_realizadas,
                    'psicologos_pendentes' => $psicologos_pendentes,
                    'receita_total' => $receita_total,
                ],
                'graficos' => [
                    'usuarios_por_mes' => $usuarios_por_mes,
                    'psicologos_por_mes' => $psicologos_por_mes,
                    'sessoes_por_mes' => $sessoes_por_mes,
                    'faturamento_mensal' => $faturamento_mensal,
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
