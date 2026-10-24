<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CategoriaEquipamento extends Model
{
    use HasFactory;

    protected $table = 'categoria_equipamentos';

    protected $fillable = [
        'nome',
        'slug',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CategoriaEquipamento $categoria) {
            if (empty($categoria->slug)) {
                $baseSlug = Str::slug($categoria->nome, '_');
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}_{$counter}";
                    $counter++;
                }
                $categoria->slug = $slug;
            }
        });
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function equipamentos(): HasMany
    {
        return $this->hasMany(Equipamento::class, 'tipo', 'slug');
    }
}
