<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Administrador;
use Illuminate\Http\Request;

class PermissaoController extends Controller
{
    /**
     * UC07 - Listar Usuários e Permissões
     */
    public function index()
    {
        $users = User::with(['administrador', 'monitora'])->get();

        return response()->json([
            'data' => $users
        ]);
    }

    /**
     * UC07 - Gerenciar Permissões / Nível de Acesso de Administrador
     */
    public function updateNivelAcesso(Request $request, $usuarioId)
    {
        $request->validate([
            'nivel_acesso' => 'required|integer|min:1|max:5',
            'setor' => 'nullable|string',
        ]);

        $user = User::find($usuarioId);

        if (!$user) {
            return response()->json([
                'message' => 'Usuario nao encontrado.'
            ], 404);
        }

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'Permissoes de nivel de acesso se aplicam a administradores.'
            ], 400);
        }

        $admin = $user->administrador;
        if ($admin) {
            $admin->update([
                'nivel_acesso' => $request->nivel_acesso,
                'setor' => $request->setor ?? $admin->setor,
            ]);
        }

        return response()->json([
            'message' => 'Permissoes atualizadas com sucesso.',
            'data' => $user->fresh(['administrador'])
        ]);
    }
}
