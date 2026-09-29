<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class PermissaoTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::create([
            'login' => 'admin@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'admin',
        ]);

        Administrador::create([
            'user_id' => $user->id,
            'setor' => 'Gestão',
            'nivel_acesso' => 3,
        ]);

        return $user;
    }

    public function test_admin_pode_listar_usuarios_e_gerenciar_nivel_acesso()
    {
        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;

        $responseIndex = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/usuarios');

        $responseIndex->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $responseUpdate = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/usuarios/' . $admin->id . '/permissoes', [
                'nivel_acesso' => 5,
                'setor' => 'Diretoria Pedagógica',
            ]);

        $responseUpdate->assertStatus(200)
            ->assertJsonPath('data.administrador.nivel_acesso', 5)
            ->assertJsonPath('data.administrador.setor', 'Diretoria Pedagógica');
    }
}
