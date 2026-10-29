<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Administrador;
use App\Models\Monitora;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Gestão de usuários (administradores e monitoras) — exclusivo de admin.
 * Cria o registro de autenticação (User) e o registro de perfil
 * (Administrador ou Monitora) numa única transação.
 */
class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $perfil = $request->input('perfil'); // opcional: 'admin' | 'monitora'
        $query = User::with(['administrador', 'monitora']);
        if (in_array($perfil, ['admin', 'monitora'], true)) {
            $query->where('perfil', $perfil);
        }
        return response()->json($query->paginate(min((int) $request->input('per_page', 20), 100)));
    }

    public function show($id)
    {
        $user = User::with(['administrador', 'monitora.alunos'])->find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario nao encontrado.'], 404);
        }
        return response()->json(['data' => $user]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string|max:100|unique:users,login',
            'password' => 'required|string|min:6',
            'perfil' => 'required|in:admin,monitora',
            // Campos de monitora
            'classes' => 'nullable|string|max:225',
            'cpf' => 'nullable|string|max:20|required_if:perfil,monitora|unique:monitoras,cpf',
            // Campos de administrador
            'setor' => 'nullable|string|max:100',
            'nivel_acesso' => 'nullable|integer|min:1|max:5',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'login' => $validated['login'],
                'password' => Hash::make($validated['password']),
                'perfil' => $validated['perfil'],
            ]);

            if ($validated['perfil'] === 'monitora') {
                Monitora::create([
                    'user_id' => $user->id,
                    'classes' => $validated['classes'] ?? null,
                    'cpf' => $validated['cpf'],
                ]);
            } else {
                Administrador::create([
                    'user_id' => $user->id,
                    'setor' => $validated['setor'] ?? null,
                    'nivel_acesso' => $validated['nivel_acesso'] ?? 1,
                ]);
            }
            return $user;
        });

        AuditLogService::registrar(null, 'Usuario', 'criacao', $request);

        return response()->json([
            'message' => 'Usuario criado com sucesso.',
            'data' => $user->fresh(['administrador', 'monitora']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::with(['administrador', 'monitora'])->find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario nao encontrado.'], 404);
        }

        $validated = $request->validate([
            'login' => 'sometimes|required|string|max:100|unique:users,login,' . $user->id,
            'classes' => 'nullable|string|max:225',
            'cpf' => 'nullable|string|max:20|unique:monitoras,cpf,' . ($user->monitora->id ?? 'NULL'),
            'setor' => 'nullable|string|max:100',
            'nivel_acesso' => 'nullable|integer|min:1|max:5',
        ]);

        DB::transaction(function () use ($user, $validated) {
            if (isset($validated['login'])) {
                $user->update(['login' => $validated['login']]);
            }
            if ($user->perfil === 'monitora' && $user->monitora) {
                $user->monitora->update(array_filter([
                    'classes' => $validated['classes'] ?? null,
                    'cpf' => $validated['cpf'] ?? null,
                ], fn ($v) => !is_null($v)));
            }
            if ($user->perfil === 'admin' && $user->administrador) {
                $dados = [];
                if (array_key_exists('setor', $validated)) $dados['setor'] = $validated['setor'];
                if (array_key_exists('nivel_acesso', $validated) && !is_null($validated['nivel_acesso'])) {
                    $dados['nivel_acesso'] = $validated['nivel_acesso'];
                }
                if ($dados) $user->administrador->update($dados);
            }
        });

        AuditLogService::registrar(null, 'Usuario', 'edicao', $request);

        return response()->json([
            'message' => 'Usuario atualizado com sucesso.',
            'data' => $user->fresh(['administrador', 'monitora']),
        ]);
    }

    public function redefinirSenha(Request $request, $id = null)
    {
        // id null => usuário troca a própria senha; id preenchido => admin redefine
        if (is_null($id)) {
            $user = $request->user();
        } else {
            // Só admin chega aqui (rota protegida por perfil:admin)
            $user = User::find($id);
        }
        if (!$user) {
            return response()->json(['message' => 'Usuario nao encontrado.'], 404);
        }
        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);
        $user->update(['password' => Hash::make($validated['password'])]);
        // Revoga tokens ativos por segurança
        $user->tokens()->delete();

        AuditLogService::registrar(null, 'Usuario', 'redefinicao_senha', $request);

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario nao encontrado.'], 404);
        }
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Voce nao pode remover o proprio usuario.'], 422);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            if ($user->monitora) $user->monitora->delete();
            if ($user->administrador) $user->administrador->delete();
            $user->delete(); // soft delete
        });

        AuditLogService::registrar(null, 'Usuario', 'remocao', $request);

        return response()->json(['message' => 'Usuario desativado com sucesso.']);
    }
}
