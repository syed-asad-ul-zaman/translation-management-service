<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Locale Model
 *
 * Represents a language/locale for translations
 * Follows PSR-12 and SOLID principles
 */
class Locale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_active',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Locale $locale) {
            if (empty($locale->code)) {
                $locale->code = strtolower($locale->name);
            }
        });
    }

    /**
     * Get all translations for this locale.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    /**
     * Get active translations for this locale.
     */
    public function activeTranslations(): HasMany
    {
        return $this->hasMany(Translation::class)
            ->where('is_active', true);
    }

    /**
     * Scope: Active locales only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default locale only
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Find by code
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Get display name attribute
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->native_name ?? $this->name
        );
    }

    /**
     * Check if locale has translations
     */
    public function hasTranslations(): bool
    {
        return $this->translations()->exists();
    }

    /**
     * Get translation count for this locale
     */
    public function getTranslationCountAttribute(): int
    {
        return $this->translations()->count();
    }
}
