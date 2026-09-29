<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcessoLog;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    /**
     * Listar Trilha de Auditoria LGPD (Apenas Administradores)
     */
    public function index(Request $request)
    {
        $query = AcessoLog::with(['user', 'aluno']);

        if ($request->has('aluno_id')) {
            $query->where('aluno_id', $request->aluno_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('recurso')) {
            $query->where('recurso', $request->recurso);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'titulo' => 'Trilha de Auditoria de Acessos a Dados Sensíveis (Art. 37 LGPD)',
            'data' => $logs
        ]);
    }
}
