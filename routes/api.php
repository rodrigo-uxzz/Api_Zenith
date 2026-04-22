<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PsicologosController;
use App\Http\Controllers\UsersController;
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
});

// Rotas Psicologo
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/verPaciente/{id}', [PsicologosController::class, 'verPaciente']);
    Route::get('/listarPacientes', [PsicologosController::class, 'listarPacientes']);
    Route::get('/consultasDoDia', [PsicologosController::class, 'consultasDoDia']);
    Route::post('/configurarAgenda', [PsicologosController::class, 'configurarAgenda']);

});

// Rotas Agenda
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/horariosDisponiveis/{id_psicologo}', [AgendaController::class, 'horariosDisponiveis']);
    Route::post('/agendarSessao', [AgendaController::class, 'agendarSessao']);
    Route::post('/marcarEvento', [AgendaController::class, 'marcarEvento']);
    Route::post('/sessaoRealizada/{id}', [AgendaController::class, 'sessaoRealizada']);
    Route::post('/cancelarSessao/{id}', [AgendaController::class, 'cancelarSessao']);
    Route::get('/detalhesConsulta/{id}', [AgendaController::class, 'detalhesConsulta']);

});
