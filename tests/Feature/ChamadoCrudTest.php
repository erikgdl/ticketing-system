<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\HistoricoChamado;
use Tests\Feature\Support\ApiTestCase;

class ChamadoCrudTest extends ApiTestCase
{
    public function test_pode_criar_chamado_com_estado_inicial_e_historico(): void
    {
        $solicitante = $this->criarUsuario();
        $categoria = $this->criarCategoria();

        $response = $this->postJson('/api/chamados', [
            'titulo' => 'Falha de acesso',
            'descricao' => 'Não consigo entrar no sistema.',
            'prioridade' => 'alta',
            'usuario_id' => $solicitante->id,
            'categoria_id' => $categoria->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('titulo', 'Falha de acesso')
            ->assertJsonPath('status', 'aberto')
            ->assertJsonPath('prioridade', 'alta')
            ->assertJsonPath('tecnico_id', null)
            ->assertJsonPath('usuario.id', $solicitante->id)
            ->assertJsonPath('categoria.id', $categoria->id)
            ->assertJsonPath('historicos.0.acao', 'Chamado criado')
            ->assertJsonPath('historicos.0.valor_novo', 'aberto');

        $chamadoId = $response->json('id');

        $this->assertNotNull($response->json('data_abertura'));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamadoId,
            'status' => 'aberto',
            'tecnico_id' => null,
        ]);
        $this->assertDatabaseHas('historico_chamados', [
            'chamado_id' => $chamadoId,
            'usuario_id' => $solicitante->id,
            'acao' => 'Chamado criado',
            'valor_anterior' => null,
            'valor_novo' => 'aberto',
        ]);
    }

    public function test_criacao_valida_campos_obrigatorios(): void
    {
        $this->postJson('/api/chamados', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'titulo',
                'descricao',
                'prioridade',
                'usuario_id',
                'categoria_id',
            ]);
    }

    public function test_criacao_rejeita_prioridade_e_relacionamentos_invalidos(): void
    {
        $this->postJson('/api/chamados', [
            'titulo' => 'Teste',
            'descricao' => 'Descrição',
            'prioridade' => 'critica',
            'usuario_id' => 999,
            'categoria_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'prioridade',
            'usuario_id',
            'categoria_id',
        ]);
    }

    public function test_pode_listar_e_exibir_chamado_com_relacionamentos(): void
    {
        $solicitante = $this->criarUsuario();
        $tecnico = $this->criarUsuario('tecnico');
        $categoria = $this->criarCategoria();
        $chamado = $this->criarChamado([
            'usuario_id' => $solicitante->id,
            'tecnico_id' => $tecnico->id,
            'categoria_id' => $categoria->id,
            'status' => 'em_atendimento',
        ]);

        Comentario::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $tecnico->id,
            'mensagem' => 'Em análise.',
        ]);
        HistoricoChamado::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $tecnico->id,
            'acao' => 'Chamado assumido',
            'valor_anterior' => 'aberto',
            'valor_novo' => 'em_atendimento',
        ]);

        $this->getJson('/api/chamados')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.usuario.id', $solicitante->id)
            ->assertJsonPath('0.tecnico.id', $tecnico->id)
            ->assertJsonPath('0.categoria.id', $categoria->id)
            ->assertJsonPath('0.comentarios.0.mensagem', 'Em análise.')
            ->assertJsonPath('0.historicos.0.acao', 'Chamado assumido');

        $this->getJson("/api/chamados/{$chamado->id}")
            ->assertOk()
            ->assertJsonPath('id', $chamado->id)
            ->assertJsonPath('usuario.id', $solicitante->id)
            ->assertJsonPath('tecnico.id', $tecnico->id);
    }

    public function test_pode_atualizar_dados_basicos_do_chamado(): void
    {
        $chamado = $this->criarChamado();
        $novoSolicitante = $this->criarUsuario();
        $novaCategoria = $this->criarCategoria(['nome' => 'Sistemas']);

        $this->putJson("/api/chamados/{$chamado->id}", [
            'titulo' => 'Título atualizado',
            'descricao' => 'Descrição atualizada',
            'prioridade' => 'urgente',
            'usuario_id' => $novoSolicitante->id,
            'categoria_id' => $novaCategoria->id,
        ])->assertOk()
            ->assertJsonPath('titulo', 'Título atualizado')
            ->assertJsonPath('prioridade', 'urgente')
            ->assertJsonPath('status', 'aberto');

        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'titulo' => 'Título atualizado',
            'descricao' => 'Descrição atualizada',
            'prioridade' => 'urgente',
            'usuario_id' => $novoSolicitante->id,
            'categoria_id' => $novaCategoria->id,
        ]);
    }

    public function test_patch_atualmente_exige_todos_os_campos(): void
    {
        $chamado = $this->criarChamado();

        $this->patchJson("/api/chamados/{$chamado->id}", [
            'titulo' => 'Somente o título',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'descricao',
            'prioridade',
            'usuario_id',
            'categoria_id',
        ]);
    }

    public function test_exclusao_remove_chamado_com_comentarios_e_historico(): void
    {
        $chamado = $this->criarChamado();
        $autor = $this->criarUsuario();

        $comentario = Comentario::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $autor->id,
            'mensagem' => 'Comentário',
        ]);
        $historico = HistoricoChamado::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $autor->id,
            'acao' => 'Teste',
        ]);

        $this->deleteJson("/api/chamados/{$chamado->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Chamado deletado com sucesso.');

        $this->assertDatabaseMissing('chamados', ['id' => $chamado->id]);
        $this->assertDatabaseMissing('comentarios', ['id' => $comentario->id]);
        $this->assertDatabaseMissing('historico_chamados', ['id' => $historico->id]);
    }

    public function test_chamado_inexistente_retorna_404(): void
    {
        $this->getJson('/api/chamados/999')->assertNotFound();
    }
}
