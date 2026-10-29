<?php

namespace App\Console\Commands;

use App\Models\Administrador;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CriarAdmin extends Command
{
    /**
     * Assinatura do comando.
     *
     * Ex.:
     *   php artisan sgan:criar-admin --login=admin@escola.edu.br --gerar-senha
     *   php artisan sgan:criar-admin --login=admin@escola.edu.br --senha='MinhaSenhaF0rte!' --setor='Coordenacao'
     */
    protected $signature = 'sgan:criar-admin
        {--login= : E-mail/login do administrador}
        {--senha= : Senha forte (mín. 12 caracteres). Omita para informar interativamente}
        {--gerar-senha : Gera uma senha forte aleatória e a exibe ao final}
        {--setor=Coordenacao Pedagogica : Setor do administrador}
        {--nivel=5 : Nível de acesso (1-5)}
        {--force : Não pedir confirmação}';

    protected $description = 'Cria o primeiro administrador do SGAN com senha forte (uso em produção).';

    /** Tamanho mínimo exigido para a senha. */
    private const SENHA_MIN = 12;

    public function handle(): int
    {
        $login = $this->option('login') ?: $this->ask('Login (e-mail) do administrador');

        if (! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $this->error('Login inválido: informe um e-mail válido.');
            return self::FAILURE;
        }

        // Já existe?
        if (User::where('login', $login)->withTrashed()->exists()) {
            $this->error("Já existe um usuário com o login '{$login}'. Abortando para não sobrescrever.");
            return self::FAILURE;
        }

        // Resolve a senha: gerada, por opção, ou interativa.
        $senhaGerada = false;
        if ($this->option('gerar-senha')) {
            $senha = $this->gerarSenhaForte();
            $senhaGerada = true;
        } else {
            $senha = $this->option('senha') ?: $this->secret('Senha (mín. ' . self::SENHA_MIN . ' caracteres)');
        }

        if (! $this->senhaForte($senha, $motivo)) {
            $this->error("Senha fraca: {$motivo}");
            return self::FAILURE;
        }

        $nivel = (int) $this->option('nivel');
        if ($nivel < 1 || $nivel > 5) {
            $this->error('Nível de acesso deve estar entre 1 e 5.');
            return self::FAILURE;
        }

        $setor = (string) $this->option('setor');

        if (! $this->option('force')) {
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Login', $login],
                    ['Perfil', 'admin'],
                    ['Setor', $setor],
                    ['Nível de acesso', $nivel],
                ]
            );
            if (! $this->confirm('Confirmar criação deste administrador?', true)) {
                $this->warn('Operação cancelada.');
                return self::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($login, $senha, $setor, $nivel) {
                // O cast 'password' => 'hashed' no model aplica o bcrypt.
                $user = User::create([
                    'login'    => $login,
                    'password' => $senha,
                    'perfil'   => 'admin',
                ]);

                Administrador::create([
                    'user_id'      => $user->id,
                    'setor'        => $setor,
                    'nivel_acesso' => $nivel,
                ]);
            });
        } catch (\Throwable $e) {
            $this->error('Falha ao criar administrador: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✔ Administrador criado com sucesso.');
        $this->line("  Login: <comment>{$login}</comment>");

        if ($senhaGerada) {
            $this->newLine();
            $this->warn('Senha gerada (anote agora — não será exibida novamente):');
            $this->line("  <comment>{$senha}</comment>");
        }

        return self::SUCCESS;
    }

    /**
     * Gera uma senha forte aleatória (letras maiúsculas, minúsculas,
     * dígitos e símbolos), garantindo ao menos um de cada classe.
     */
    private function gerarSenhaForte(int $tamanho = 20): string
    {
        $maiusc  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minusc  = 'abcdefghijkmnopqrstuvwxyz';
        $digitos = '23456789';
        $simb    = '!@#$%&*?-_=+';
        $todos   = $maiusc . $minusc . $digitos . $simb;

        $pick = fn (string $set) => $set[random_int(0, strlen($set) - 1)];

        // Garante ao menos um de cada classe.
        $chars = [$pick($maiusc), $pick($minusc), $pick($digitos), $pick($simb)];
        for ($i = count($chars); $i < $tamanho; $i++) {
            $chars[] = $pick($todos);
        }

        // Embaralha para não deixar as classes obrigatórias no início.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    /**
     * Valida força mínima: tamanho + presença de classes de caracteres.
     */
    private function senhaForte(?string $senha, ?string &$motivo = null): bool
    {
        $senha = (string) $senha;

        if (Str::length($senha) < self::SENHA_MIN) {
            $motivo = 'deve ter ao menos ' . self::SENHA_MIN . ' caracteres.';
            return false;
        }
        if (! preg_match('/[A-Z]/', $senha)) {
            $motivo = 'deve conter ao menos uma letra maiúscula.';
            return false;
        }
        if (! preg_match('/[a-z]/', $senha)) {
            $motivo = 'deve conter ao menos uma letra minúscula.';
            return false;
        }
        if (! preg_match('/\d/', $senha)) {
            $motivo = 'deve conter ao menos um dígito.';
            return false;
        }
        if (! preg_match('/[^A-Za-z0-9]/', $senha)) {
            $motivo = 'deve conter ao menos um símbolo.';
            return false;
        }

        return true;
    }
}
