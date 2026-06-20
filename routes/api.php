<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthAdminsController;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PixController;
use App\Http\Controllers\PsicologoDashboardController;
use App\Http\Controllers\PsicologosController;
use App\Http\Controllers\PushTokenController;
use App\Http\Controllers\RecuperarSenhaController;
use App\Http\Controllers\SessaoController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VerificarEmailController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// use Illuminate\Support\Facades\Mail;
// use App\Mail\AgendamentoSessao;

// Route::get('/teste-email', function () {
//     Mail::to('hypnoszz01@gmail.com')->send(new AgendamentoSessao(null));
//     return response()->json(['message' => 'Email enviado!']);
// });

Route::post('/registerPsicologo', [UsersController::class, 'cadastroPsicologo']);
Route::post('/registerPaciente', [UsersController::class, 'cadastroPaciente']);
Route::post('/login', [AuthUserController::class, 'login']);
Route::post('/loginAdmin', [AuthAdminsController::class, 'login']);
Route::post('/verificarUserCPF', [AuthUserController::class, 'verificarCPF']);
Route::post('/verificarUsername', [AuthUserController::class, 'verificarUsername']);
Route::post('/verificarEmail', [VerificarEmailController::class, 'verificar']);
Route::post('/sendVerificationEmail', [VerificarEmailController::class, 'enviar']);
Route::post('/forgotPassword', [RecuperarSenhaController::class, 'enviarCodigo']);
Route::post('/verifyResetCode', [RecuperarSenhaController::class, 'verificarCodigo']);
Route::post('/resetPassword', [RecuperarSenhaController::class, 'redefinirSenha']);

// Rotas usuarios
Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/update', [AuthUserController::class, 'updatePerfil']);
    Route::get('/perfil', [AuthUserController::class, 'perfil']);
    Route::post('/logout', [AuthUserController::class, 'logout']);
    Route::delete('/delete', [AuthUserController::class, 'excluirPerfil']);
});

// Rotas Paciente
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/listarPsicologos', [PacienteController::class, 'listarPsicologos']);
    Route::get('/verPsicologo/{id}', [PacienteController::class, 'verPsicologo']);
    Route::get('/minhasSessoes', [PacienteController::class, 'minhasSessoes']);
    Route::get('/pacienteHistorico', [PacienteController::class, 'historicoSessoes']);
    Route::get('/meusPsicologos', [PacienteController::class, 'listarMeusPsicologos']);
});

// Rotas Psicologo
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/verPaciente/{id}', [PsicologosController::class, 'verPaciente']);
    Route::get('/listarPacientes', [PsicologosController::class, 'listarPacientes']);
    Route::get('/consultasDoDia', [PsicologosController::class, 'consultasDoDia']);
    Route::get('/sessoesPendentes', [PsicologosController::class, 'sessoesPendentes']);
    Route::get('/psicologoHistorico', [PsicologosController::class, 'historicoSessoes']);
    Route::get('/dahsBoardPsicologo', [PsicologoDashboardController::class, 'dashboard']);
    Route::post('/psicologos/especialidades', [PsicologosController::class, 'updateEspecialidades']);
    Route::get('/psicologos/especialidades', [PsicologosController::class, 'getEspecialidade']);
    Route::get('/especialidades', [PsicologosController::class, 'getEspecialidades']);

});

Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/'.$path);

    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');

// Rotas Agenda
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/horariosDisponiveis/{id_psicologo}', [AgendaController::class, 'horariosDisponiveis']);
    Route::post('/marcarEvento', [AgendaController::class, 'marcarEvento']);
    Route::post('/configurarAgenda', [AgendaController::class, 'configurarAgenda']);
    Route::get('/psicologo/agenda/semanal', [PsicologosController::class, 'consultasDaSemana']);
});

