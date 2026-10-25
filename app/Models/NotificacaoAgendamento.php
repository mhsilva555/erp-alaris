<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\WebPushService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Illuminate\Support\defer;

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

    /**
     * Toda notificação criada é empurrada via Web Push ao Service Worker dos aparelhos
     * inscritos, garantindo entrega com o PWA fechado ou o celular bloqueado.
     */
    protected static function booted(): void
    {
        static::created(function (self $notificacao): void {
            DB::afterCommit(function () use ($notificacao): void {
                $enviar = fn () => app(WebPushService::class)->enviarNotificacao($notificacao);

                if (app()->runningInConsole()) {
                    $enviar();
                } else {
                    defer($enviar);
                }
            });
        });
    }

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

    /**
     * Filtra notificações pertinentes estritamente ao perfil do usuário conectado.
     * Técnicos de campo recebem apenas notificações atribuídas a eles ou eventos de suas visitas.
     */
    public function scopeParaUsuario(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isTecnico()) {
            return $query->where(function (Builder $q) use ($user) {
                // Notificação explicitamente direcionada ao user do técnico
                $q->where('user_id', $user->id);

                // Ou notificação de visita atribuída a este técnico (caso user_id seja nulo)
                if ($user->tecnico_id) {
                    $q->orWhere(function (Builder $sub) use ($user) {
                        $sub->whereNull('user_id')
                            ->where('tipo_evento', 'visita')
                            ->whereIn('evento_id', function ($sq) use ($user) {
                                $sq->select('id')->from('visitas')->where('tecnico_id', $user->tecnico_id);
                            });
                    });
                }
            });
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)->orWhereNull('user_id');
        });
    }

    /**
     * Verifica se a notificação individual pertence ao usuário informado.
     */
    public function pertenceAoUsuario(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        if ($user->isTecnico() && $user->tecnico_id && $this->tipo_evento === 'visita') {
            return Visita::where('id', $this->evento_id)
                ->where('tecnico_id', $user->tecnico_id)
                ->exists();
        }

        return false;
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

    /**
     * Dispara notificação de nova OS recebida/atribuída ao técnico.
     */
    public static function notificarOsCriada(Visita $visita): ?self
    {
        if (! $visita->tecnico_id) {
            return null;
        }

        $visita->loadMissing(['cliente', 'tecnico.user']);
        $tecnicoUser = $visita->tecnico?->user;
        $clienteNome = $visita->cliente?->nome ?? 'Cliente não informado';

        return self::create([
            'tipo_evento' => 'visita',
            'evento_id' => $visita->id,
            'titulo' => "Nova OS Atribuída: OS #{$visita->id} — {$clienteNome}",
            'mensagem' => "Você recebeu uma nova OS agendada para {$visita->data_hora->format('d/m/Y H:i')}. Serviço: {$visita->tipoServicoLabel()}.",
            'url' => route('visitas.show', $visita, false),
            'data_evento' => $visita->data_hora,
            'user_id' => $tecnicoUser?->id,
            'lida' => false,
            'enviada_so' => false,
        ]);
    }

    /**
     * Dispara notificação de OS atualizada ou cancelada para o técnico responsável.
     */
    public static function notificarOsAtualizada(Visita $visita, ?int $tecnicoAnteriorId = null, ?string $statusAnterior = null): void
    {
        $visita->loadMissing(['cliente', 'tecnico.user']);
        $clienteNome = $visita->cliente?->nome ?? 'Cliente não informado';

        // 1. Se a OS foi cancelada
        if ($visita->status === Visita::STATUS_CANCELADA && $statusAnterior !== Visita::STATUS_CANCELADA) {
            self::notificarOsCancelada($visita);

            return;
        }

        // 2. Se o técnico foi reatribuído (trocou de responsável)
        if ($tecnicoAnteriorId && $tecnicoAnteriorId !== $visita->tecnico_id) {
            $anteriorUser = Tecnico::find($tecnicoAnteriorId)?->user;
            if ($anteriorUser) {
                self::create([
                    'tipo_evento' => 'visita',
                    'evento_id' => $visita->id,
                    'titulo' => "OS Reatribuída: OS #{$visita->id} — {$clienteNome}",
                    'mensagem' => "A Ordem de Serviço #{$visita->id} foi reatribuída a outro profissional.",
                    'url' => route('visitas.show', $visita, false),
                    'data_evento' => $visita->data_hora,
                    'user_id' => $anteriorUser->id,
                    'lida' => false,
                    'enviada_so' => false,
                ]);
            }

            // O novo técnico recebe a notificação de nova OS
            self::notificarOsCriada($visita);

            return;
        }

        // 3. Atualização normal da OS atribuída ao técnico
        if ($visita->tecnico_id) {
            $tecnicoUser = $visita->tecnico?->user;
            self::create([
                'tipo_evento' => 'visita',
                'evento_id' => $visita->id,
                'titulo' => "OS Atualizada: OS #{$visita->id} — {$clienteNome}",
                'mensagem' => "A Ordem de Serviço #{$visita->id} agendada para {$visita->data_hora->format('d/m/Y H:i')} foi atualizada.",
                'url' => route('visitas.show', $visita, false),
                'data_evento' => $visita->data_hora,
                'user_id' => $tecnicoUser?->id,
                'lida' => false,
                'enviada_so' => false,
            ]);
        }
    }

    /**
     * Dispara notificação de cancelamento de OS para o técnico responsável.
     */
    public static function notificarOsCancelada(Visita $visita): ?self
    {
        if (! $visita->tecnico_id) {
            return null;
        }

        $visita->loadMissing(['cliente', 'tecnico.user']);
        $tecnicoUser = $visita->tecnico?->user;
        $clienteNome = $visita->cliente?->nome ?? 'Cliente não informado';

        return self::create([
            'tipo_evento' => 'visita',
            'evento_id' => $visita->id,
            'titulo' => "OS Cancelada: OS #{$visita->id} — {$clienteNome}",
            'mensagem' => "A Ordem de Serviço #{$visita->id} prevista para {$visita->data_hora->format('d/m/Y H:i')} foi cancelada.",
            'url' => route('visitas.show', $visita, false),
            'data_evento' => $visita->data_hora,
            'user_id' => $tecnicoUser?->id,
            'lida' => false,
            'enviada_so' => false,
        ]);
    }
}
