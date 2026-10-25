<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Perfis de Acesso (Roles)
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('slug', 100)->unique();
            $table->string('descricao')->nullable();
            $table->boolean('is_sistema')->default(false);
            $table->timestamps();
        });

        // 2. Tabela de Permissões Granulares
        Schema::create('permissoes', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 50); // ex: clientes, visitas, financeiro, etc.
            $table->string('nome', 150);
            $table->string('chave', 100)->unique(); // ex: clientes.ver, visitas.finalizar
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        // 3. Tabela Pivot Perfil x Permissão
        Schema::create('perfil_permissao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $table->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['perfil_id', 'permissao_id']);
        });

        // 4. Vínculo do Usuário com o Perfil
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('perfil_id')->nullable()->after('role')->constrained('perfis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['perfil_id']);
            $table->dropColumn(['perfil_id']);
        });

        Schema::dropIfExists('perfil_permissao');
        Schema::dropIfExists('permissoes');
        Schema::dropIfExists('perfis');
    }
};
