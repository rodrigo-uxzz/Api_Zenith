<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pagamento;
use App\Models\Paciente;
use App\Models\Psicologo;
use App\Models\Sessao;

class FinanceiroController extends Controller
{
    public function dashboardFinanceiro() {}

    public function listarPagamentos() {}

    public function detalhesPagamento($id) {}

    public function marcarComoPago($id) {}

}
