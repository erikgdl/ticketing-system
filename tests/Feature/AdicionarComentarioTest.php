<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Support\ApiTestCase;

class AdicionarComentarioTest extends ApiTestCase
{
    public function test_pode_adicionar_comentario_e_historico_no_chamado_correto(): void
    {
        $outroChamado = $this->criarChamado();
        $chamado = $this->criarChamado();
        $autor = $this->criarUsuario('tecnico');

        $response = $this->postJson("/api/chamados/{$chamado->id}/comentarios", [
            'usuario_id' => $autor->id,
            'mensagem' => 'Estamos verificando o problema.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('id', $chamado->id)
            ->assertJsonPath('comentarios.0.usuario_id', $autor->id)
            ->assertJsonPath('comentarios.0.mensagem', 'Estamos verificando o problema.')
            ->assertJsonPath('historicos.0.acao', 'Comentário adicionado');

        $this->assertDatabaseHas('comentarios', [
            'chamado_id' => $chamado->id,
            'usuario_id' => $autor->id,
            'mensagem' => 'Estamos verificando o problema.',
        ]);
        $this->assertDatabaseHas('historico_chamados', [
            'chamado_id' => $chamado->id,
            'usuario_id' => $autor->id,
            'acao' => 'Comentário adicionado',
        ]);
        $this->assertDatabaseMissing('historico_chamados', [
            'chamado_id' => $outroChamado->id,
            'acao' => 'Comentário adicionado',
        ]);
    }

    #[DataProvider('statusEncerrados')]
    public function test_nao_pode_comentar_em_chamado_encerrado(string $status): void
    {
        $chamado = $this->criarChamado(['status' => $status]);
        $autor = $this->criarUsuario();

        $this->postJson("/api/chamados/{$chamado->id}/comentarios", [
            'usuario_id' => $autor->id,
            'mensagem' => 'Novo comentário',
        ])->assertUnprocessable()->assertJsonValidationErrors('chamado');

        $this->assertDatabaseMissing('comentarios', [
            'chamado_id' => $chamado->id,
            'mensagem' => 'Novo comentário',
        ]);
    }

    public function test_comentario_exige_usuario_e_mensagem(): void
    {
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/comentarios", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario_id', 'mensagem']);
    }

    public function test_comentario_rejeita_usuario_inexistente(): void
    {
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/comentarios", [
            'usuario_id' => 999,
            'mensagem' => 'Comentário',
        ])->assertUnprocessable()->assertJsonValidationErrors('usuario_id');
    }

    public static function statusEncerrados(): array
    {
        return [
            'finalizado' => ['finalizado'],
            'cancelado' => ['cancelado'],
        ];
    }
}
