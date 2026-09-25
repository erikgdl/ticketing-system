<?php

namespace Tests\Feature;

use Tests\Feature\Support\ApiTestCase;

class PermissoesTest extends ApiTestCase
{
    public function test_solicitante_lista_e_abre_apenas_os_proprios_chamados(): void
    {
        $solicitante = $this->autenticar();
        $outroChamado = $this->criarChamado();
        $categoria = $this->criarCategoria(['nome' => 'Acesso']);

        $response = $this->postJson('/api/chamados', [
            'titulo' => 'Falha de acesso',
            'descricao' => 'Não consigo acessar.',
            'prioridade' => 'alta',
            'categoria_id' => $categoria->id,
            'usuario_id' => $outroChamado->usuario_id,
        ])->assertCreated()->assertJsonPath('usuario_id', $solicitante->id);

        $this->getJson('/api/chamados')
            ->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', $response->json('id'));

        $this->getJson("/api/chamados/{$outroChamado->id}")->assertForbidden();
    }

    public function test_solicitante_pode_comentar_apenas_no_proprio_chamado(): void
    {
        $solicitante = $this->autenticar();
        $proprio = $this->criarChamado(['usuario_id' => $solicitante->id]);
        $outro = $this->criarChamado();

        $this->postJson("/api/chamados/{$proprio->id}/comentarios", [
            'mensagem' => 'Ainda preciso de ajuda.',
            'usuario_id' => 999,
        ])->assertOk()->assertJsonPath('comentarios.0.usuario_id', $solicitante->id);

        $this->postJson("/api/chamados/{$outro->id}/comentarios", [
            'mensagem' => 'Mensagem indevida',
        ])->assertForbidden();
    }

    public function test_solicitante_nao_acessa_acoes_da_equipe(): void
    {
        $this->autenticar();
        $chamado = $this->criarChamado();

        $this->postJson("/api/chamados/{$chamado->id}/assumir")->assertForbidden();
        $this->postJson("/api/chamados/{$chamado->id}/finalizar")->assertForbidden();
        $this->postJson("/api/chamados/{$chamado->id}/cancelar")->assertForbidden();
        $this->deleteJson("/api/chamados/{$chamado->id}")->assertForbidden();
        $this->postJson('/api/categorias', ['nome' => 'Restrita'])->assertForbidden();
    }

    public function test_tecnico_e_admin_gerenciam_categorias(): void
    {
        $this->autenticar('tecnico');

        $categoria = $this->postJson('/api/categorias', [
            'nome' => 'Infraestrutura',
            'descricao' => 'Rede e equipamentos',
        ])->assertCreated()->json();

        $this->autenticar('admin');

        $this->putJson("/api/categorias/{$categoria['id']}", [
            'nome' => 'Redes',
            'descricao' => 'Conectividade',
        ])->assertOk()->assertJsonPath('nome', 'Redes');

        $this->deleteJson("/api/categorias/{$categoria['id']}")->assertOk();
    }

    public function test_tecnico_remove_chamado_da_lista_sem_apagar_do_banco(): void
    {
        $this->autenticar('tecnico');
        $chamado = $this->criarChamado();

        $this->deleteJson("/api/chamados/{$chamado->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Chamado removido da lista com sucesso.');

        $this->assertSoftDeleted('chamados', ['id' => $chamado->id]);
        $this->assertDatabaseHas('chamados', ['id' => $chamado->id]);
        $this->getJson('/api/chamados')->assertOk()->assertJsonCount(0);
        $this->getJson("/api/chamados/{$chamado->id}")->assertNotFound();
    }

    public function test_admin_tambem_pode_remover_chamado_da_lista(): void
    {
        $this->autenticar('admin');
        $chamado = $this->criarChamado();

        $this->deleteJson("/api/chamados/{$chamado->id}")->assertOk();
        $this->assertSoftDeleted('chamados', ['id' => $chamado->id]);
    }
}
