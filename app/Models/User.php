<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'perfil_id', 'tecnico_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_TECNICO = 'tecnico';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN
            || empty($this->role)
            || ($this->perfil && $this->perfil->slug === 'admin');
    }

    public function isTecnico(): bool
    {
        return $this->role === self::ROLE_TECNICO
            || ($this->perfil && $this->perfil->slug === 'tecnico');
    }

    public function roleLabel(): string
    {
        if ($this->perfil) {
            return $this->perfil->nome;
        }

        return $this->isAdmin() ? 'Administrador' : 'Técnico';
    }

    /**
     * Verifica se o usuário possui uma permissão granular específica.
     */
    public function temPermissao(string $chave): bool
    {
        // 1. Administrador possui acesso total irrestrito a qualquer recurso
        if ($this->isAdmin()) {
            return true;
        }

        // 2. Se o usuário possui um perfil atribuído com permissões
        if ($this->perfil) {
            return $this->perfil->temPermissao($chave);
        }

        // 3. Fallback de compatibilidade para usuários com role legado 'tecnico'
        if ($this->isTecnico()) {
            $permissoesTecnicoPadrao = [
                'dashboard.ver',
                'dashboard.card_visitas_hoje',
                'dashboard.card_visitas_mes',
                'dashboard.proximas_visitas',
                'dashboard.historico_visitas',
                'notificacoes.ver',
                'notificacoes.editar',
                'notificacoes.alertas_navegador',
                'visitas.ver',
                'visitas.finalizar',
                'visitas.gerar_os',
                'comodatos.ver',
                'comodatos.registrar_reposicao',
                'agendamentos.ver',
                'produtos.ver',
            ];

            return in_array($chave, $permissoesTecnicoPadrao, true);
        }

        return false;
    }

    /**
     * Verifica se o usuário possui ao menos uma das permissões informadas.
     */
    public function temAlgumaPermissao(array $chaves): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($chaves as $chave) {
            if ($this->temPermissao($chave)) {
                return true;
            }
        }

        return false;
    }
}
