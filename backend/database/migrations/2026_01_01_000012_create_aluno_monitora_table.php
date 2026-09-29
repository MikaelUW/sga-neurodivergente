<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aluno_monitora', function (Blueprint $table) {
            $table->foreignId('aluno_id')->constrained('alunos')->onDelete('cascade');
            $table->foreignId('monitora_id')->constrained('monitoras')->onDelete('cascade');
            $table->primary(['aluno_id', 'monitora_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aluno_monitora');
    }
};
