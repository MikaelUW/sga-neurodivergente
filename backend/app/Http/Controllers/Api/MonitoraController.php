<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitora;
use App\Models\Aluno;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

/**
 * Visões e vínculos centrados em Monitora (exclusivo de admin).
 * A criação/edição/remoção de contas de monitora fica em UsuarioController;
 * aqui tratamos listagem e o vínculo monitora <-> aluno (pivot aluno_monitora).
 */
class MonitoraController extends Controller
{
    public function index(Request $request)
    {
        $monitoras = Monitora::with('user')
            ->withCount('alunos')
            ->paginate(min((int) $request->input('per_page', 20), 100));
        return response()->json($monitoras);
    }

    public function show($id)
    {
        $monitora = Monitora::with(['user', 'alunos'])->find($id);
        if (!$monitora) {
            return response()->json(['message' => 'Monitora nao encontrada.'], 404);
        }
        return response()->json(['data' => $monitora]);
    }

    public function listarAlunos($id)
    {
        $monitora = Monitora::with('alunos')->find($id);
        if (!$monitora) {
            return response()->json(['message' => 'Monitora nao encontrada.'], 404);
        }
        return response()->json(['data' => $monitora->alunos]);
    }

    public function vincularAluno(Request $request, $id)
    {
        $monitora = Monitora::find($id);
        if (!$monitora) {
            return response()->json(['message' => 'Monitora nao encontrada.'], 404);
        }
        $validated = $request->validate([
            'aluno_id' => 'required|exists:alunos,id',
        ]);
        $monitora->alunos()->syncWithoutDetaching([$validated['aluno_id']]);

        AuditLogService::registrar($validated['aluno_id'], 'VinculoMonitora', 'criacao', $request);

        return response()->json([
            'message' => 'Aluno vinculado a monitora com sucesso.',
            'data' => $monitora->fresh('alunos'),
        ], 201);
    }

    public function desvincularAluno(Request $request, $id, $alunoId)
    {
        $monitora = Monitora::find($id);
        if (!$monitora) {
            return response()->json(['message' => 'Monitora nao encontrada.'], 404);
        }
        $monitora->alunos()->detach($alunoId);

        AuditLogService::registrar((int) $alunoId, 'VinculoMonitora', 'remocao', $request);

        return response()->json(['message' => 'Vinculo removido com sucesso.']);
    }
}
