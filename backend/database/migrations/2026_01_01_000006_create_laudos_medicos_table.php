<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laudos_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('alunos')->onDelete('cascade');
            $table->string('path_arquivo');
            $table->text('crm_medico')->nullable(); // Criptografado
            $table->timestamp('data_upload')->useCurrent();
            $table->text('especialidade')->nullable(); // Criptografado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laudos_medicos');
    }
};
