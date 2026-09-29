<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Administrador;
use App\Models\Monitora;
use App\Models\Aluno;
use App\Models\Responsavel;
use App\Models\AcessoLog;
use App\Models\LaudoMedico;
use App\Services\CriptografiaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LgpdAdequacaoTest extends TestCase
{
    use RefreshDatabase;

    private $adminUser;
    private $adminToken;
    private $monitoraUser;
    private $monitoraToken;
    private $monitora;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar Usuário Admin
        $this->adminUser = User::create([
            'login' => 'admin.lgpd@escola.edu.br',
            'password' => bcrypt('password123'),
            'perfil' => 'admin',
        ]);
        Administrador::create([
            'user_id' => $this->adminUser->id,
            'cargo' => 'Diretor',
            'cpf' => '000.000.000-00',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin_token')->plainTextToken;

        // Criar Usuário Monitora
        $this->monitoraUser = User::create([
            'login' => 'monitora.lgpd@escola.edu.br',
            'password' => bcrypt('password123'),
            'perfil' => 'monitora',
        ]);
        $this->monitora = Monitora::create([
            'user_id' => $this->monitoraUser->id,
            'classes' => 'Turma A',
            'cpf' => '111.111.111-11',
        ]);
        $this->monitoraToken = $this->monitoraUser->createToken('monitora_token')->plainTextToken;
    }

    public function test_cadastro_de_aluno_registra_responsavel_e_consentimento_lgpd()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/alunos', [
                'nome' => 'Gabriel Souza',
                'data_nascimento' => '2017-08-20',
                'necessidades_especiais' => 'TEA Nível 2',
                'observacoes_gerais' => 'Sensível a luzes piscantes',
                'responsavel_nome' => 'Carlos Souza',
                'responsavel_cpf' => '999.888.777-66',
                'responsavel_email' => 'carlos@exemplo.com',
                'responsavel_telefone' => '(11) 98888-7777',
                'consentimento_lgpd_assinado' => true,
                'versao_termo_lgpd' => '1.0',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.consentimento_lgpd_assinado', true)
            ->assertJsonPath('data.responsavel.nome', 'Carlos Souza');

        $this->assertDatabaseHas('responsaveis', ['cpf' => '999.888.777-66']);
        $this->assertDatabaseHas('acesso_logs', ['recurso' => 'Aluno', 'acao' => 'criacao']);
    }

    public function test_laudo_medico_armazenado_no_storage_privado_e_gera_log_de_auditoria()
    {
        Storage::fake('local');

        $aluno = Aluno::create(['nome' => 'Aluno Teste Laudo']);

        $file = UploadedFile::fake()->create('laudo_neurologico.pdf', 500, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/api/alunos/{$aluno->id}/laudos", [
                'arquivo' => $file,
                'crm_medico' => 'CRM/SP 123456',
                'especialidade' => 'Neuropediatria',
            ]);

        $response->assertStatus(201);

        $laudo = LaudoMedico::first();
        $this->assertNotNull($laudo);

        // Confirmar que foi salvo no storage privado 'local'
        Storage::disk('local')->assertExists($laudo->path_arquivo);

        // Verificar Log de Auditoria LGPD
        $this->assertDatabaseHas('acesso_logs', [
            'aluno_id' => $aluno->id,
            'recurso' => 'LaudoMedico',
            'acao' => 'criacao',
        ]);
    }

    public function test_download_de_laudo_medico_valida_permissao_de_monitora()
    {
        Storage::fake('local');

        $aluno = Aluno::create(['nome' => 'Aluno Protegido']);
        $path = Storage::disk('local')->putFile('laudos_privados', UploadedFile::fake()->create('laudo.pdf', 100));

        $laudo = LaudoMedico::create([
            'aluno_id' => $aluno->id,
            'path_arquivo' => $path,
            'crm_medico' => 'CRM/SP 999',
            'especialidade' => 'Pediatria',
            'data_upload' => now(),
        ]);

        // 1. Tentar baixar sem estar vinculada ao aluno -> Retorna 403 Forbidden
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->monitoraToken)
            ->getJson("/api/laudos/{$laudo->id}/download");
        $response1->assertStatus(403);

        // 2. Vincular monitora ao aluno e tentar novamente -> Download Permitido + Log Gerado
        $this->monitora->alunos()->attach($aluno->id);

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->monitoraToken)
            ->getJson("/api/laudos/{$laudo->id}/download");
        $response2->assertStatus(200);

        // Verificar Log de Auditoria LGPD no Download
        $this->assertDatabaseHas('acesso_logs', [
            'aluno_id' => $aluno->id,
            'recurso' => 'LaudoMedico',
            'acao' => 'download',
            'user_id' => $this->monitoraUser->id,
        ]);
    }

    public function test_exclusao_de_aluno_utiliza_soft_deletes()
    {
        $aluno = Aluno::create(['nome' => 'Aluno Soft Delete']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/api/alunos/{$aluno->id}");

        $response->assertStatus(200);

        // Registro continua no banco marcado como deletado (SoftDelete)
        $this->assertSoftDeleted('alunos', ['id' => $aluno->id]);
    }

    public function test_exportacao_de_dados_para_portabilidade_lgpd()
    {
        $aluno = Aluno::create([
            'nome' => 'Aluno Portabilidade',
            'consentimento_lgpd_assinado' => true,
            'versao_termo_lgpd' => '1.0',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson("/api/alunos/{$aluno->id}/exportar-lgpd");

        $response->assertStatus(200)
            ->assertJsonPath('titular_aluno.nome', 'Aluno Portabilidade')
            ->assertJsonPath('consentimento_lgpd.assinado', true);

        // Verificar Log de Auditoria de Exportação
        $this->assertDatabaseHas('acesso_logs', [
            'aluno_id' => $aluno->id,
            'recurso' => 'Aluno',
            'acao' => 'exportacao_lgpd',
        ]);
    }

    public function test_administrador_pode_consultar_trilha_de_auditoria()
    {
        AcessoLog::create([
            'user_id' => $this->adminUser->id,
            'recurso' => 'Aluno',
            'acao' => 'visualizacao',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/auditoria/logs');

        $response->assertStatus(200)
            ->assertJsonPath('titulo', 'Trilha de Auditoria de Acessos a Dados Sensíveis (Art. 37 LGPD)');
    }
}
