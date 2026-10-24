<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_financeiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visita_id')->nullable()->constrained('visitas')->nullOnDelete();
            $table->string('tipo', 10); // receita, despesa
            $table->string('categoria', 50); // faturamento_servico, compra_estoque, diaria_tecnico, impostos, deslocamento, custo_fixo, outros
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_competencia');
            $table->date('data_pagamento')->nullable();
            $table->string('status', 20)->default('pendente'); // pendente, pago, cancelado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_financeiras');
    }
};
