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
        // Idempotente: usa firstOrCreate para poder rodar várias vezes
        // (ex.: reboot automático em outra máquina) sem violar unique.

        // 1. Administradores
        $adminUser = User::firstOrCreate(
            ['login' => 'admin@escola.edu.br'],
            ['password' => Hash::make('admin123'), 'perfil' => 'admin']
        );
        Administrador::firstOrCreate(
            ['user_id' => $adminUser->id],
            ['setor' => 'Coordenacao Pedagogica', 'nivel_acesso' => 5]
        );

        $adminInstUser = User::firstOrCreate(
            ['login' => 'admin@instituicao.edu.br'],
            ['password' => Hash::make('admin123'), 'perfil' => 'admin']
        );
        Administrador::firstOrCreate(
            ['user_id' => $adminInstUser->id],
            ['setor' => 'Coordenacao Geral', 'nivel_acesso' => 5]
        );

        // 2. Monitoras
        $monitoraUser = User::firstOrCreate(
            ['login' => 'monitora@escola.edu.br'],
            ['password' => Hash::make('monitora123'), 'perfil' => 'monitora']
        );
        Monitora::firstOrCreate(
            ['user_id' => $monitoraUser->id],
            ['classes' => 'Turma 3A, 4B', 'cpf' => '123.456.789-00']
        );

        $monitoraInstUser = User::firstOrCreate(
            ['login' => 'monitora@instituicao.edu.br'],
            ['password' => Hash::make('monitora123'), 'perfil' => 'monitora']
        );
        Monitora::firstOrCreate(
            ['user_id' => $monitoraInstUser->id],
            ['classes' => 'Turma 1A, 2B', 'cpf' => '987.654.321-11']
        );
    }
}
