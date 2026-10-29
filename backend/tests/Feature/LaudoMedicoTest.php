<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Aluno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class LaudoMedicoTest extends TestCase
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

    public function test_admin_pode_fazer_upload_de_laudo_medico_pdf()
    {
        Storage::fake('local');

        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;
        $aluno = Aluno::create(['nome' => 'Bruno Souza']);

        $file = UploadedFile::fake()->create('laudo.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/alunos/' . $aluno->id . '/laudos', [
                'arquivo' => $file,
                'crm_medico' => 'CRM/SP 123456',
                'especialidade' => 'Neuropediatria',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.crm_medico', 'CRM/SP 123456');

        $laudo = $aluno->laudosMedicos()->first();
        Storage::disk('local')->assertExists($laudo->path_arquivo);
    }

    public function test_usuario_autenticado_pode_fazer_download_de_laudo()
    {
        Storage::fake('local');

        $admin = $this->createAdminUser();
        $token = $admin->createToken('test')->plainTextToken;
        $aluno = Aluno::create(['nome' => 'Bruno Souza']);

        $file = UploadedFile::fake()->create('laudo.pdf', 100, 'application/pdf');

        $uploadResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/alunos/' . $aluno->id . '/laudos', [
                'arquivo' => $file,
                'crm_medico' => 'CRM/SP 123456',
                'especialidade' => 'Neuropediatria',
            ]);

        $laudoId = $uploadResponse->json('data.id');

        $downloadResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/laudos/' . $laudoId . '/download');

        $downloadResponse->assertStatus(200);
    }
}
