<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Administrador;
use App\Models\Monitora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_autenticar_com_credenciais_validas()
    {
        $user = User::create([
            'login' => 'admin@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'admin',
        ]);
        Administrador::create([
            'user_id' => $user->id,
            'setor' => 'Coordenação',
            'nivel_acesso' => 5,
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'admin@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'user' => ['id', 'login', 'perfil', 'detalhes']
            ]);
    }

    public function test_login_falha_com_credenciais_invalidas()
    {
        User::create([
            'login' => 'admin@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'admin',
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'admin@teste.com',
            'password' => 'senha_errada',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais invalidas.']);
    }

    public function test_usuario_autenticado_pode_obter_seus_dados()
    {
        $user = User::create([
            'login' => 'monitora@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'monitora',
        ]);
        Monitora::create([
            'user_id' => $user->id,
            'classes' => 'Turma 1A',
            'cpf' => '111.222.333-44',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'login' => 'monitora@teste.com',
                'perfil' => 'monitora',
            ]);
    }

    public function test_usuario_autenticado_pode_fazer_logout()
    {
        $user = User::create([
            'login' => 'monitora@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'monitora',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sessao encerrada com sucesso.']);

        $this->assertCount(0, $user->tokens);
    }
}
