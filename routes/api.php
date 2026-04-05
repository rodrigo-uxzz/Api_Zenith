<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PsicologosController;

Route::post('/registerPsicologo', [UsersController::class, 'cadastroPsicologo']);
Route::post('/registerPaciente', [UsersController::class, 'cadastroPaciente']);
Route::post('/login', [AuthUserController::class, 'login']);
Route::post('/verificarUserCPF', [AuthUserController::class, 'verificarUserCPF']);

Route::middleware('auth:sanctum')->group(function () {
        Route::patch('/update', [AuthUserController::class, 'updatePerfil']);
        Route::get('/perfil', [AuthUserController::class, 'perfil']);
        Route::post('/logout', [AuthUserController::class, 'logout']);
        Route::delete('/delete', [AuthUserController::class, 'excluirPerfil']);
});

Route::middleware('auth:sanctum')->group(function () {
        Route::get('/listarPsicologos', [PacienteController::class, 'listarPsicologos']);
        Route::get('/verPsicologo/{id}', [PacienteController::class, 'verPsicologo']);
});

