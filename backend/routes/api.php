<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\InfoOperacionalController;
use App\Http\Controllers\Api\OcorrenciaController;
use App\Http\Controllers\Api\LaudoMedicoController;
use App\Http\Controllers\Api\PermissaoController;
use App\Http\Controllers\Api\AuditoriaController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Gestao de Alunos Neurodivergentes (LGPD Compliant)
|--------------------------------------------------------------------------
*/

// UC01: Autenticação pública
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas por autenticação via Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Sessao do usuario
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // UC04: Consulta de Alunos e Perfis (Filtrado por permissão/vínculo)
    Route::get('/alunos', [AlunoController::class, 'index']);
    Route::get('/alunos/{id}', [AlunoController::class, 'show']);

    // LGPD Art. 18: Portabilidade de Dados do Aluno para os Pais/Responsáveis
    Route::get('/alunos/{id}/exportar-lgpd', [AlunoController::class, 'exportarLgpd']);

    // UC06: Consultar Histórico de Ocorrências Decifradas
    Route::get('/alunos/{alunoId}/ocorrencias', [OcorrenciaController::class, 'historicoPorAluno']);

    // UC09: Download Seguro de Laudo Médico (Disco Privado com Audit Log)
    Route::get('/laudos/{id}/download', [LaudoMedicoController::class, 'download']);

    // Rotas restritas para Monitoras e Admins
    Route::middleware('perfil:monitora,admin')->group(function () {
        // UC05: Registrar Ocorrências (Criptografia automatica dos fatos + Audit Log)
        Route::post('/ocorrencias', [OcorrenciaController::class, 'store']);
    });

    // Rotas exclusivas para Administradores
    Route::middleware('perfil:admin')->group(function () {

        // UC02: Manter Cadastro de Alunos (Com consentimento LGPD e Responsável Legal)
        Route::post('/alunos', [AlunoController::class, 'store']);
        Route::put('/alunos/{id}', [AlunoController::class, 'update']);
        Route::delete('/alunos/{id}', [AlunoController::class, 'destroy']);

        // UC03: Vincular Informações Operacionais ao Aluno
        Route::post('/alunos/{alunoId}/info-operacional', [InfoOperacionalController::class, 'store']);
        Route::put('/info-operacional/{id}', [InfoOperacionalController::class, 'update']);
        Route::delete('/info-operacional/{id}', [InfoOperacionalController::class, 'destroy']);

        // UC09: Upload de Laudo Médico PDF (Salvo em Storage Privado)
        Route::post('/alunos/{alunoId}/laudos', [LaudoMedicoController::class, 'store']);

        // UC07: Gerenciar Permissões de Usuários
        Route::get('/usuarios', [PermissaoController::class, 'index']);
        Route::put('/usuarios/{usuarioId}/permissoes', [PermissaoController::class, 'updateNivelAcesso']);

        // LGPD Art. 37: Trilha de Auditoria de Acessos a Dados Sensíveis
        Route::get('/auditoria/logs', [AuditoriaController::class, 'index']);
    });
});
