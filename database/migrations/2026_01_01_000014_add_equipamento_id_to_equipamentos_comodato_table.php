<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipamentos_comodato', function (Blueprint $table) {
            $table->foreignId('equipamento_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('equipamentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipamentos_comodato', function (Blueprint $table) {
            $table->dropForeign(['equipamento_id']);
            $table->dropColumn('equipamento_id');
        });
    }
};
