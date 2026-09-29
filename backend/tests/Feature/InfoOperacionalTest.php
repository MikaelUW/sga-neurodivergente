<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aluno;
use App\Models\InfoOperacional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class InfoOperacionalTest extends TestCase
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

    public function test_admin_pode_vincular_info_operacional_ao_aluno()
    {
        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;

        $aluno = Aluno::create(['nome' => 'Gabriel Lima']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/alunos/' . $aluno->id . '/info-operacional', [
                'gatilho_comportamental' => 'Barulho alto',
                'acao_recomendada' => 'Oferecer abafador acustico',
                'prioridade' => 'alta',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.gatilho_comportamental', 'Barulho alto')
            ->assertJsonPath('data.prioridade', 'alta');
    }

    public function test_admin_pode_atualizar_e_excluir_info_operacional()
    {
        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;

        $aluno = Aluno::create(['nome' => 'Gabriel Lima']);
        $info = $aluno->infoOperacionais()->create([
            'gatilho_comportamental' => 'Gatilho Inicial',
            'acao_recomendada' => 'Acao Inicial',
            'prioridade' => 'media',
        ]);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/info-operacional/' . $info->id, [
                'prioridade' => 'alta',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.prioridade', 'alta');

        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/info-operacional/' . $info->id);

        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('info_operacional', ['id' => $info->id]);
    }
}
