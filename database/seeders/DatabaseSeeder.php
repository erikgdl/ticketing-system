<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        foreach ([
            ['name' => 'Usuário Solicitante', 'email' => 'usuario@nexoti.com', 'tipo' => 'solicitante'],
            ['name' => 'Técnico NexoTI', 'email' => 'tecnico@nexoti.com', 'tipo' => 'tecnico'],
            ['name' => 'Administrador NexoTI', 'email' => 'admin@nexoti.com', 'tipo' => 'admin'],
        ] as $usuario) {
            User::updateOrCreate(
                ['email' => $usuario['email']],
                [...$usuario, 'password' => 'password']
            );
        }
    }
}
