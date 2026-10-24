<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'tecnico_id'])]
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

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || empty($this->role);
    }

    public function isTecnico(): bool
    {
        return $this->role === self::ROLE_TECNICO;
    }

    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'Administrador' : 'Técnico';
    }
}
