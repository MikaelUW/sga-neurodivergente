<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aluno;
use App\Models\Responsavel;
use App\Models\Monitora;
use App\Models\InfoOperacional;
use App\Models\Ocorrencia;

class AlunoSeeder extends Seeder
{
    public function run(): void
    {
        // Criar Responsável Legal
        $responsavel = Responsavel::create([
            'nome' => 'Maria da Silva',
            'cpf' => '123.456.789-00',
            'email' => 'maria.silva@exemplo.com',
            'telefone' => '(11) 99999-8888',
        ]);

        // Criar Aluno de Teste com dados criptografados automaticamente e consentimento LGPD
        $aluno = Aluno::create([
            'nome' => 'Joao da Silva',
            'data_nascimento' => '2016-05-12',
            'necessidades_especiais' => 'TEA Nivel 1 de Suporte, TDAH',
            'observacoes_gerais' => 'Extremamente sensivel a ruidos agudos e mudancas de ambiente.',
            'responsavel_id' => $responsavel->id,
            'consentimento_lgpd_assinado' => true,
            'data_consentimento' => now(),
            'versao_termo_lgpd' => '1.0',
        ]);

        // Criar Informação Operacional (Regras de Manejo)
        InfoOperacional::create([
            'aluno_id' => $aluno->id,
            'gatilho_comportamental' => 'Excesso de ruido no patio durante o intervalo',
            'acao_recomendada' => 'Uso imediato de abafador de ruido acustico e direcionamento para sala de descanso',
            'prioridade' => 'alta',
        ]);

        $monitoras = Monitora::all();

        foreach ($monitoras as $monitora) {
            // Vincular Monitora ao Aluno (LGPD Minimização Art. 6º)
            $monitora->alunos()->syncWithoutDetaching([$aluno->id]);

            // Criar Ocorrência Inicial se for a primeira monitora
            if ($monitora->id === $monitoras->first()->id) {
                Ocorrencia::create([
                    'aluno_id' => $aluno->id,
                    'monitora_id' => $monitora->id,
                    'data_hora' => now(),
                    'descricao_fatos' => 'O aluno apresentou agitacao motora durante a aula de educacao fisica devido ao barulho do apito.',
                    'tipo_intervencao' => 'Intervencao verbal calma e uso de abafador acustico',
                ]);
            }
        }
    }
}
