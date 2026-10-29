<?php

namespace Database\Factories;

use App\Models\Monitora;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoraFactory extends Factory
{
    protected $model = Monitora::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->monitora(),
            'classes' => 'Turma ' . $this->faker->randomLetter(),
            'cpf' => $this->faker->unique()->numerify('###.###.###-##'),
        ];
    }
}
