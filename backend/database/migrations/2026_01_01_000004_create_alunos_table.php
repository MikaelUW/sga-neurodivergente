<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->text('nome'); // Criptografado
            $table->date('data_nascimento')->nullable();
            $table->text('necessidades_especiais')->nullable(); // Criptografado
            $table->text('observacoes_gerais')->nullable(); // Criptografado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};
