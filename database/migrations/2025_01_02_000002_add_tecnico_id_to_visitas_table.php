<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->unsignedBigInteger('tecnico_id')->nullable()->after('cliente_id');
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn('tecnico');
        });
    }

    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->string('tecnico')->nullable();
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn('tecnico_id');
        });
    }
};
