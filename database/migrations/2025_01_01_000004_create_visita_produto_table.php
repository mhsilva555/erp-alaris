<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visita_produto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visita_id')->constrained('visitas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->decimal('quantidade', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visita_produto');
    }
};
