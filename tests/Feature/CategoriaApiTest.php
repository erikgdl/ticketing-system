<?php

namespace Tests\Feature;

use Tests\Feature\Support\ApiTestCase;

class CategoriaApiTest extends ApiTestCase
{
    public function test_pode_criar_categoria(): void
    {
        $response = $this->postJson('/api/categorias', [
            'nome' => 'Infraestrutura',
            'descricao' => 'Rede e equipamentos',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('nome', 'Infraestrutura')
            ->assertJsonPath('descricao', 'Rede e equipamentos');

        $this->assertDatabaseHas('categorias', [
            'nome' => 'Infraestrutura',
            'descricao' => 'Rede e equipamentos',
        ]);
    }

    public function test_descricao_da_categoria_pode_ser_nula(): void
    {
        $this->postJson('/api/categorias', ['nome' => 'Software'])
            ->assertCreated()
            ->assertJsonPath('descricao', null);
    }

    public function test_nome_da_categoria_e_obrigatorio(): void
    {
        $this->postJson('/api/categorias', ['descricao' => 'Sem nome'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');
    }

    public function test_pode_listar_e_exibir_categorias(): void
    {
        $primeira = $this->criarCategoria(['nome' => 'Hardware']);
        $segunda = $this->criarCategoria(['nome' => 'Software']);

        $this->getJson('/api/categorias')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $primeira->id)
            ->assertJsonPath('1.id', $segunda->id);

        $this->getJson("/api/categorias/{$segunda->id}")
            ->assertOk()
            ->assertJsonPath('nome', 'Software');
    }

    public function test_pode_atualizar_categoria(): void
    {
        $categoria = $this->criarCategoria();

        $this->putJson("/api/categorias/{$categoria->id}", [
            'nome' => 'Acesso',
            'descricao' => 'Contas e permissões',
        ])->assertOk()->assertJsonPath('nome', 'Acesso');

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nome' => 'Acesso',
            'descricao' => 'Contas e permissões',
        ]);
    }

    public function test_pode_excluir_categoria(): void
    {
        $categoria = $this->criarCategoria();

        $this->deleteJson("/api/categorias/{$categoria->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Categoria deletada com sucesso.');

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_categoria_inexistente_retorna_404(): void
    {
        $this->getJson('/api/categorias/999')->assertNotFound();
    }
}
