<?php

namespace App\Services;

use App\Models\AcessoLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Registra um acesso ou operação a dados sensíveis de um aluno.
     *
     * @param int|null $alunoId
     * @param string $recurso (ex: 'Aluno', 'Ocorrencia', 'LaudoMedico')
     * @param string $acao (ex: 'visualizacao', 'download', 'criacao', 'edicao', 'remocao', 'exportacao_lgpd')
     * @param Request|null $request
     * @return AcessoLog
     */
    public static function registrar(?int $alunoId, string $recurso, string $acao, ?Request $request = null): AcessoLog
    {
        $request = $request ?? request();
        $user = Auth::user();

        return AcessoLog::create([
            'user_id' => $user ? $user->id : null,
            'aluno_id' => $alunoId,
            'recurso' => $recurso,
            'acao' => $acao,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr($request->userAgent() ?? '', 0, 255) : null,
        ]);
    }
}
