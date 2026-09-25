<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ChamadoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('chamados', ChamadoController::class);
    Route::post('chamados/{chamado}/assumir', [ChamadoController::class, 'assumir']);
    Route::post('chamados/{chamado}/comentarios', [ChamadoController::class, 'adicionarComentario']);
    Route::post('chamados/{chamado}/finalizar', [ChamadoController::class, 'finalizar']);
    Route::post('chamados/{chamado}/cancelar', [ChamadoController::class, 'cancelar']);
});
