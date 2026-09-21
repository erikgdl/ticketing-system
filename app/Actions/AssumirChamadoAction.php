<?php

namespace App\Actions;
use App\Models\Chamado;
use App\Models\HistoricoChamado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class AssumirChamadoAction {

    public function execute(Chamado $chamado, int $tecnicoId): Chamado
    {
        return DB::transaction(function () use ($chamado, $tecnicoId) {
            $tecnico = User::findOrFail($tecnicoId);

            if ($tecnico->tipo !== 'tecnico') {
                throw ValidationException::withMessages([
                    'tecnico_id' => 'Apenas usuários do tipo técnico podem assumir chamados.',
                ]);
            }

            if ($chamado->status !== 'aberto') {
                throw ValidationException::withMessages([
                    'chamado' => 'Apenas chamados abertos podem ser assumidos.',
                ]);
            }

            if ($chamado->tecnico_id !== null) {
                throw ValidationException::withMessages([
                    'chamado' => 'Esse chamado já possui um técnico responsável.',
                ]);
            }

            $statusAnterior = $chamado->status;

            $chamado->update([
                'tecnico_id' => $tecnico->id,
                'status' => 'em_atendimento',
            ]);

            HistoricoChamado::create([
                'chamado_id' => $chamado->id,
                'usuario_id' => $tecnico->id,
                'acao' => 'Chamado assumido',
                'valor_anterior' => $statusAnterior,
                'valor_novo' => 'em_atendimento',
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
