<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aluno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AlunoTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        return User::create([
            'login' => 'admin@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'admin',
        ]);
    }

    private function createMonitoraUser(): User
    {
        return User::create([
            'login' => 'monitora@teste.com',
            'password' => Hash::make('senha123'),
            'perfil' => 'monitora',
        ]);
    }

    public function test_admin_pode_cadastrar_novo_aluno()
    {
        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/alunos', [
                'nome' => 'Lucas Pereira',
                'data_nascimento' => '2015-08-20',
                'necessidades_especiais' => 'TEA Nivel 2',
                'observacoes_gerais' => 'Sensibilidade tátil',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nome', 'Lucas Pereira');

        $this->assertDatabaseHas('alunos', [
            'data_nascimento' => '2015-08-20',
        ]);
    }

    public function test_monitora_nao_pode_cadastrar_aluno()
    {
        $monitora = $this->createMonitoraUser();
        $token = $monitora->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/alunos', [
                'nome' => 'Lucas Pereira',
            ]);

        $response->assertStatus(403);
    }

    public function test_usuario_autenticado_pode_listar_e_visualizar_alunos()
    {
        $monitora = $this->createMonitoraUser();
        \App\Models\Monitora::create([
            'user_id' => $monitora->id,
            'classes' => 'Turma X',
            'cpf' => '555.666.777-88',
        ]);
        $token = $monitora->createToken('test')->plainTextToken;

        $aluno = Aluno::create([
            'nome' => 'Maria Clara',
            'data_nascimento' => '2017-03-10',
            'necessidades_especiais' => 'TDAH',
        ]);

        // Vincula a monitora ao aluno (regra de minimização LGPD)
        $monitora->monitora->alunos()->attach($aluno->id);

        $responseList = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/alunos');

        $responseList->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $responseShow = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/alunos/' . $aluno->id);

        $responseShow->assertStatus(200)
            ->assertJsonPath('data.nome', 'Maria Clara');
    }

    public function test_admin_pode_atualizar_e_remover_aluno()
    {
        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;

        $aluno = Aluno::create([
            'nome' => 'Carlos Eduardo',
        ]);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/alunos/' . $aluno->id, [
                'nome' => 'Carlos Eduardo Atualizado',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.nome', 'Carlos Eduardo Atualizado');

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/alunos/' . $aluno->id);

        $deleteResponse->assertStatus(200);

        // Remoção é SoftDelete (preservação regulatória LGPD): o registro
        // permanece no banco marcado como deletado.
        $this->assertSoftDeleted('alunos', [
            'id' => $aluno->id,
        ]);
    }
}
