<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Models\LaudoMedico;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaudoMedicoController extends Controller
{
    /**
     * Listar laudos (metadados) de um aluno.
     * Admin vê qualquer um; monitora só de aluno vinculado.
     */
    public function index(Request $request, $alunoId)
    {
        $aluno = Aluno::find($alunoId);
        if (!$aluno) {
            return response()->json(['message' => 'Aluno nao encontrado.'], 404);
        }

        $user = $request->user();
        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            if (!$monitora || !$monitora->alunos()->where('alunos.id', $alunoId)->exists()) {
                return response()->json(['message' => 'Acesso negado aos laudos deste aluno.'], 403);
            }
        }

        // Retorna apenas metadados (não o arquivo)
        $laudos = $aluno->laudosMedicos()->orderBy('data_upload', 'desc')->get();
        return response()->json(['data' => $laudos]);
    }

    /**
     * UC09 - Upload de Laudo Médico PDF (LGPD: Salvo em Storage Privado)
     */
    public function store(Request $request, $alunoId)
    {
        $aluno = Aluno::find($alunoId);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        $request->validate([
            'arquivo' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'crm_medico' => 'required|string',
            'especialidade' => 'required|string',
        ]);

        $file = $request->file('arquivo');
        // LGPD: Armazenado no disco privado 'local' (storage/app/laudos_privados) para evitar acesso público HTTP
        $path = $file->store('laudos_privados', 'local');

        $laudo = $aluno->laudosMedicos()->create([
            'path_arquivo' => $path,
            'crm_medico' => $request->crm_medico,
            'especialidade' => $request->especialidade,
            'data_upload' => now(),
        ]);

        // Registrar Log de Auditoria LGPD
        AuditLogService::registrar($aluno->id, 'LaudoMedico', 'criacao', $request);

        return response()->json([
            'message' => 'Laudo medico enviado com sucesso para o storage privado.',
            'data' => $laudo
        ], 201);
    }

    /**
     * UC09 - Download do Laudo Médico Privado (Com verificação de autorização e Audit Trail)
     */
    public function download(Request $request, $id)
    {
        $laudo = LaudoMedico::with('aluno')->find($id);

        if (!$laudo || !Storage::disk('local')->exists($laudo->path_arquivo)) {
            return response()->json([
                'message' => 'Arquivo de laudo nao encontrado.'
            ], 404);
        }

        $user = $request->user();

        // Se for monitora, verificar vínculo com o aluno (Minimização LGPD Art. 6º)
        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            if (!$monitora || !$monitora->alunos()->where('alunos.id', $laudo->aluno_id)->exists()) {
                return response()->json([
                    'message' => 'Acesso negado: Voce nao possui permissao para acessar o laudo deste aluno.'
                ], 403);
            }
        }

        // Registrar Log de Auditoria LGPD no download de dado sensível de saúde
        AuditLogService::registrar($laudo->aluno_id, 'LaudoMedico', 'download', $request);

        return Storage::disk('local')->download($laudo->path_arquivo);
    }

    /**
     * Editar metadados do laudo (CRM/especialidade) e, opcionalmente, trocar
     * o arquivo PDF. Exclusivo de admin.
     */
    public function update(Request $request, $id)
    {
        $laudo = LaudoMedico::find($id);
        if (!$laudo) {
            return response()->json(['message' => 'Laudo nao encontrado.'], 404);
        }

        $validated = $request->validate([
            'crm_medico' => 'sometimes|required|string',
            'especialidade' => 'sometimes|required|string',
            'arquivo' => 'sometimes|file|mimes:pdf|max:10240',
        ]);

        if ($request->hasFile('arquivo')) {
            // Remove o arquivo antigo e grava o novo
            if ($laudo->path_arquivo && Storage::disk('local')->exists($laudo->path_arquivo)) {
                Storage::disk('local')->delete($laudo->path_arquivo);
            }
            $laudo->path_arquivo = $request->file('arquivo')->store('laudos_privados', 'local');
        }
        if (isset($validated['crm_medico'])) $laudo->crm_medico = $validated['crm_medico'];
        if (isset($validated['especialidade'])) $laudo->especialidade = $validated['especialidade'];
        $laudo->save();

        AuditLogService::registrar($laudo->aluno_id, 'LaudoMedico', 'edicao', $request);

        return response()->json([
            'message' => 'Laudo atualizado com sucesso.',
            'data' => $laudo->fresh(),
        ]);
    }

    /**
     * Remover laudo: apaga o registro E o arquivo físico (expurgo LGPD).
     * Exclusivo de admin.
     */
    public function destroy(Request $request, $id)
    {
        $laudo = LaudoMedico::find($id);
        if (!$laudo) {
            return response()->json(['message' => 'Laudo nao encontrado.'], 404);
        }

        $alunoId = $laudo->aluno_id;
        if ($laudo->path_arquivo && Storage::disk('local')->exists($laudo->path_arquivo)) {
            Storage::disk('local')->delete($laudo->path_arquivo);
        }
        $laudo->delete();

        AuditLogService::registrar($alunoId, 'LaudoMedico', 'remocao', $request);

        return response()->json(['message' => 'Laudo removido com sucesso.']);
    }
}
