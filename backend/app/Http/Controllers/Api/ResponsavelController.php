<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Responsavel;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Edição dos dados do responsável legal (exclusivo de admin).
 * A criação ocorre junto do cadastro do aluno; aqui permitimos manter
 * os dados atualizados (telefone, email, etc.).
 */
class ResponsavelController extends Controller
{
    public function show($id)
    {
        $resp = Responsavel::with('alunos')->find($id);
        if (!$resp) {
            return response()->json(['message' => 'Responsavel nao encontrado.'], 404);
        }
        return response()->json(['data' => $resp]);
    }

    public function update(Request $request, $id)
    {
        $resp = Responsavel::find($id);
        if (!$resp) {
            return response()->json(['message' => 'Responsavel nao encontrado.'], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'cpf' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
        ]);

        $resp->update($validated);
        AuditLogService::registrar(null, 'Responsavel', 'edicao', $request);

        return response()->json([
            'message' => 'Responsavel atualizado com sucesso.',
            'data' => $resp->fresh(),
        ]);
    }
}
