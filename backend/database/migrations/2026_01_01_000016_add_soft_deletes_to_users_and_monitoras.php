<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete para usuários e monitoras.
 *
 * Desativar (em vez de apagar) preserva a integridade referencial das
 * ocorrências já registradas por uma monitora e mantém a trilha de
 * auditoria (LGPD Art. 37). Um usuário/monitora "removido" é marcado
 * como deletado e deixa de autenticar/aparecer nas listas.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('monitoras', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('administradores', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('monitoras', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('administradores', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
