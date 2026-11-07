<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable();
            $table->string('status', 30)->default('rascunho'); // rascunho, ativo, suspenso, encerrado, cancelado, expirado
            $table->string('tipo_vigencia', 20)->default('indeterminado'); // determinado, indeterminado
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->boolean('permite_renovacao')->default(true);
            $table->string('periodicidade', 30)->default('mensal'); // atendimento_unico, semanal, quinzenal, mensal, bimestral, trimestral, semestral, anual, personalizado
            $table->unsignedInteger('periodicidade_dias')->nullable();
            $table->unsignedTinyInteger('dia_preferencial')->nullable(); // 1-31
            $table->unsignedTinyInteger('dia_semana')->nullable(); // 0-6 (0=Domingo, 1=Segunda, etc.)
            $table->string('tipo_servico', 50)->default('controle_pragas');
            $table->decimal('valor_total', 10, 2)->default(0.00);
            $table->string('modelo_cobranca', 50)->default('mensal'); // mensal, por_visita, fechamento_periodo, fixo_anual
            $table->string('condicoes_pagamento')->nullable();
            $table->decimal('desconto', 10, 2)->default(0.00);
            $table->text('observacoes_comerciais')->nullable();
            $table->date('proxima_geracao_ate')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'status']);
            $table->index('status');
        });

        Schema::create('contrato_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->cascadeOnDelete();
            $table->string('tipo_servico', 50)->default('controle_pragas');
            $table->string('descricao');
            $table->decimal('quantidade', 10, 2)->default(1.00);
            $table->decimal('valor_unitario', 10, 2)->default(0.00);
            $table->decimal('valor_total', 10, 2)->default(0.00);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('contrato_id');
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->foreignId('contrato_id')->nullable()->after('comodato_id')->constrained('contratos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS visitas_contrato_id_index');
        }

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropForeign(['contrato_id']);
            $table->dropColumn('contrato_id');
        });

        Schema::dropIfExists('contrato_itens');
        Schema::dropIfExists('contratos');
    }
};
