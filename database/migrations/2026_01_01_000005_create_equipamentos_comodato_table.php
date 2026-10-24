<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipamentos_comodato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('nome_equipamento');
            $table->unsignedInteger('quantidade')->default(1);
            $table->unsignedInteger('periodicidade_dias')->default(15);
            $table->date('data_instalacao');
            $table->date('data_ultima_reposicao')->nullable();
            $table->date('data_proxima_reposicao');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipamentos_comodato');
    }
};
