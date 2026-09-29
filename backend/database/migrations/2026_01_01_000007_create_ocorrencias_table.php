<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocorrencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('alunos')->onDelete('cascade');
            $table->foreignId('monitora_id')->constrained('monitoras')->onDelete('cascade');
            $table->timestamp('data_hora')->useCurrent();
            $table->text('descricao_fatos'); // Criptografado
            $table->text('tipo_intervencao')->nullable(); // Criptografado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocorrencias');
    }
};
