<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Models\Ocorrencia;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class OcorrenciaController extends Controller
{
    /**
     * UC05 - Registrar Ocorrência (Com Criptografia de Fatos e Intervenção + Audit Log)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'aluno_id' => 'required|exists:alunos,id',
            'descricao_fatos' => 'required|string',
            'tipo_intervencao' => 'required|string',
        ]);

        $monitora = $user->monitora;

        if (!$monitora && $user->perfil !== 'admin') {
            return response()->json([
                'message' => 'Apenas monitoras autenticadas ou administradores podem registrar ocorrencias.'
            ], 403);
        }

        // Se for monitora, verificar se está vinculada ao aluno
        if ($monitora && !$monitora->alunos()->where('alunos.id', $validated['aluno_id'])->exists() && $user->perfil !== 'admin') {
            return response()->json([
                'message' => 'Acesso negado: Voce nao esta vinculada a este aluno.'
            ], 403);
        }

        $ocorrencia = Ocorrencia::create([
            'aluno_id' => $validated['aluno_id'],
            'monitora_id' => $monitora ? $monitora->id : null,
            'data_hora' => now(),
            'descricao_fatos' => $validated['descricao_fatos'], // Criptografado automaticamente via Model
            'tipo_intervencao' => $validated['tipo_intervencao'], // Criptografado automaticamente via Model
        ]);

        // Audit Log
        AuditLogService::registrar($validated['aluno_id'], 'Ocorrencia', 'criacao', $request);

        return response()->json([
            'message' => 'Ocorrencia registrada com sucesso e cifrada em AES-256.',
            'data' => $ocorrencia
        ], 201);
    }

    /**
     * UC06 - Consultar Histórico por Aluno (Decifrando os relatos sensíveis com Audit Log)
     */
    public function historicoPorAluno(Request $request, $alunoId)
    {
        $aluno = Aluno::find($alunoId);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        $user = $request->user();

        // Se for monitora, verificar vínculo com o aluno (Minimização LGPD Art. 6º)
        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            if (!$monitora || !$monitora->alunos()->where('alunos.id', $alunoId)->exists()) {
                return response()->json([
                    'message' => 'Acesso negado: Voce nao possui permissao para visualizar ocorrencias deste aluno.'
                ], 403);
            }
        }

        $ocorrencias = Ocorrencia::with('monitora.user')
            ->where('aluno_id', $alunoId)
            ->orderBy('data_hora', 'desc')
            ->get();

        // Audit Log de leitura de relatos comportamentais sensíveis
        AuditLogService::registrar($aluno->id, 'Ocorrencia', 'visualizacao', $request);

        return response()->json([
            'aluno_id' => $aluno->id,
            'aluno_nome' => $aluno->nome,
            'ocorrencias' => $ocorrencias
        ]);
    }
}
