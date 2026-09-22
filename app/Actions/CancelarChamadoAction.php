<?php

namespace App\Actions;

use App\Models\Chamado;
use App\Models\HistoricoChamado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelarChamadoAction
{
    public function execute(Chamado $chamado, int $usuarioId): Chamado
    {
        return DB::transaction(function () use ($chamado, $usuarioId) {
            $usuario = User::findOrFail($usuarioId);

            if ($chamado->status === 'finalizado') {
                throw ValidationException::withMessages([
                    'chamado' => 'Chamados finalizados não podem ser cancelados.',
                ]);
            }

            if ($chamado->status === 'cancelado') {
                throw ValidationException::withMessages([
                    'chamado' => 'Este chamado já está cancelado.',
                ]);
            }

            $solicitanteResponsavel = $usuario->tipo === 'solicitante'
                && $chamado->usuario_id === $usuario->id;

            if (! $solicitanteResponsavel && $usuario->tipo !== 'admin') {
                throw ValidationException::withMessages([
                    'usuario_id' => 'Apenas o solicitante responsável ou um administrador pode cancelar este chamado.',
                ]);
            }

            $statusAnterior = $chamado->status;

            $chamado->update([
                'status' => 'cancelado',
                'data_fechamento' => now(),
            ]);

            HistoricoChamado::create([
                'chamado_id' => $chamado->id,
                'usuario_id' => $usuario->id,
                'acao' => 'Chamado cancelado',
                'valor_anterior' => $statusAnterior,
                'valor_novo' => 'cancelado',
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
