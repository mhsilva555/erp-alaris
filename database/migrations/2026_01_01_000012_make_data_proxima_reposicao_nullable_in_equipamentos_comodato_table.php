<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipamentos_comodato', function (Blueprint $table) {
            $table->date('data_proxima_reposicao')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('equipamentos_comodato', function (Blueprint $table) {
            $table->date('data_proxima_reposicao')->nullable(false)->change();
        });
    }
};
