<?php

namespace App\Actions;

use App\Models\Chamado;
use App\Models\HistoricoChamado;
use Illuminate\Support\Facades\DB;

class CriarChamadoAction
{
    public function execute(array $dados): Chamado
    {
        return DB::transaction(function () use ($dados) {
            $chamado = Chamado::create([
                'titulo' => $dados['titulo'],
                'descricao' => $dados['descricao'],
                'prioridade' => $dados['prioridade'],
                'usuario_id' => $dados['usuario_id'],
                'categoria_id' => $dados['categoria_id'],

                // Regra de negócio
                'status' => 'aberto',
                'tecnico_id' => null,
                'data_abertura' => now(),
            ]);

            HistoricoChamado::create([
                'chamado_id' => $chamado->id,
                'usuario_id' => $dados['usuario_id'],
                'acao' => 'Chamado criado',
                'valor_anterior' => null,
                'valor_novo' => 'aberto',
            ]);

            return $chamado->load([
                'usuario',
                'tecnico',
                'categoria',
                'comentarios',
                'historicos',
            ]);
        });
    }
}
