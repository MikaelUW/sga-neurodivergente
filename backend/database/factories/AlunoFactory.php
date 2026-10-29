<?php

namespace Database\Factories;

use App\Models\Aluno;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlunoFactory extends Factory
{
    protected $model = Aluno::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'data_nascimento' => $this->faker->date(),
            'necessidades_especiais' => 'TEA Nivel 1',
            'observacoes_gerais' => $this->faker->sentence(),
            'consentimento_lgpd_assinado' => true,
            'data_consentimento' => now(),
            'versao_termo_lgpd' => '1.0',
        ];
    }
}
