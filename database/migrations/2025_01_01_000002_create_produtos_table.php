<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('unidade')->default('L'); // L, Kg, Un, etc.
            $table->decimal('quantidade_estoque', 10, 2)->default(0);
            $table->decimal('estoque_minimo', 10, 2)->default(0);
            $table->text('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
