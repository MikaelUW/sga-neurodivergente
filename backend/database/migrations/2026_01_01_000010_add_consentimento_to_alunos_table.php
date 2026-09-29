<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->foreignId('responsavel_id')->nullable()->constrained('responsaveis')->onDelete('set null');
            $table->boolean('consentimento_lgpd_assinado')->default(false);
            $table->timestamp('data_consentimento')->nullable();
            $table->string('versao_termo_lgpd')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->dropForeign(['responsavel_id']);
            $table->dropColumn([
                'responsavel_id',
                'consentimento_lgpd_assinado',
                'data_consentimento',
                'versao_termo_lgpd',
                'deleted_at'
            ]);
        });
    }
};
