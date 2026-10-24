<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_equipamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('slug')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        $defaultCategorias = [
            ['nome' => 'Porta-Iscas / Túnel', 'slug' => 'porta_iscas'],
            ['nome' => 'Armadilha Luminosa', 'slug' => 'armadilha_luminosa'],
            ['nome' => 'Pulverizador / Atomizador', 'slug' => 'pulverizador'],
            ['nome' => 'Termonebulizador', 'slug' => 'termonebulizador'],
            ['nome' => 'Polvilhadeira', 'slug' => 'polvilhadeira'],
            ['nome' => 'Outros Equipamentos', 'slug' => 'outros'],
        ];

        foreach ($defaultCategorias as $cat) {
            DB::table('categoria_equipamentos')->insert([
                'nome' => $cat['nome'],
                'slug' => $cat['slug'],
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_equipamentos');
    }
};
