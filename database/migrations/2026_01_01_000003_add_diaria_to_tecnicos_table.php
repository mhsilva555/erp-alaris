<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tecnicos', function (Blueprint $table) {
            $table->decimal('valor_diaria_padrao', 10, 2)->default(50.00)->after('especialidade');
        });
    }

    public function down(): void
    {
        Schema::table('tecnicos', function (Blueprint $table) {
            $table->dropColumn('valor_diaria_padrao');
        });
    }
};
