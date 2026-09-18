<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::all();

        return response()->json($categorias);
    }


    public function store(Request $request) : JsonResponse
    {
        $categoria = Categoria::create($request->validated());

        return response()->json($categoria, 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json($categoria);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): JsonResponse
    {
        $categoria->update($request->validated());

        return response()->json($categoria);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        $categoria->delete();

        return response()->json([
            'message' => 'Categoria deletada com sucesso.'
        ]);
    }
}
