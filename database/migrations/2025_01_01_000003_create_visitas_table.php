<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->dateTime('data_hora');
            $table->string('status')->default('agendada'); // agendada, em_andamento, concluida, cancelada
            $table->string('tecnico');
            $table->string('tipo_praga')->nullable();
            $table->string('area_tratada')->nullable();
            $table->text('observacoes')->nullable();
            $table->dateTime('proxima_visita')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
