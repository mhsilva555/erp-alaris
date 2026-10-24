<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('modelo')->nullable();
            $table->string('tipo', 50)->default('porta_iscas'); // porta_iscas, armadilha_luminosa, pulverizador, outros
            $table->unsignedInteger('quantidade_total')->default(0);
            $table->unsignedInteger('quantidade_disponivel')->default(0);
            $table->decimal('valor_unitario', 10, 2)->default(0.00);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipamentos');
    }
};
