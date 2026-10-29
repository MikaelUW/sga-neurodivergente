<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona um "blind index" do nome do aluno.
 *
 * O campo `nome` é cifrado (AES-256-CBC com IV aleatório), então o mesmo
 * texto gera ciphertext diferente a cada escrita — impossível buscar com
 * WHERE/LIKE. O blind index guarda um HMAC-SHA256 determinístico do nome
 * normalizado, permitindo busca por igualdade sem expor o texto claro.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->string('nome_hash', 64)->nullable()->after('nome')->index();
        });
    }

    public function down(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->dropIndex(['nome_hash']);
            $table->dropColumn('nome_hash');
        });
    }
};
