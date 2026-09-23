<?php

namespace Tests\Feature;

use Tests\Feature\Support\ApiTestCase;

class FinalizarChamadoTest extends ApiTestCase
{
    public function test_tecnico_responsavel_pode_finalizar_chamado_em_atendimento(): void
    {
        $tecnico = $this->criarUsuario('tecnico');
        $chamado = $this->criarChamado([
            'status' => 'em_atendimento',
            'tecnico_id' => $tecnico->id,
        ]);

        $response = $this->postJson("/api/chamados/{$chamado->id}/finalizar", [
            'tecnico_id' => $tecnico->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'finalizado')
            ->assertJsonPath('historicos.0.acao', 'Chamado finalizado')
            ->assertJsonPath('historicos.0.valor_anterior', 'em_atendimento')
            ->assertJsonPath('historicos.0.valor_novo', 'finalizado');

        $this->assertNotNull($response->json('data_fechamento'));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => 'finalizado',
        ]);
        $this->assertDatabaseHas('historico_chamados', [
            'chamado_id' => $chamado->id,
            'usuario_id' => $tecnico->id,
            'acao' => 'Chamado finalizado',
        ]);
    }

    public function test_outro_tecnico_nao_pode_finalizar_chamado(): void
    {
        $responsavel = $this->criarUsuario('tecnico');
        $outroTecnico = $this->criarUsuario('tecnico');
        $chamado = $this->criarChamado([
            'status' => 'em_atendimento',
            'tecnico_id' => $responsavel->id,
        ]);

        $this->postJson("/api/chamados/{$chamado->id}/finalizar", [
            'tecnico_id' => $outroTecnico->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('tecnico_id');

        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => 'em_atendimento',
            'tecnico_id' => $responsavel->id,
            'data_fechamento' => null,
        ]);
    }

    public function test_chamado_fora_de_atendimento_nao_pode_ser_finalizado(): void
    {
        $tecnico = $this->criarUsuario('tecnico');
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/finalizar", [
            'tecnico_id' => $tecnico->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('chamado');
    }

    public function test_finalizacao_exige_tecnico_existente(): void
    {
        $chamado = $this->criarChamado(['status' => 'em_atendimento']);

        $this->postJson("/api/chamados/{$chamado->id}/finalizar", [
            'tecnico_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors('tecnico_id');
    }
}
