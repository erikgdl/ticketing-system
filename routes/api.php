<?php

use App\Http\Controllers\CategoriaController;
use \App\Http\Controllers\ChamadoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('chamados', ChamadoController::class);
