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
     * Visão geral de ocorrências (admin): todas, com filtros e paginação.
     */
    public function index(Request $request)
    {
        $query = Ocorrencia::with(['aluno', 'monitora.user'])->orderBy('data_hora', 'desc');

        if ($request->filled('aluno_id')) {
            $query->where('aluno_id', $request->aluno_id);
        }
        if ($request->filled('monitora_id')) {
            $query->where('monitora_id', $request->monitora_id);
        }
        if ($request->filled('de')) {
            $query->where('data_hora', '>=', $request->de);
        }
        if ($request->filled('ate')) {
            $query->where('data_hora', '<=', $request->ate);
        }

        $ocorrencias = $query->paginate(min((int) $request->input('per_page', 20), 100));
        return response()->json($ocorrencias);
    }

    /**
     * Ver uma ocorrência isolada (admin, ou monitora vinculada ao aluno).
     */
    public function show(Request $request, $id)
    {
        $ocorrencia = Ocorrencia::with(['aluno', 'monitora.user'])->find($id);
        if (!$ocorrencia) {
            return response()->json(['message' => 'Ocorrencia nao encontrada.'], 404);
        }

        $user = $request->user();
        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            if (!$monitora || !$monitora->alunos()->where('alunos.id', $ocorrencia->aluno_id)->exists()) {
                return response()->json(['message' => 'Acesso negado a esta ocorrencia.'], 403);
            }
        }

        AuditLogService::registrar($ocorrencia->aluno_id, 'Ocorrencia', 'visualizacao', $request);
        return response()->json(['data' => $ocorrencia]);
    }

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

    /**
     * Editar ocorrência.
     * Autorização: admin sempre; monitora apenas as próprias e dentro de 24h.
     */
    public function update(Request $request, $id)
    {
        $ocorrencia = Ocorrencia::find($id);
        if (!$ocorrencia) {
            return response()->json(['message' => 'Ocorrencia nao encontrada.'], 404);
        }

        $erro = $this->autorizarEdicao($request->user(), $ocorrencia);
        if ($erro) return $erro;

        $validated = $request->validate([
            'descricao_fatos' => 'sometimes|required|string',
            'tipo_intervencao' => 'sometimes|required|string',
        ]);

        $ocorrencia->update($validated);
        AuditLogService::registrar($ocorrencia->aluno_id, 'Ocorrencia', 'edicao', $request);

        return response()->json([
            'message' => 'Ocorrencia atualizada com sucesso.',
            'data' => $ocorrencia->fresh(['monitora.user']),
        ]);
    }

    /**
     * Remover ocorrência (mesma regra de autorização da edição).
     */
    public function destroy(Request $request, $id)
    {
        $ocorrencia = Ocorrencia::find($id);
        if (!$ocorrencia) {
            return response()->json(['message' => 'Ocorrencia nao encontrada.'], 404);
        }

        $erro = $this->autorizarEdicao($request->user(), $ocorrencia);
        if ($erro) return $erro;

        $alunoId = $ocorrencia->aluno_id;
        $ocorrencia->delete();
        AuditLogService::registrar($alunoId, 'Ocorrencia', 'remocao', $request);

        return response()->json(['message' => 'Ocorrencia removida com sucesso.']);
    }

    /**
     * Regra de negócio de edição/remoção de ocorrência.
     * Retorna uma JsonResponse de erro se não autorizado, ou null se ok.
     */
    private function autorizarEdicao($user, Ocorrencia $ocorrencia)
    {
        if ($user->perfil === 'admin') {
            return null; // admin pode tudo
        }
        // Monitora: precisa ser a autora
        $monitora = $user->monitora;
        if (!$monitora || $ocorrencia->monitora_id !== $monitora->id) {
            return response()->json([
                'message' => 'Apenas a monitora autora ou um administrador podem alterar esta ocorrencia.'
            ], 403);
        }
        // E dentro da janela de 24h
        if ($ocorrencia->data_hora && $ocorrencia->data_hora->lt(now()->subDay())) {
            return response()->json([
                'message' => 'Prazo para edicao expirado (24h). Solicite a um administrador.'
            ], 403);
        }
        return null;
    }
}
