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

    public function store(CategoriaRequest $request): JsonResponse
    {
        $this->autorizarEquipe($request);
        $categoria = Categoria::create($request->validated());

        return response()->json($categoria, 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json($categoria);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): JsonResponse
    {
        $this->autorizarAdministrador($request);
        $categoria->update($request->validated());

        return response()->json($categoria);
    }

    public function destroy(Request $request, Categoria $categoria): JsonResponse
    {
        $this->autorizarAdministrador($request);
        $categoria->delete();

        return response()->json([
            'message' => 'Categoria deletada com sucesso.',
        ]);
    }

    private function autorizarEquipe(Request $request): void
    {
        abort_unless(
            in_array($request->user()->tipo, ['tecnico', 'admin'], true),
            403,
            'Apenas técnicos e administradores podem gerenciar categorias.'
        );
    }

    private function autorizarAdministrador(Request $request): void
    {
        abort_unless($request->user()->tipo === 'admin', 403, 'Apenas administradores podem alterar ou excluir categorias.');
    }
}
