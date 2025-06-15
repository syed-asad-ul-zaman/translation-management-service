<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * TranslationTag Model
 *
 * Represents tags for categorizing translations (mobile, desktop, web, etc.)
 * Follows PSR-12 and SOLID principles
 */
class TranslationTag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (TranslationTag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function (TranslationTag $tag) {
            if ($tag->isDirty('name') && !$tag->isDirty('slug')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Get the translations associated with the tag.
     */
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class, 'translation_translation_tag');
    }

    /**
     * Get active translations associated with the tag.
     */
    public function activeTranslations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class, 'translation_translation_tag')
            ->where('translations.is_active', true);
    }

    /**
     * Scope: Active tags only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by slug
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearchName(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get display name with translation count
     */
    protected function displayNameWithCount(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->name} ({$this->translation_count})"
        );
    }

    /**
     * Get translation count for this tag
     */
    public function getTranslationCountAttribute(): int
    {
        return $this->translations()->count();
    }

    /**
     * Get active translation count for this tag
     */
    public function getActiveTranslationCountAttribute(): int
    {
        return $this->activeTranslations()->count();
    }

    /**
     * Check if tag has translations
     */
    public function hasTranslations(): bool
    {
        return $this->translations()->exists();
    }

    /**
     * Get most used tags (ordered by translation count)
     */
    public static function getMostUsed(int $limit = 10): array
    {
        return static::active()
            ->withCount('translations')
            ->orderBy('translations_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
