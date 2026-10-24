<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('origem', 50)->default('outros')->after('cep'); // Trafego Pago, Indicacao, Prospeccao Ativa, Redes Sociais, outros
            $table->string('periodicidade', 50)->default('avulso')->after('origem'); // avulso, mensal, bimestral, trimestral, semestral, anual
            $table->string('status_prospeccao', 50)->default('ativo')->after('periodicidade'); // ativo, em_negociacao, prospeccao, retornar_3_meses, contrato_concorrente, inativo
            $table->date('data_followup')->nullable()->after('status_prospeccao');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['origem', 'periodicidade', 'status_prospeccao', 'data_followup']);
        });
    }
};
