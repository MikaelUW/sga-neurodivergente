<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aluno;
use App\Models\Responsavel;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AlunoController extends Controller
{
    /**
     * UC04 - Listar Alunos (Monitoras veem apenas seus alunos atribuídos, Admin ve todos)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min((int) $request->input('per_page', 15), 100);
        $busca = $request->input('busca');

        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            $query = $monitora ? $monitora->alunos()->with('responsavel') : Aluno::query()->whereRaw('1 = 0');
        } else {
            $query = Aluno::with('responsavel');
        }

        // Busca por nome via blind index (igualdade exata, case-insensitive)
        if (!empty($busca)) {
            $query->buscarPorNome($busca);
        }

        $alunos = $query->paginate($perPage);

        return response()->json($alunos);
    }

    /**
     * UC02 - Cadastrar Aluno com Consentimento LGPD e Responsável Legal (Art. 14, §1º)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'data_nascimento' => 'nullable|date',
            'necessidades_especiais' => 'nullable|string',
            'observacoes_gerais' => 'nullable|string',

            // Dados do Responsável Legal (LGPD)
            'responsavel_nome' => 'nullable|string|max:255',
            'responsavel_cpf' => 'nullable|string|max:14',
            'responsavel_email' => 'nullable|email|max:255',
            'responsavel_telefone' => 'nullable|string|max:20',

            // Termo de Consentimento LGPD
            'consentimento_lgpd_assinado' => 'nullable|boolean',
            'versao_termo_lgpd' => 'nullable|string|max:50',
        ]);

        $responsavelId = null;
        if (!empty($validated['responsavel_nome'])) {
            $responsavel = Responsavel::create([
                'nome' => $validated['responsavel_nome'],
                'cpf' => $validated['responsavel_cpf'] ?? null,
                'email' => $validated['responsavel_email'] ?? null,
                'telefone' => $validated['responsavel_telefone'] ?? null,
            ]);
            $responsavelId = $responsavel->id;
        }

        $aluno = Aluno::create([
            'nome' => $validated['nome'],
            'data_nascimento' => $validated['data_nascimento'] ?? null,
            'necessidades_especiais' => $validated['necessidades_especiais'] ?? null,
            'observacoes_gerais' => $validated['observacoes_gerais'] ?? null,
            'responsavel_id' => $responsavelId,
            'consentimento_lgpd_assinado' => $validated['consentimento_lgpd_assinado'] ?? false,
            'data_consentimento' => ($validated['consentimento_lgpd_assinado'] ?? false) ? now() : null,
            'versao_termo_lgpd' => $validated['versao_termo_lgpd'] ?? '1.0',
        ]);

        // Audit Log
        AuditLogService::registrar($aluno->id, 'Aluno', 'criacao', $request);

        return response()->json([
            'message' => 'Aluno cadastrado com sucesso com registro de consentimento LGPD.',
            'data' => $aluno->load('responsavel')
        ], 201);
    }

    /**
     * UC04 - Consultar Perfil Completo do Aluno (Com Audit Log e Validação de Permissão)
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Se for monitora, validar vínculo com o aluno (LGPD Minimização Art. 6º, III)
        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            if (!$monitora || !$monitora->alunos()->where('alunos.id', $id)->exists()) {
                return response()->json([
                    'message' => 'Acesso negado: Voce nao esta vinculada a este aluno.'
                ], 403);
            }
        }

        $aluno = Aluno::with(['responsavel', 'infoOperacionais', 'laudosMedicos', 'ocorrencias.monitora.user'])->find($id);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        // Registrar Audit Log de leitura de prontuário sensível
        AuditLogService::registrar($aluno->id, 'Aluno', 'visualizacao', $request);

        return response()->json([
            'data' => $aluno
        ]);
    }

    /**
     * UC02 - Editar Aluno
     */
    public function update(Request $request, $id)
    {
        $aluno = Aluno::find($id);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:255',
            'data_nascimento' => 'nullable|date',
            'necessidades_especiais' => 'nullable|string',
            'observacoes_gerais' => 'nullable|string',
            'consentimento_lgpd_assinado' => 'nullable|boolean',
            'versao_termo_lgpd' => 'nullable|string',
        ]);

        if (isset($validated['consentimento_lgpd_assinado']) && $validated['consentimento_lgpd_assinado']) {
            $validated['data_consentimento'] = now();
        }

        $aluno->update($validated);

        // Audit Log
        AuditLogService::registrar($aluno->id, 'Aluno', 'edicao', $request);

        return response()->json([
            'message' => 'Aluno atualizado com sucesso.',
            'data' => $aluno->load('responsavel')
        ]);
    }

    /**
     * UC02 - Remover Aluno (SoftDelete para preservação regulatória)
     */
    public function destroy(Request $request, $id)
    {
        $aluno = Aluno::with('laudosMedicos')->find($id);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        $alunoId = $aluno->id;

        // Expurgo dos arquivos de laudo associados (evita arquivos órfãos no
        // disco após a remoção do aluno) — conformidade LGPD de eliminação.
        foreach ($aluno->laudosMedicos as $laudo) {
            if ($laudo->path_arquivo && \Illuminate\Support\Facades\Storage::disk('local')->exists($laudo->path_arquivo)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($laudo->path_arquivo);
            }
        }

        $aluno->delete(); // SoftDelete do registro (preservação regulatória)

        // Audit Log
        AuditLogService::registrar($alunoId, 'Aluno', 'remocao', $request);

        return response()->json([
            'message' => 'Aluno desativado (SoftDelete) com sucesso para fins de cumprimento legal.'
        ]);
    }

    /**
     * Endpoint LGPD: Exportar Prontuário Completo de Portabilidade (Artigo 18 da LGPD)
     */
    public function exportarLgpd(Request $request, $id)
    {
        $user = $request->user();

        // Monitora só pode exportar aluno ao qual está vinculada (LGPD Art. 6º).
        if ($user->perfil === 'monitora') {
            $monitora = $user->monitora;
            if (!$monitora || !$monitora->alunos()->where('alunos.id', $id)->exists()) {
                return response()->json([
                    'message' => 'Acesso negado: Voce nao esta vinculada a este aluno.'
                ], 403);
            }
        }

        $aluno = Aluno::with(['responsavel', 'infoOperacionais', 'laudosMedicos', 'ocorrencias.monitora.user'])->find($id);

        if (!$aluno) {
            return response()->json([
                'message' => 'Aluno nao encontrado.'
            ], 404);
        }

        // Registrar Audit Log de Exportação LGPD
        AuditLogService::registrar($aluno->id, 'Aluno', 'exportacao_lgpd', $request);

        return response()->json([
            'titulo' => 'Relatório de Portabilidade e Transparência LGPD (Art. 18)',
            'data_emissao' => now()->toIso8601String(),
            'titular_aluno' => [
                'id' => $aluno->id,
                'nome' => $aluno->nome,
                'data_nascimento' => $aluno->data_nascimento,
                'necessidades_especiais' => $aluno->necessidades_especiais,
                'observacoes_gerais' => $aluno->observacoes_gerais,
            ],
            'consentimento_lgpd' => [
                'assinado' => $aluno->consentimento_lgpd_assinado,
                'data_assinatura' => $aluno->data_consentimento,
                'versao_termo' => $aluno->versao_termo_lgpd,
            ],
            'responsavel_legal' => $aluno->responsavel,
            'planos_manejo_operacional' => $aluno->infoOperacionais,
            'historico_ocorrencias' => $aluno->ocorrencias,
            'laudos_medicos_metadata' => $aluno->laudosMedicos->map(function ($laudo) {
                return [
                    'id' => $laudo->id,
                    'crm_medico' => $laudo->crm_medico,
                    'especialidade' => $laudo->especialidade,
                    'data_upload' => $laudo->data_upload,
                ];
            }),
        ]);
    }
}
