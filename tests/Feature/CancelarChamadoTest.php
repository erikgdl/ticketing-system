<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelarChamadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitante_pode_cancelar_o_proprio_chamado(): void
    {
        $solicitante = User::factory()->create(['tipo' => 'solicitante']);
        $chamado = $this->criarChamado($solicitante);

        $response = $this->postJson("/api/chamados/{$chamado->id}/cancelar", [
            'usuario_id' => $solicitante->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'cancelado')
            ->assertJsonPath('historicos.0.acao', 'Chamado cancelado')
            ->assertJsonPath('historicos.0.valor_anterior', 'aberto')
            ->assertJsonPath('historicos.0.valor_novo', 'cancelado');

        $this->assertNotNull($response->json('data_fechamento'));

        $this->assertDatabaseHas('historico_chamados', [
            'chamado_id' => $chamado->id,
            'usuario_id' => $solicitante->id,
            'acao' => 'Chamado cancelado',
            'valor_anterior' => 'aberto',
            'valor_novo' => 'cancelado',
        ]);
    }

    public function test_admin_pode_cancelar_chamado_de_outro_usuario(): void
    {
        $solicitante = User::factory()->create(['tipo' => 'solicitante']);
        $admin = User::factory()->create(['tipo' => 'admin']);
        $chamado = $this->criarChamado($solicitante, 'em_atendimento');

        $this->postJson("/api/chamados/{$chamado->id}/cancelar", [
            'usuario_id' => $admin->id,
        ])->assertOk()->assertJsonPath('status', 'cancelado');
    }

    public function test_tecnico_nao_pode_cancelar_chamado_de_outro_usuario(): void
    {
        $solicitante = User::factory()->create(['tipo' => 'solicitante']);
        $tecnico = User::factory()->create(['tipo' => 'tecnico']);
        $chamado = $this->criarChamado($solicitante);

        $this->postJson("/api/chamados/{$chamado->id}/cancelar", [
            'usuario_id' => $tecnico->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('usuario_id');

        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => 'aberto',
            'data_fechamento' => null,
        ]);
    }

    public function test_chamado_finalizado_nao_pode_ser_cancelado(): void
    {
        $solicitante = User::factory()->create(['tipo' => 'solicitante']);
        $chamado = $this->criarChamado($solicitante, 'finalizado');

        $this->postJson("/api/chamados/{$chamado->id}/cancelar", [
            'usuario_id' => $solicitante->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('chamado');

        $this->assertDatabaseMissing('historico_chamados', [
            'chamado_id' => $chamado->id,
            'acao' => 'Chamado cancelado',
        ]);
    }

    public function test_chamado_cancelado_nao_pode_ser_cancelado_novamente(): void
    {
        $solicitante = User::factory()->create(['tipo' => 'solicitante']);
        $chamado = $this->criarChamado($solicitante, 'cancelado');

        $this->postJson("/api/chamados/{$chamado->id}/cancelar", [
            'usuario_id' => $solicitante->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('chamado');

        $this->assertDatabaseMissing('historico_chamados', [
            'chamado_id' => $chamado->id,
            'acao' => 'Chamado cancelado',
        ]);
    }

    private function criarChamado(User $solicitante, string $status = 'aberto'): Chamado
    {
        $categoria = Categoria::create([
            'nome' => 'Suporte',
            'descricao' => 'Suporte técnico',
        ]);

        return Chamado::create([
            'titulo' => 'Problema no sistema',
            'descricao' => 'Não consigo acessar o sistema.',
            'status' => $status,
            'prioridade' => 'media',
            'usuario_id' => $solicitante->id,
            'categoria_id' => $categoria->id,
            'data_abertura' => now(),
            'data_fechamento' => $status === 'finalizado' ? now() : null,
        ]);
    }
}
