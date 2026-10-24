<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notificacoes_agendamentos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_evento', 30); // 'visita', 'comodato', 'followup'
            $table->unsignedBigInteger('evento_id');
            $table->string('titulo');
            $table->text('mensagem');
            $table->string('url')->default('/agendamentos');
            $table->dateTime('data_evento');
            $table->dateTime('data_notificacao')->nullable();
            $table->boolean('lida')->default(false);
            $table->boolean('enviada_so')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['tipo_evento', 'evento_id', 'data_evento'], 'notif_agendamento_idx');
            $table->index('lida');
            $table->index('enviada_so');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacoes_agendamentos');
    }
};
