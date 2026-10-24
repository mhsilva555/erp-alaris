<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visita_produto', function (Blueprint $table) {
            $table->decimal('custo_proporcional', 10, 2)->default(0.00)->after('quantidade');
        });
    }

    public function down(): void
    {
        Schema::table('visita_produto', function (Blueprint $table) {
            $table->dropColumn('custo_proporcional');
        });
    }
};
