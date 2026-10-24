<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('natureza', 20)->default('liquido')->after('nome'); // liquido, solido, unidade
            $table->string('unidade_armazenamento', 10)->default('L')->after('natureza'); // L, Kg, Un
            $table->string('unidade_aplicacao', 10)->default('mL')->after('unidade_armazenamento'); // mL, g, Un
            $table->decimal('fator_conversao', 10, 4)->default(1000.0000)->after('unidade_aplicacao'); // 1 L = 1000 mL, 1 Kg = 1000 g
            $table->decimal('preco_custo_unitario', 10, 2)->default(0.00)->after('estoque_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn([
                'natureza',
                'unidade_armazenamento',
                'unidade_aplicacao',
                'fator_conversao',
                'preco_custo_unitario',
            ]);
        });
    }
};
