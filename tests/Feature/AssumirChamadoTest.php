<?php

namespace Tests\Feature;

use Tests\Feature\Support\ApiTestCase;

class AssumirChamadoTest extends ApiTestCase
{
    public function test_tecnico_pode_assumir_chamado_aberto(): void
    {
        $tecnico = $this->criarUsuario('tecnico');
        $chamado = $this->criarChamado();

        $response = $this->postJson("/api/chamados/{$chamado->id}/assumir", [
            'tecnico_id' => $tecnico->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'em_atendimento')
            ->assertJsonPath('tecnico.id', $tecnico->id)
            ->assertJsonPath('historicos.0.acao', 'Chamado assumido')
            ->assertJsonPath('historicos.0.valor_anterior', 'aberto')
            ->assertJsonPath('historicos.0.valor_novo', 'em_atendimento');

        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'tecnico_id' => $tecnico->id,
            'status' => 'em_atendimento',
        ]);
        $this->assertDatabaseHas('historico_chamados', [
            'chamado_id' => $chamado->id,
            'usuario_id' => $tecnico->id,
            'acao' => 'Chamado assumido',
        ]);
    }

    public function test_solicitante_nao_pode_assumir_chamado(): void
    {
        $solicitante = $this->criarUsuario();
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/assumir", [
            'tecnico_id' => $solicitante->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('tecnico_id');

        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => 'aberto',
            'tecnico_id' => null,
        ]);
    }

    public function test_chamado_que_nao_esta_aberto_nao_pode_ser_assumido(): void
    {
        $tecnico = $this->criarUsuario('tecnico');
        $chamado = $this->criarChamado(['status' => 'finalizado']);

        $this->postJson("/api/chamados/{$chamado->id}/assumir", [
            'tecnico_id' => $tecnico->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('chamado');
    }

    public function test_chamado_com_tecnico_nao_pode_ser_assumido_novamente(): void
    {
        $primeiroTecnico = $this->criarUsuario('tecnico');
        $segundoTecnico = $this->criarUsuario('tecnico');
        $chamado = $this->criarChamado(['tecnico_id' => $primeiroTecnico->id]);

        $this->postJson("/api/chamados/{$chamado->id}/assumir", [
            'tecnico_id' => $segundoTecnico->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('chamado');

        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'tecnico_id' => $primeiroTecnico->id,
        ]);
    }

    public function test_assumir_exige_tecnico_existente(): void
    {
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/assumir", [
            'tecnico_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors('tecnico_id');
    }
}
