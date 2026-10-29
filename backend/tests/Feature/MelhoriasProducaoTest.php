<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Administrador;
use App\Models\Monitora;
use App\Models\Aluno;

class MelhoriasProducaoTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $u = User::factory()->admin()->create();
        Administrador::create(['user_id' => $u->id, 'setor' => 'TI', 'nivel_acesso' => 5]);
        return $u->createToken('t')->plainTextToken;
    }

    public function test_listagem_de_alunos_e_paginada(): void
    {
        Aluno::factory()->count(3)->create();
        $token = $this->adminToken();

        $resp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/alunos');

        $resp->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
        $this->assertEquals(3, $resp->json('total'));
    }

    public function test_busca_por_nome_usa_blind_index(): void
    {
        Aluno::factory()->create(['nome' => 'Mariana Alves']);
        Aluno::factory()->create(['nome' => 'Pedro Santos']);
        $token = $this->adminToken();

        $resp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/alunos?busca=mariana alves');

        $resp->assertStatus(200);
        $this->assertEquals(1, $resp->json('total'));
        $this->assertEquals('Mariana Alves', $resp->json('data.0.nome'));
    }

    public function test_nome_e_persistido_cifrado_mas_hash_e_deterministico(): void
    {
        $aluno = Aluno::factory()->create(['nome' => 'Joao Teste']);

        // Valor cru na coluna nome deve ser diferente do texto claro (cifrado)
        $raw = \DB::table('alunos')->where('id', $aluno->id)->value('nome');
        $this->assertNotEquals('Joao Teste', $raw);

        // O accessor decifra corretamente
        $this->assertEquals('Joao Teste', $aluno->fresh()->nome);

        // nome_hash preenchido e determinístico
        $hash = \DB::table('alunos')->where('id', $aluno->id)->value('nome_hash');
        $this->assertNotEmpty($hash);
        $esperado = (new \App\Services\CriptografiaService())->blindIndex('Joao Teste');
        $this->assertEquals($esperado, $hash);
    }

    public function test_admin_pode_registrar_ocorrencia_sem_monitora(): void
    {
        $aluno = Aluno::factory()->create();
        $token = $this->adminToken();

        $resp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ocorrencias', [
                'aluno_id' => $aluno->id,
                'descricao_fatos' => 'Teste de ocorrencia por admin',
                'tipo_intervencao' => 'Observacao',
            ]);

        $resp->assertStatus(201);
        $this->assertDatabaseHas('ocorrencias', [
            'aluno_id' => $aluno->id,
            'monitora_id' => null,
        ]);
    }

    public function test_monitora_nao_vinculada_nao_exporta_lgpd(): void
    {
        $aluno = Aluno::factory()->create();
        $mUser = User::factory()->monitora()->create();
        Monitora::create(['user_id' => $mUser->id, 'classes' => 'A', 'cpf' => '1']);
        $token = $mUser->createToken('t')->plainTextToken;

        $resp = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/alunos/{$aluno->id}/exportar-lgpd");

        $resp->assertStatus(403);
    }

    public function test_login_gera_log_de_auditoria(): void
    {
        $u = User::factory()->admin()->create(['login' => 'audit@test.com']);
        Administrador::create(['user_id' => $u->id, 'setor' => 'TI', 'nivel_acesso' => 5]);

        $this->postJson('/api/login', ['login' => 'audit@test.com', 'password' => 'password123'])
            ->assertStatus(200);

        $this->assertDatabaseHas('acesso_logs', ['recurso' => 'Auth', 'acao' => 'login']);
    }
}
