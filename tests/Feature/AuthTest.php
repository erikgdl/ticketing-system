<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitante_entra_apenas_no_portal_correto(): void
    {
        User::factory()->create([
            'email' => 'usuario@example.com',
            'password' => 'password',
            'tipo' => 'solicitante',
        ]);

        $this->postJson('/api/login', [
            'email' => 'usuario@example.com',
            'password' => 'password',
            'portal' => 'solicitante',
        ])->assertOk()->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.tipo', 'solicitante');

        $this->postJson('/api/login', [
            'email' => 'usuario@example.com',
            'password' => 'password',
            'portal' => 'equipe',
        ])->assertUnprocessable();
    }

    public function test_tecnico_e_admin_entram_no_portal_da_equipe(): void
    {
        foreach (['tecnico', 'admin'] as $tipo) {
            $usuario = User::factory()->create(['password' => 'password', 'tipo' => $tipo]);

            $this->postJson('/api/login', [
                'email' => $usuario->email,
                'password' => 'password',
                'portal' => 'equipe',
            ])->assertOk()->assertJsonPath('user.tipo', $tipo);
        }
    }

    public function test_rotas_do_sistema_exigem_autenticacao(): void
    {
        $this->getJson('/api/chamados')->assertUnauthorized();
        $this->getJson('/api/categorias')->assertUnauthorized();
    }
}
