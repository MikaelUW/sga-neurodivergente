<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Monitora;
use App\Models\Aluno;
use App\Models\Ocorrencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class OcorrenciaTest extends TestCase
{
    use RefreshDatabase;

    private function createMonitoraUser(): User
    {
        $user = User::create([
            'login' => 'monitora@escola.edu.br',
            'password' => Hash::make('senha123'),
            'perfil' => 'monitora',
        ]);

        Monitora::create([
            'user_id' => $user->id,
            'classes' => 'Turma 2B',
            'cpf' => '000.111.222-33',
        ]);

        return $user;
    }

    public function test_monitora_pode_registrar_ocorrencia_e_fatos_sao_criptografados_no_banco()
    {
        $user = $this->createMonitoraUser();
        $token = $user->createToken('test')->plainTextToken;

        $aluno = Aluno::create(['nome' => 'Rafael Ramos']);

        $relatoOriginal = "O aluno teve uma crise devido a mudanças repentinas na rotina.";
        $intervencaoOriginal = "Redirecionamento para a sala de descompressão.";

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ocorrencias', [
                'aluno_id' => $aluno->id,
                'descricao_fatos' => $relatoOriginal,
                'tipo_intervencao' => $intervencaoOriginal,
            ]);

        $response->assertStatus(201);

        $ocorrenciaCriada = Ocorrencia::first();
        $this->assertEquals($relatoOriginal, $ocorrenciaCriada->descricao_fatos);

        // Verificar no banco de dados bruto se os dados NÃO estão em texto claro (estão cifrados)
        $rawRow = DB::table('ocorrencias')->where('id', $ocorrenciaCriada->id)->first();
        $this->assertNotEquals($relatoOriginal, $rawRow->descricao_fatos);
    }

    public function test_usuario_autenticado_pode_consultar_historico_de_ocorrencias_decifrado()
    {
        $user = $this->createMonitoraUser();
        $token = $user->createToken('test')->plainTextToken;

        $aluno = Aluno::create(['nome' => 'Rafael Ramos']);
        
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/ocorrencias', [
                'aluno_id' => $aluno->id,
                'descricao_fatos' => 'Relato Confidencial',
                'tipo_intervencao' => 'Intervenção Positiva',
            ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/alunos/' . $aluno->id . '/ocorrencias');

        $response->assertStatus(200)
            ->assertJsonPath('ocorrencias.0.descricao_fatos', 'Relato Confidencial')
            ->assertJsonPath('ocorrencias.0.tipo_intervencao', 'Intervenção Positiva');
    }
}