// Rotas Sessão
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/agendarSessao', [SessaoController::class, 'agendarSessao']);
    Route::post('/sessaoRealizada/{id_sessao}', [SessaoController::class, 'sessaoRealizada']);
    Route::post('/cancelarSessao/{id_sessao}', [SessaoController::class, 'cancelarSessao']);
    Route::post('/reagendarSessao/{id_sessao}', [SessaoController::class, 'reagendarSessao']);
    Route::get('/detalhesConsulta/{id_sessao}', [SessaoController::class, 'detalhesConsulta']);
    Route::post('/aprovarSessao/{id_sessao}', [SessaoController::class, 'aprovarSessao']);
    Route::post('/recusarSessao/{id_sessao}', [SessaoController::class, 'recusarSessao']);
    Route::post('/solicitarCancelamento/{id_sessao}', [SessaoController::class, 'solicitarCancelamento']);
    Route::post('/solicitarReagendamento/{id_sessao}', [SessaoController::class, 'solicitarReagendamento']);

});

// Rotas Admin
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logoutAdmin', [AuthAdminsController::class, 'logout']);
    Route::get('/psicologos', [AdminController::class, 'listarPsicologos']);
    Route::get('/detalhesPsicologo/{id}', [AdminController::class, 'verPsicologo']);
    Route::get('/pacientes', [AdminController::class, 'listarPacientes']);
    Route::get('/detalhesPaciente/{id}', [AdminController::class, 'verPaciente']);
    Route::post('/aprovarPsicologo/{id_psicologo}', [AdminController::class, 'aprovarPsicologo']);
    Route::post('/rejeitarPsicologo/{id_psicologo}', [AdminController::class, 'rejeitarPsicologo']);
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
});
// Rotas Financeiro
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboardFinanceiro', [FinanceiroController::class, 'dashboardFinanceiro']);
    Route::get('/listarPagamentos', [FinanceiroController::class, 'listarPagamentos']);
    Route::get('/detalhesPagamento/{id}', [FinanceiroController::class, 'detalhesPagamento']);
    Route::post('/marcarComoPago/{id}', [FinanceiroController::class, 'marcarComoPago']);
    Route::get('/verComprovante/{id}', [FinanceiroController::class, 'verComprovante']);
    Route::get('/financeiro/pagamentos/semanal', [FinanceiroController::class, 'listarPagamentosSemanal']);
    Route::post('/anexarComprovante/{id}', [FinanceiroController::class, 'anexarComprovante']);
    Route::get('/pagamento/pendente', [FinanceiroController::class, 'pagamentoPendente']);

});
// Rotas Chat

Route::middleware('auth:sanctum')->group(function () {
    // ListarPsicologo na primeira tela, assim terei
    Route::post('/chat/iniciar', [ChatController::class, 'iniciarChat']); // Isso criar ou chama o chat, será a primeira ação do chat
    Route::post('/chat/enviar', [ChatController::class, 'enviar']); // Enviar o contéudo.
    Route::get('/chat/historico/{id}', [ChatController::class, 'historico']);
    Route::patch('/chat/visualizar/{id_chat}', [ChatController::class, 'visualizar']);
    Route::post('/broadcasting/auth', function (Illuminate\Http\Request $request) {
        return Broadcast::auth($request);
    });
});

// Rotas Notificação
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/save-push-token', [PushTokenController::class, 'store']);
    Route::get('/notifications', [NotificationLogController::class, 'index']);
});

//Rotas pix
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/pix', [PixController::class, 'buscar']);
    Route::put('/pix', [PixController::class, 'salvar']);
    Route::get('/pix/pagamento/{id_pagamento}', [PixController::class, 'dadosPagamento']);
});

//Rotas link da consulta
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/link', [LinkController::class, 'buscarLinkFixo']);
    Route::put('/link', [LinkController::class, 'salvarLinkFixo']);
    Route::put('/link/sessao/{id_sessao}', [LinkController::class, 'salvarLinkSessao']);
    Route::get('/link/sessao/{id_sessao}', [LinkController::class, 'linkParaEntrar']);
});

//Rotas Avaliação
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/avaliacao/{id_psicologo}', [AvaliacaoController::class, 'avaliar']);
});
