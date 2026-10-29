<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

class GerarSegredos extends Command
{
    protected $signature = 'sgan:gerar-segredos
        {--cipher=AES-256-CBC : Cifra usada para dimensionar a APP_KEY}';

    protected $description = 'Gera APP_KEY e BLIND_INDEX_KEY para o .env de produção (exibe, não grava).';

    public function handle(): int
    {
        $cipher = (string) $this->option('cipher');

        // APP_KEY no formato que o Laravel espera (base64:...).
        $appKey = 'base64:' . base64_encode(Encrypter::generateKey($cipher));

        // BLIND_INDEX_KEY: 32 bytes aleatórios em base64 (independente da APP_KEY).
        $blindIndexKey = 'base64:' . base64_encode(random_bytes(32));

        $this->newLine();
        $this->info('Segredos gerados — copie para o .env de produção e guarde no cofre/secret manager:');
        $this->newLine();
        $this->line("APP_KEY={$appKey}");
        $this->line("BLIND_INDEX_KEY={$blindIndexKey}");
        $this->newLine();
        $this->warn('IMPORTANTE:');
        $this->line('  • Estas chaves cifram os dados sensíveis. Mantenha-as FIXAS.');
        $this->line('  • Faça BACKUP delas junto do banco — perdê-las torna os dados ilegíveis.');
        $this->line('  • Defina a BLIND_INDEX_KEY ANTES de popular dados (mudá-la quebra a busca).');
        $this->newLine();

        return self::SUCCESS;
    }
}
