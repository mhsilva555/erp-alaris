<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meios_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('codigo', 50)->unique();
            $table->string('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('meio_pagamento_id')
                ->nullable()
                ->after('modelo_cobranca')
                ->constrained('meios_pagamento')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['meio_pagamento_id']);
            $table->dropColumn('meio_pagamento_id');
        });

        Schema::dropIfExists('meios_pagamento');
    }
};
