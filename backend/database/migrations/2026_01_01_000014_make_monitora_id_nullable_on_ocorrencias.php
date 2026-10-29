<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Torna monitora_id anulável em ocorrencias.
 *
 * Regra de negócio: um administrador pode registrar uma ocorrência sem
 * estar vinculado a um registro de Monitora. Nesse caso a ocorrência
 * fica sem monitora associada (monitora_id = NULL). Antes a coluna era
 * NOT NULL, o que quebrava esse fluxo com violação de FK no PostgreSQL.
 */
return new class extends Migration {
    public function up(): void
    {
        // Remove a FK, altera para nullable e recria a FK com set null.
        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->dropForeign(['monitora_id']);
        });

        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->foreignId('monitora_id')->nullable()->change();
        });

        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->foreign('monitora_id')
                ->references('id')->on('monitoras')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->dropForeign(['monitora_id']);
        });

        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->foreignId('monitora_id')->nullable(false)->change();
        });

        Schema::table('ocorrencias', function (Blueprint $table) {
            $table->foreign('monitora_id')
                ->references('id')->on('monitoras')
                ->cascadeOnDelete();
        });
    }
};
