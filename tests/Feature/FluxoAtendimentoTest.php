<?php

namespace Tests\Feature;

use Tests\Feature\Support\ApiTestCase;

class FluxoAtendimentoTest extends ApiTestCase
{
    public function test_tecnico_pode_assumir_comentar_e_finalizar_chamado(): void
    {
        $tecnico = $this->autenticar('tecnico');
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/assumir", ['tecnico_id' => 999])
            ->assertOk()
            ->assertJsonPath('status', 'em_atendimento')
            ->assertJsonPath('tecnico_id', $tecnico->id);

        $this->postJson("/api/chamados/{$chamado->id}/comentarios", [
            'mensagem' => 'Estamos analisando.',
            'usuario_id' => 999,
        ])->assertOk()->assertJsonPath('comentarios.0.usuario_id', $tecnico->id);

        $this->postJson("/api/chamados/{$chamado->id}/finalizar", ['tecnico_id' => 999])
            ->assertOk()->assertJsonPath('status', 'finalizado');
    }

    public function test_outro_tecnico_nao_finaliza_chamado(): void
    {
        $responsavel = $this->criarUsuario('tecnico');
        $outro = $this->autenticar('tecnico');
        $chamado = $this->criarChamado([
            'status' => 'em_atendimento',
            'tecnico_id' => $responsavel->id,
        ]);

        $this->postJson("/api/chamados/{$chamado->id}/finalizar")
            ->assertUnprocessable()->assertJsonValidationErrors('tecnico_id');

        $this->assertNotEquals($responsavel->id, $outro->id);
    }

    public function test_admin_visualiza_comenta_e_cancela_qualquer_chamado(): void
    {
        $admin = $this->autenticar('admin');
        $chamado = $this->criarChamado();

        $this->getJson('/api/chamados')->assertOk()->assertJsonCount(1);
        $this->postJson("/api/chamados/{$chamado->id}/comentarios", [
            'mensagem' => 'Registro administrativo.',
        ])->assertOk()->assertJsonPath('comentarios.0.usuario_id', $admin->id);

        $this->postJson("/api/chamados/{$chamado->id}/cancelar")
            ->assertOk()->assertJsonPath('status', 'cancelado');
    }
}
