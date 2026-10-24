<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->decimal('gastos_adicionais', 10, 2)->default(0.00)->after('custo_deslocamento');
            $table->string('motivo_gastos_adicionais', 255)->nullable()->after('gastos_adicionais');
        });
    }

    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn(['gastos_adicionais', 'motivo_gastos_adicionais']);
        });
    }
};
