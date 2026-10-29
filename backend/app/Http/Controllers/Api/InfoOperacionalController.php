<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Models\InfoOperacional;
use Illuminate\Http\Request;

class InfoOperacionalController extends Controller
{
    /**
     * Listar protocolos operacionais de um aluno.
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
                return response()->json(['message' => 'Acesso negado.'], 403);
            }
        }
        return response()->json(['data' => $aluno->infoOperacionais()->get()]);
    }

    /**
     * Ver um protocolo isolado.
     */
    public function show($id)
    {
        $info = InfoOperacional::find($id);
        if (!$info) {
            return response()->json(['message' => 'Informacao operacional nao encontrada.'], 404);
        }
        return response()->json(['data' => $info]);
    }

    /**
     * UC03 - Vincular Informações Operacionais ao Aluno
     */
    public function store(Request $request, $alunoId)
    {
        $aluno = Aluno::find($alunoId);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        $validated = $request->validate([
            'gatilho_comportamental' => 'required|string',
            'acao_recomendada' => 'required|string',
            'prioridade' => 'required|string|in:alta,media,baixa',
        ]);

        $info = $aluno->infoOperacionais()->create($validated);

        return response()->json([
            'message' => 'Informacao operacional vinculada com sucesso.',
            'data' => $info
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $info = InfoOperacional::find($id);

        if (!$info) {
            return response()->json([
                'message' => 'Informacao operacional nao encontrada.'
            ], 404);
        }

        $validated = $request->validate([
            'gatilho_comportamental' => 'sometimes|required|string',
            'acao_recomendada' => 'sometimes|required|string',
            'prioridade' => 'sometimes|required|string|in:alta,media,baixa',
        ]);

        $info->update($validated);

        return response()->json([
            'message' => 'Informacao operacional atualizada com sucesso.',
            'data' => $info
        ]);
    }

    public function destroy($id)
    {
        $info = InfoOperacional::find($id);

        if (!$info) {
            return response()->json([
                'message' => 'Informacao operacional nao encontrada.'
            ], 404);
        }

        $info->delete();

        return response()->json([
            'message' => 'Informacao operacional removida com sucesso.'
        ]);
    }
}
