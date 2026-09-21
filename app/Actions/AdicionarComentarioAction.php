<?php

namespace App\Actions;

use App\Models\Chamado;
use App\Models\Comentario;
use App\Models\HistoricoChamado;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdicionarComentarioAction
{
    public function execute(Chamado $chamado, array $dados): Chamado
    {
        return DB::transaction(function () use ($chamado, $dados) {
            if (in_array($chamado->status, ['finalizado', 'cancelado'])) {
                throw ValidationException::withMessages([
                    'chamado' => 'Nâo possivel comentar em um chamado finalizado ou cancelado',
                ]);
            }

            $comentario = Comentario::create([
                'chamado_id' => $chamado->id,
                'usuario_id' => $dados['usuario_id'],
                'mensagem' => $dados['mensagem'],
            ]);

            HistoricoChamado::create([
                'chamado_id' => $comentario->id,
                'usuario_id' => $dados['usuario_id'],
                'acao' => 'Comentário adicionado',
                'valor_anterior' => null,
                'valor_novo' => 'Comentário ID: ' . $comentario->id,
            ]);

            return $chamado->load([
                'usuario',
                'tecnico',
                'categoria',
                'comentarios.usuario',
                'historicos',
            ]);


        });
    }

}
