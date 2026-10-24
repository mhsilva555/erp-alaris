<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->string('tipo_visita', 30)->default('execucao')->after('status'); // comercial, tecnica, demonstracao, execucao
            $table->string('tipo_servico', 30)->default('controle_pragas')->after('tipo_visita'); // controle_pragas, limpeza
            $table->decimal('valor_cobrado', 10, 2)->default(0.00)->after('proxima_visita');
            $table->decimal('custo_produtos', 10, 2)->default(0.00)->after('valor_cobrado');
            $table->decimal('custo_mao_de_obra', 10, 2)->default(0.00)->after('custo_produtos');
            $table->decimal('custo_deslocamento', 10, 2)->default(0.00)->after('custo_mao_de_obra');
            $table->boolean('exige_nota_fiscal')->default(false)->after('custo_deslocamento');
            $table->decimal('percentual_imposto', 5, 2)->default(0.00)->after('exige_nota_fiscal');
            $table->decimal('valor_imposto', 10, 2)->default(0.00)->after('percentual_imposto');
            $table->decimal('lucro_bruto', 10, 2)->default(0.00)->after('valor_imposto');
        });
    }

    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_visita',
                'tipo_servico',
                'valor_cobrado',
                'custo_produtos',
                'custo_mao_de_obra',
                'custo_deslocamento',
                'exige_nota_fiscal',
                'percentual_imposto',
                'valor_imposto',
                'lucro_bruto',
            ]);
        });
    }
};
