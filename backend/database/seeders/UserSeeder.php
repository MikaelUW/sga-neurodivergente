<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Administrador;
use App\Models\Monitora;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Administrador Padrão
        $adminUser = User::create([
            'login' => 'admin@escola.edu.br',
            'password' => Hash::make('admin123'),
            'perfil' => 'admin',
        ]);

        Administrador::create([
            'user_id' => $adminUser->id,
            'setor' => 'Coordenacao Pedagogica',
            'nivel_acesso' => 5,
        ]);

        $adminInstUser = User::create([
            'login' => 'admin@instituicao.edu.br',
            'password' => Hash::make('admin123'),
            'perfil' => 'admin',
        ]);

        Administrador::create([
            'user_id' => $adminInstUser->id,
            'setor' => 'Coordenacao Geral',
            'nivel_acesso' => 5,
        ]);

        // 2. Monitora Padrão
        $monitoraUser = User::create([
            'login' => 'monitora@escola.edu.br',
            'password' => Hash::make('monitora123'),
            'perfil' => 'monitora',
        ]);

        Monitora::create([
            'user_id' => $monitoraUser->id,
            'classes' => 'Turma 3A, 4B',
            'cpf' => '123.456.789-00',
        ]);

        $monitoraInstUser = User::create([
            'login' => 'monitora@instituicao.edu.br',
            'password' => Hash::make('monitora123'),
            'perfil' => 'monitora',
        ]);

        Monitora::create([
            'user_id' => $monitoraInstUser->id,
            'classes' => 'Turma 1A, 2B',
            'cpf' => '987.654.321-11',
        ]);
    }
}
