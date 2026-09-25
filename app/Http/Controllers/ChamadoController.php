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
use Illuminate\Http\Request;

class ChamadoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Chamado::with([
            'usuario',
            'tecnico',
            'categoria',
            'comentarios.usuario',
            'historicos',
        ]);

        if ($request->user()->tipo === 'solicitante') {
            $query->where('usuario_id', $request->user()->id);
        }

        $chamados = $query->latest('id')->get();

        return response()->json($chamados);
    }

    public function store(
        ChamadoRequest $request,
        CriarChamadoAction $action
    ): JsonResponse {
        abort_unless($request->user()->tipo === 'solicitante', 403, 'Apenas solicitantes podem abrir chamados.');

        $chamado = $action->execute([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($chamado, 201);
    }

    public function show(Request $request, Chamado $chamado): JsonResponse
    {
        $this->autorizarVisualizacao($request, $chamado);

        $chamado->load([
            'usuario',
            'tecnico',
            'categoria',
            'comentarios.usuario',
            'historicos',
        ]);

        return response()->json($chamado);
    }

    public function update(ChamadoRequest $request, Chamado $chamado): JsonResponse
    {
        abort_unless($request->user()->tipo === 'admin', 403, 'Apenas administradores podem editar chamados.');

        $dados = $request->validated();

        $chamado->update([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'prioridade' => $dados['prioridade'],
            'categoria_id' => $dados['categoria_id'],
        ]);

        return response()->json($chamado);
    }

    public function destroy(Request $request, Chamado $chamado): JsonResponse
    {
        abort_unless(
            in_array($request->user()->tipo, ['tecnico', 'admin'], true),
            403,
            'Apenas técnicos e administradores podem remover chamados da lista.'
        );

        $chamado->delete();

        return response()->json([
            'message' => 'Chamado removido da lista com sucesso.',
        ]);
    }

    public function assumir(
        AssumirChamadoRequest $request,
        Chamado $chamado,
        AssumirChamadoAction $action
    ): JsonResponse {
        abort_unless($request->user()->tipo === 'tecnico', 403, 'Apenas técnicos podem assumir chamados.');

        $chamado = $action->execute($chamado, $request->user()->id);

        return response()->json($chamado);
    }

    public function adicionarComentario(
        AdicionarComentarioRequest $request,
        Chamado $chamado,
        AdicionarComentarioAction $action
    ): JsonResponse {
        $this->autorizarVisualizacao($request, $chamado);

        $chamado = $action->execute($chamado, [
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($chamado);
    }

    public function finalizar(
        FinalizarChamadoRequest $request,
        Chamado $chamado,
        FinalizarChamadoAction $action
    ): JsonResponse {
        abort_unless($request->user()->tipo === 'tecnico', 403, 'Apenas técnicos podem finalizar chamados.');

        $chamado = $action->execute(
            $chamado,
            $request->user()->id
        );

        return response()->json($chamado);
    }

    public function cancelar(
        CancelarChamadoRequest $request,
        Chamado $chamado,
        CancelarChamadoAction $action
    ): JsonResponse {
        abort_unless($request->user()->tipo === 'admin', 403, 'Apenas administradores podem cancelar chamados.');

        $chamado = $action->execute(
            $chamado,
            $request->user()->id
        );

        return response()->json($chamado);
    }

    private function autorizarVisualizacao(Request $request, Chamado $chamado): void
    {
        if ($request->user()->tipo === 'solicitante') {
            abort_unless(
                $chamado->usuario_id === $request->user()->id,
                403,
                'Você não tem acesso a este chamado.'
            );
        }
    }
}
