<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['nome', 'slug', 'descricao', 'is_sistema'])]
class Perfil extends Model
{
    use HasFactory;

    protected $table = 'perfis';

    protected function casts(): array
    {
        return [
            'is_sistema' => 'boolean',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'perfil_id');
    }

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao')->withTimestamps();
    }

    /**
     * Verifica se o perfil possui uma permissão específica por chave (ex: 'clientes.criar').
     */
    public function temPermissao(string $chave): bool
    {
        // Se for perfil de Administrador do sistema, possui acesso irrestrito
        if ($this->slug === 'admin') {
            return true;
        }

        return $this->permissoes->contains('chave', $chave);
    }

    /**
     * Atribui uma lista de permissões (IDs ou chaves) a este perfil.
     */
    public function sincronizarPermissoes(array $permissoes): void
    {
        if (empty($permissoes)) {
            $this->permissoes()->detach();

            return;
        }

        // Se forem chaves (strings não numéricas), converte para IDs
        $primeiro = reset($permissoes);
        if (! is_numeric($primeiro)) {
            $ids = Permissao::whereIn('chave', $permissoes)->pluck('id')->toArray();
        } else {
            $ids = $permissoes;
        }

        $this->permissoes()->sync($ids);
    }

    /**
     * Gera slug padronizado caso não tenha sido explicitado.
     */
    protected static function booted(): void
    {
        static::creating(function (Perfil $perfil) {
            if (empty($perfil->slug)) {
                $perfil->slug = Str::slug($perfil->nome, '_');
            }
        });
    }
}
