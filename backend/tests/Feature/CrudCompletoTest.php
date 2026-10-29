<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Administrador;
use App\Models\Monitora;
use App\Models\Aluno;
use App\Models\Ocorrencia;
use App\Models\LaudoMedico;
use App\Models\Responsavel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CrudCompletoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        $u = User::factory()->admin()->create();
        Administrador::create(['user_id' => $u->id, 'setor' => 'TI', 'nivel_acesso' => 5]);
        return [$u, $u->createToken('t')->plainTextToken];
    }

    private function auth($token)
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    // ---------- Frente A: usuários/monitoras ----------

    public function test_admin_cria_monitora_com_usuario(): void
    {
        [, $token] = $this->admin();
        $resp = $this->auth($token)->postJson('/api/usuarios', [
            'login' => 'nova.monitora@escola.edu.br',
            'password' => 'senha123',
            'perfil' => 'monitora',
            'classes' => 'Turma 5A',
            'cpf' => '111.222.333-44',
        ]);
        $resp->assertStatus(201)->assertJsonPath('data.perfil', 'monitora');
        $this->assertDatabaseHas('users', ['login' => 'nova.monitora@escola.edu.br']);
        $this->assertDatabaseHas('monitoras', ['cpf' => '111.222.333-44']);

        // A nova monitora consegue logar
        $this->postJson('/api/login', ['login' => 'nova.monitora@escola.edu.br', 'password' => 'senha123'])
            ->assertStatus(200);
    }

    public function test_admin_cria_outro_admin(): void
    {
        [, $token] = $this->admin();
        $this->auth($token)->postJson('/api/usuarios', [
            'login' => 'outro.admin@escola.edu.br',
            'password' => 'senha123',
            'perfil' => 'admin',
            'setor' => 'Direcao',
            'nivel_acesso' => 4,
        ])->assertStatus(201)->assertJsonPath('data.perfil', 'admin');
    }

    public function test_admin_edita_e_remove_usuario(): void
    {
        [, $token] = $this->admin();
        $mUser = User::factory()->monitora()->create();
        Monitora::create(['user_id' => $mUser->id, 'classes' => 'A', 'cpf' => '999']);

        $this->auth($token)->putJson('/api/usuarios/' . $mUser->id, ['classes' => 'B, C'])
            ->assertStatus(200);
        $this->assertDatabaseHas('monitoras', ['user_id' => $mUser->id, 'classes' => 'B, C']);

        $this->auth($token)->deleteJson('/api/usuarios/' . $mUser->id)->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $mUser->id]);
    }

    public function test_admin_redefine_senha_de_usuario(): void
    {
        [, $token] = $this->admin();
        $mUser = User::factory()->monitora()->create(['login' => 'reset@escola.edu.br']);
        Monitora::create(['user_id' => $mUser->id, 'classes' => 'A', 'cpf' => '888']);

        $this->auth($token)->putJson('/api/usuarios/' . $mUser->id . '/senha', ['password' => 'novaSenha1'])
            ->assertStatus(200);
        $this->postJson('/api/login', ['login' => 'reset@escola.edu.br', 'password' => 'novaSenha1'])
            ->assertStatus(200);
    }

    public function test_admin_vincula_e_desvincula_aluno_de_monitora(): void
    {
        [, $token] = $this->admin();
        $mUser = User::factory()->monitora()->create();
        $monitora = Monitora::create(['user_id' => $mUser->id, 'classes' => 'A', 'cpf' => '777']);
        $aluno = Aluno::factory()->create();

        $this->auth($token)->postJson('/api/monitoras/' . $monitora->id . '/alunos', ['aluno_id' => $aluno->id])
            ->assertStatus(201);
        $this->assertDatabaseHas('aluno_monitora', ['monitora_id' => $monitora->id, 'aluno_id' => $aluno->id]);

        $this->auth($token)->deleteJson('/api/monitoras/' . $monitora->id . '/alunos/' . $aluno->id)
            ->assertStatus(200);
        $this->assertDatabaseMissing('aluno_monitora', ['monitora_id' => $monitora->id, 'aluno_id' => $aluno->id]);
    }

    // ---------- Frente B: ocorrências ----------

    public function test_admin_ve_todas_as_ocorrencias(): void
    {
        [, $token] = $this->admin();
        $aluno = Aluno::factory()->create();
        Ocorrencia::create(['aluno_id' => $aluno->id, 'monitora_id' => null, 'data_hora' => now(), 'descricao_fatos' => 'x', 'tipo_intervencao' => 'y']);

        $resp = $this->auth($token)->getJson('/api/ocorrencias');
        $resp->assertStatus(200)->assertJsonStructure(['data', 'total']);
        $this->assertEquals(1, $resp->json('total'));
    }

    public function test_admin_edita_e_remove_ocorrencia(): void
    {
        [, $token] = $this->admin();
        $aluno = Aluno::factory()->create();
        $oc = Ocorrencia::create(['aluno_id' => $aluno->id, 'monitora_id' => null, 'data_hora' => now(), 'descricao_fatos' => 'original', 'tipo_intervencao' => 'z']);

        $this->auth($token)->putJson('/api/ocorrencias/' . $oc->id, ['descricao_fatos' => 'corrigido'])
            ->assertStatus(200);
        $this->assertEquals('corrigido', $oc->fresh()->descricao_fatos);

        $this->auth($token)->deleteJson('/api/ocorrencias/' . $oc->id)->assertStatus(200);
        $this->assertDatabaseMissing('ocorrencias', ['id' => $oc->id]);
    }

    public function test_monitora_nao_edita_ocorrencia_de_outra(): void
    {
        [, $adminToken] = $this->admin();
        $aluno = Aluno::factory()->create();
        // ocorrência registrada pelo admin (sem monitora)
        $oc = Ocorrencia::create(['aluno_id' => $aluno->id, 'monitora_id' => null, 'data_hora' => now(), 'descricao_fatos' => 'a', 'tipo_intervencao' => 'b']);

        $mUser = User::factory()->monitora()->create();
        $monitora = Monitora::create(['user_id' => $mUser->id, 'classes' => 'A', 'cpf' => '666']);
        $monitora->alunos()->attach($aluno->id);
        $mToken = $mUser->createToken('t')->plainTextToken;

        $this->auth($mToken)->putJson('/api/ocorrencias/' . $oc->id, ['descricao_fatos' => 'hack'])
            ->assertStatus(403);
    }

    public function test_monitora_nao_edita_ocorrencia_apos_24h(): void
    {
        $aluno = Aluno::factory()->create();
        $mUser = User::factory()->monitora()->create();
        $monitora = Monitora::create(['user_id' => $mUser->id, 'classes' => 'A', 'cpf' => '555']);
        $monitora->alunos()->attach($aluno->id);
        $mToken = $mUser->createToken('t')->plainTextToken;

        $oc = Ocorrencia::create(['aluno_id' => $aluno->id, 'monitora_id' => $monitora->id, 'data_hora' => now()->subDays(2), 'descricao_fatos' => 'antiga', 'tipo_intervencao' => 'b']);

        $this->auth($mToken)->putJson('/api/ocorrencias/' . $oc->id, ['descricao_fatos' => 'tardio'])
            ->assertStatus(403);
    }

    // ---------- Frente B: laudos ----------

    public function test_admin_lista_edita_e_remove_laudo(): void
    {
        Storage::fake('local');
        [, $token] = $this->admin();
        $aluno = Aluno::factory()->create();

        // cria via upload
        $file = UploadedFile::fake()->create('l.pdf', 50, 'application/pdf');
        $up = $this->auth($token)->postJson('/api/alunos/' . $aluno->id . '/laudos', [
            'arquivo' => $file, 'crm_medico' => 'CRM 1', 'especialidade' => 'Neuro',
        ]);
        $up->assertStatus(201);
        $laudoId = $up->json('data.id');

        // lista
        $this->auth($token)->getJson('/api/alunos/' . $aluno->id . '/laudos')
            ->assertStatus(200)->assertJsonPath('data.0.id', $laudoId);

        // edita metadados
        $this->auth($token)->putJson('/api/laudos/' . $laudoId, ['especialidade' => 'Psiquiatria'])
            ->assertStatus(200)->assertJsonPath('data.especialidade', 'Psiquiatria');

        // remove
        $this->auth($token)->deleteJson('/api/laudos/' . $laudoId)->assertStatus(200);
        $this->assertDatabaseMissing('laudos_medicos', ['id' => $laudoId]);
    }

    // ---------- Frente C: info operacional e responsável ----------

    public function test_lista_info_operacional_do_aluno(): void
    {
        [, $token] = $this->admin();
        $aluno = Aluno::factory()->create();
        $this->auth($token)->postJson('/api/alunos/' . $aluno->id . '/info-operacional', [
            'gatilho_comportamental' => 'ruido', 'acao_recomendada' => 'abafador', 'prioridade' => 'alta',
        ])->assertStatus(201);

        $this->auth($token)->getJson('/api/alunos/' . $aluno->id . '/info-operacional')
            ->assertStatus(200)->assertJsonPath('data.0.gatilho_comportamental', 'ruido');
    }

    public function test_admin_edita_responsavel(): void
    {
        [, $token] = $this->admin();
        $resp = Responsavel::create(['nome' => 'Carlos', 'cpf' => '1', 'email' => 'c@x.com', 'telefone' => '1']);

        $this->auth($token)->putJson('/api/responsaveis/' . $resp->id, ['telefone' => '(11) 90000-0000'])
            ->assertStatus(200)->assertJsonPath('data.telefone', '(11) 90000-0000');
    }
}
