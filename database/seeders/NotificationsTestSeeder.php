<?php

namespace Database\Seeders;

use App\Models\NotificacaoAgendamento;
use Illuminate\Database\Seeder;

class NotificationsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i < 3; $i++) {
            NotificacaoAgendamento::create([
                'tipo_evento' => 'visita',
                'evento_id' => $i,
                'titulo' => "Visita Agendada: OS #{$i}",
                'mensagem' => "Visita agendada para o cliente #{$i}",
                'url' => '#',
                'data_evento' => now()->addDays($i),
                'data_notificacao' => now()->addMinutes($i),
                'lida' => false,
                'enviada_so' => false,
                'user_id' => 1,
            ]);
        }
    }
}
