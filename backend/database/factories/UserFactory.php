<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'login' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'perfil' => 'monitora',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['perfil' => 'admin']);
    }

    public function monitora(): static
    {
        return $this->state(fn () => ['perfil' => 'monitora']);
    }
}
