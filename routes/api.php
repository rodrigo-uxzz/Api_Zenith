<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PsicologosController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\SessaoController;
use Illuminate\Support\Facades\Route;

Route::post('/registerPsicologo', [UsersController::class, 'cadastroPsicologo']);
Route::post('/registerPaciente', [UsersController::class, 'cadastroPaciente']);
Route::post('/login', [AuthUserController::class, 'login']);
Route::post('/verificarUserCPF', [AuthUserController::class, 'verificarUserCPF']);

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
});

// Rotas Psicologo
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/verPaciente/{id}', [PsicologosController::class, 'verPaciente']);
    Route::get('/listarPacientes', [PsicologosController::class, 'listarPacientes']);
    Route::get('/consultasDoDia', [PsicologosController::class, 'consultasDoDia']);
});

// Rotas Agenda
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/horariosDisponiveis/{id_psicologo}', [AgendaController::class, 'horariosDisponiveis']);
    Route::post('/marcarEvento', [AgendaController::class, 'marcarEvento']);
    Route::post('/configurarAgenda', [AgendaController::class, 'configurarAgenda']);
});

//Rotas Sessão
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/agendarSessao', [SessaoController::class, 'agendarSessao']);
    Route::post('/sessaoRealizada/{id}', [SessaoController::class, 'sessaoRealizada']);
    Route::post('/cancelarSessao/{id}', [SessaoController::class, 'cancelarSessao']);
    Route::get('/detalhesConsulta/{id}', [SessaoController::class, 'detalhesConsulta']);
    Route::post('/aprovarSessao/{id_sessao}', [SessaoController::class, 'aprovarSessao']);
    Route::post('/recusarSessao/{id_sessao}', [SessaoController::class, 'recusarSessao']);

});
