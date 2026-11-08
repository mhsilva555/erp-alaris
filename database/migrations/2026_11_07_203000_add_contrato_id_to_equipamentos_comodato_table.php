<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipamentos_comodato', function (Blueprint $table) {
            $table->foreignId('contrato_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('contratos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipamentos_comodato', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contrato_id');
        });
    }
};
