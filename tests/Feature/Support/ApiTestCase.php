<?php

namespace Tests\Feature\Support;

use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function criarUsuario(string $tipo = 'solicitante'): User
    {
        return User::factory()->create(['tipo' => $tipo]);
    }

    protected function autenticar(string $tipo = 'solicitante'): User
    {
        $usuario = $this->criarUsuario($tipo);
        $this->actingAs($usuario, 'sanctum');

        return $usuario;
    }

    protected function criarCategoria(array $atributos = []): Categoria
    {
        return Categoria::create(array_merge([
            'nome' => 'Suporte',
            'descricao' => 'Suporte técnico',
        ], $atributos));
    }

    protected function criarChamado(array $atributos = []): Chamado
    {
        return Chamado::create(array_merge([
            'titulo' => 'Problema no sistema',
            'descricao' => 'Não consigo acessar o sistema.',
            'status' => 'aberto',
            'prioridade' => 'media',
            'usuario_id' => $this->criarUsuario()->id,
            'categoria_id' => $this->criarCategoria()->id,
            'data_abertura' => now(),
        ], $atributos));
    }
}
