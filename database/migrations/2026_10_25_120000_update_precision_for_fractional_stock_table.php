<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('quantidade_estoque', 12, 4)->default(0)->change();
            $table->decimal('estoque_minimo', 12, 4)->default(0)->change();
        });

        Schema::table('visita_produto', function (Blueprint $table) {
            $table->decimal('quantidade', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('quantidade_estoque', 10, 2)->default(0)->change();
            $table->decimal('estoque_minimo', 10, 2)->default(0)->change();
        });

        Schema::table('visita_produto', function (Blueprint $table) {
            $table->decimal('quantidade', 10, 2)->change();
        });
    }
};
