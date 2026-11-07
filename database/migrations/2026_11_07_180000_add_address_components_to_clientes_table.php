<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('logradouro')->nullable()->after('endereco');
            $table->string('numero', 50)->nullable()->after('logradouro');
            $table->string('bairro', 100)->nullable()->after('numero');
            $table->string('complemento', 100)->nullable()->after('bairro');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['logradouro', 'numero', 'bairro', 'complemento']);
        });
    }
};
