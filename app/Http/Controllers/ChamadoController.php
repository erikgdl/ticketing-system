<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChamadoRequest;
use App\Models\Chamado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChamadoController extends Controller
{
    public function index(): JsonResponse
    {
        $chamados = Chamado::with([
            'usuario',
            'tecnico',
            'categoria',
            'comentarios',
            'historicos',
        ])->get();

        return response()->json($chamados);
    }

    public function store(ChamadoRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $chamado = Chamado::create([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'prioridade' => $dados['prioridade'],
            'usuario_id' => $dados['usuario_id'],
            'categoria_id' => $dados['categoria_id'],
            'status' => 'aberto',
            'tecnico_id' => null,
            'data_abertura' => now(),
        ]);

        return response()->json($chamado, 201);
    }

    public function show(Chamado $chamado): JsonResponse
    {
        $chamado->load([
            'usuario',
            'tecnico',
            'categoria',
            'comentarios',
            'historicos',
        ]);

        return response()->json($chamado);
    }

    public function update(ChamadoRequest $request, Chamado $chamado): JsonResponse
    {
        $dados = $request->validated();

        $chamado->update([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'prioridade' => $dados['prioridade'],
            'usuario_id' => $dados['usuario_id'],
            'categoria_id' => $dados['categoria_id'],
        ]);

        return response()->json($chamado);
    }

    public function destroy(Chamado $chamado): JsonResponse
    {
        $chamado->delete();

        return response()->json([
            'message' => 'Chamado deletado com sucesso.'
        ]);
    }
}
