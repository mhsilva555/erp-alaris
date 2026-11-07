<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->boolean('exige_nota_fiscal')->default(false)->after('modelo_cobranca');
            $table->decimal('percentual_imposto', 5, 2)->default(0.00)->after('exige_nota_fiscal');
            $table->decimal('custo_diaria_padrao', 10, 2)->nullable()->after('percentual_imposto');
            $table->decimal('custo_deslocamento_padrao', 10, 2)->nullable()->after('custo_diaria_padrao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropColumn([
                'exige_nota_fiscal',
                'percentual_imposto',
                'custo_diaria_padrao',
                'custo_deslocamento_padrao',
            ]);
        });
    }
};
