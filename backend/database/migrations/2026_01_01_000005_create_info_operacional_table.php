<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('info_operacional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('alunos')->onDelete('cascade');
            $table->text('gatilho_comportamental')->nullable(); // Criptografado
            $table->text('acao_recomendada')->nullable(); // Criptografado
            $table->string('prioridade')->default('media'); // alta, media, baixa
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('info_operacional');
    }
};
