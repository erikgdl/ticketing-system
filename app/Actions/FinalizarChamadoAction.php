<?php

namespace App\Actions;

use App\Models\Chamado;
use App\Models\HistoricoChamado;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizarChamadoAction
{
    public function execute(Chamado $chamado, int $tecnicoId): Chamado {
        return DB::transaction(function () use ($chamado, $tecnicoId) {
           if ($chamado->status !== 'em_atendimento') {
               throw ValidationException::withMessages([
                   'chamado' => 'Apenas chamados em atendimento podem ser finalizados.',
               ]);
           }

            if ($chamado->tecnico_id !== $tecnicoId) {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'Apenas o técnico responsável pode finalizar este chamado.',
                ]);
            }

            $statusAnterior = $chamado->status;

            $chamado->update([
                'status' => 'finalizado',
                'data_fechamento' => now(),
            ]);
            HistoricoChamado::create([
                'chamado_id' => $chamado->id,
                'usuario_id' => $tecnicoId,
                'acao' => 'Chamado finalizado',
                'valor_anterior' => $statusAnterior,
                'valor_novo' => 'finalizado',
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
