<?php

namespace App\Http\Controllers;

use App\Actions\AdicionarComentarioAction;
use App\Actions\AssumirChamadoAction;
use App\Actions\CancelarChamadoAction;
use App\Actions\CriarChamadoAction;
use App\Actions\FinalizarChamadoAction;
use App\Http\Requests\AdicionarComentarioRequest;
use App\Http\Requests\AssumirChamadoRequest;
use App\Http\Requests\CancelarChamadoRequest;
use App\Http\Requests\ChamadoRequest;
use App\Http\Requests\FinalizarChamadoRequest;
use App\Models\Chamado;
use Illuminate\Http\JsonResponse;

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

    public function store(
        ChamadoRequest $request,
        CriarChamadoAction $action
    ): JsonResponse {
        $chamado = $action->execute($request->validated());

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
            'message' => 'Chamado deletado com sucesso.',
        ]);
    }

    public function assumir(
        AssumirChamadoRequest $request,
        Chamado $chamado,
        AssumirChamadoAction $action
    ): JsonResponse {
        $dados = $request->validated();

        $chamado = $action->execute($chamado, $dados['tecnico_id']);

        return response()->json($chamado);
    }

    public function adicionarComentario(
        AdicionarComentarioRequest $request,
        Chamado $chamado,
        AdicionarComentarioAction $action
    ): JsonResponse {
        $chamado = $action->execute($chamado, $request->validated());

        return response()->json($chamado);
    }

    public function finalizar(
        FinalizarChamadoRequest $request,
        Chamado $chamado,
        FinalizarChamadoAction $action
    ): JsonResponse {
        $dados = $request->validated();

        $chamado = $action->execute(
            $chamado,
            $dados['tecnico_id']
        );

        return response()->json($chamado);
    }

    public function cancelar(
        CancelarChamadoRequest $request,
        Chamado $chamado,
        CancelarChamadoAction $action
    ): JsonResponse {
        $dados = $request->validated();

        $chamado = $action->execute(
            $chamado,
            $dados['usuario_id']
        );

        return response()->json($chamado);
    }
}
