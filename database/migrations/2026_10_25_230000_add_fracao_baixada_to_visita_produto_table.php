<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visita_produto', function (Blueprint $table) {
            $table->decimal('fracao_baixada', 12, 4)->default(0)->after('custo_proporcional');
        });

        // Para visitas já existentes com status 'concluida', a fração já havia sido baixada do estoque.
        // Registramos a fração já baixada para garantir consistência histórica e evitar dupla baixa.
        $concluidas = DB::table('visita_produto')
            ->join('visitas', 'visitas.id', '=', 'visita_produto.visita_id')
            ->join('produtos', 'produtos.id', '=', 'visita_produto.produto_id')
            ->where('visitas.status', 'concluida')
            ->select('visita_produto.id', 'visita_produto.quantidade', 'produtos.fator_conversao')
            ->get();

        foreach ($concluidas as $item) {
            $fator = (float) $item->fator_conversao;
            $fracao = $fator > 0 ? round((float) $item->quantidade / $fator, 4) : (float) $item->quantidade;
            DB::table('visita_produto')->where('id', $item->id)->update(['fracao_baixada' => $fracao]);
        }
    }

    public function down(): void
    {
        Schema::table('visita_produto', function (Blueprint $table) {
            $table->dropColumn('fracao_baixada');
        });
    }
};
