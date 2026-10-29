<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlunoController;
use App\Http\Controllers\Api\InfoOperacionalController;
use App\Http\Controllers\Api\OcorrenciaController;
use App\Http\Controllers\Api\LaudoMedicoController;
use App\Http\Controllers\Api\PermissaoController;
use App\Http\Controllers\Api\AuditoriaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\MonitoraController;
use App\Http\Controllers\Api\ResponsavelController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Gestao de Alunos Neurodivergentes (LGPD Compliant)
|--------------------------------------------------------------------------
*/

// UC01: Autenticação pública (com rate limiting contra força bruta)
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Rotas protegidas por autenticação via Sanctum
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Sessao do usuario
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Usuário troca a própria senha
    Route::put('/me/senha', [UsuarioController::class, 'redefinirSenha']);

    // UC04: Consulta de Alunos e Perfis (Filtrado por permissão/vínculo)
    Route::get('/alunos', [AlunoController::class, 'index']);
    Route::get('/alunos/{id}', [AlunoController::class, 'show']);

    // LGPD Art. 18: Portabilidade de Dados do Aluno
    Route::get('/alunos/{id}/exportar-lgpd', [AlunoController::class, 'exportarLgpd']);

    // Protocolos operacionais (leitura)
    Route::get('/alunos/{alunoId}/info-operacional', [InfoOperacionalController::class, 'index']);
    Route::get('/info-operacional/{id}', [InfoOperacionalController::class, 'show']);

    // Laudos (leitura + download)
    Route::get('/alunos/{alunoId}/laudos', [LaudoMedicoController::class, 'index']);
    Route::get('/laudos/{id}/download', [LaudoMedicoController::class, 'download']);

    // UC06: Histórico de ocorrências por aluno + consulta unitária
    Route::get('/alunos/{alunoId}/ocorrencias', [OcorrenciaController::class, 'historicoPorAluno']);
    Route::get('/ocorrencias/{id}', [OcorrenciaController::class, 'show']);

    // Ocorrências: criar (monitora/admin), editar e remover (regra de autoria/24h no controller)
    Route::middleware('perfil:monitora,admin')->group(function () {
        Route::post('/ocorrencias', [OcorrenciaController::class, 'store']);
        Route::put('/ocorrencias/{id}', [OcorrenciaController::class, 'update']);
        Route::delete('/ocorrencias/{id}', [OcorrenciaController::class, 'destroy']);
    });

    // Rotas exclusivas para Administradores
    Route::middleware('perfil:admin')->group(function () {

        // --- Alunos (CRUD) ---
        Route::post('/alunos', [AlunoController::class, 'store']);
        Route::put('/alunos/{id}', [AlunoController::class, 'update']);
        Route::delete('/alunos/{id}', [AlunoController::class, 'destroy']);

        // --- Protocolos operacionais (escrita) ---
        Route::post('/alunos/{alunoId}/info-operacional', [InfoOperacionalController::class, 'store']);
        Route::put('/info-operacional/{id}', [InfoOperacionalController::class, 'update']);
        Route::delete('/info-operacional/{id}', [InfoOperacionalController::class, 'destroy']);

        // --- Laudos (escrita) ---
        Route::post('/alunos/{alunoId}/laudos', [LaudoMedicoController::class, 'store']);
        Route::put('/laudos/{id}', [LaudoMedicoController::class, 'update']);
        Route::delete('/laudos/{id}', [LaudoMedicoController::class, 'destroy']);

        // --- Ocorrências: visão geral (admin) ---
        Route::get('/ocorrencias', [OcorrenciaController::class, 'index']);

        // --- Responsável legal ---
        Route::get('/responsaveis/{id}', [ResponsavelController::class, 'show']);
        Route::put('/responsaveis/{id}', [ResponsavelController::class, 'update']);

        // --- Usuários (admin e monitora): CRUD completo ---
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
        Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
        Route::put('/usuarios/{id}/senha', [UsuarioController::class, 'redefinirSenha']);
        // UC07: mantém o endpoint legado de nível de acesso
        Route::put('/usuarios/{usuarioId}/permissoes', [PermissaoController::class, 'updateNivelAcesso']);

        // --- Monitoras: listagem e vínculos com alunos ---
        Route::get('/monitoras', [MonitoraController::class, 'index']);
        Route::get('/monitoras/{id}', [MonitoraController::class, 'show']);
        Route::get('/monitoras/{id}/alunos', [MonitoraController::class, 'listarAlunos']);
        Route::post('/monitoras/{id}/alunos', [MonitoraController::class, 'vincularAluno']);
        Route::delete('/monitoras/{id}/alunos/{alunoId}', [MonitoraController::class, 'desvincularAluno']);

        // LGPD Art. 37: Trilha de Auditoria
        Route::get('/auditoria/logs', [AuditoriaController::class, 'index']);
    });
});
