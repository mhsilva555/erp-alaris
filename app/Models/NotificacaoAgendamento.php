<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificacaoAgendamento extends Model
{
    use HasFactory;

    protected $table = 'notificacoes_agendamentos';

    protected $fillable = [
        'tipo_evento',
        'evento_id',
        'titulo',
        'mensagem',
        'url',
        'data_evento',
        'data_notificacao',
        'lida',
        'enviada_so',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data_evento' => 'datetime',
            'data_notificacao' => 'datetime',
            'lida' => 'boolean',
            'enviada_so' => 'boolean',
        ];
    }

    public function scopePendentesSo(Builder $query): Builder
    {
        return $query->where('enviada_so', false)->orderBy('created_at', 'asc');
    }

    public function scopeNaoLidas(Builder $query): Builder
    {
        return $query->where('lida', false)->orderBy('created_at', 'desc');
    }

    public function marcarComoEnviada(): void
    {
        $this->update([
            'enviada_so' => true,
            'data_notificacao' => now(),
        ]);
    }

    public function marcarComoLida(): void
    {
        $this->update([
            'lida' => true,
            'enviada_so' => true,
        ]);
    }
}
