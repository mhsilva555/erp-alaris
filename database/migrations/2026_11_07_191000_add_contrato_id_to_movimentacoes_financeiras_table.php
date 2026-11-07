<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes_financeiras', function (Blueprint $table) {
            $table->foreignId('contrato_id')->nullable()->after('visita_id')->constrained('contratos')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->after('contrato_id')->constrained('clientes')->nullOnDelete();
            $table->date('data_vencimento')->nullable()->after('data_competencia');
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_financeiras', function (Blueprint $table) {
            $table->dropForeign(['contrato_id']);
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['contrato_id', 'cliente_id', 'data_vencimento']);
        });
    }
};
