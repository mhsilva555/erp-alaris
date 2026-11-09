<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_empresa', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); // Ex.: Matriz Imperatriz, Filial Açailândia
            $table->string('tipo', 20)->default('matriz'); // 'matriz' ou 'filial'
            $table->string('razao_social');
            $table->string('nome_fantasia');
            $table->string('cnpj', 30);
            $table->string('licenca_sanitaria')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('slogan')->nullable();
            $table->string('cep', 20)->nullable();
            $table->string('endereco')->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('estado', 2)->nullable();
            $table->boolean('padrao')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('unidade_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('unidades_empresa')
                ->nullOnDelete();
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->foreignId('unidade_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('unidades_empresa')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidade_id');
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unidade_id');
        });

        Schema::dropIfExists('unidades_empresa');
    }
};
