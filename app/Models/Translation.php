<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Translation Model
 *
 * Represents a translation entry with key-value pairs for different locales
 * Optimized for performance and follows SOLID principles
 */
class Translation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'locale_id',
        'description',
        'metadata',
        'is_active',
        'verified_at',
        'verified_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Translation $translation) {
            $translation->key = str($translation->key)->trim()->lower()->toString();
        });

        static::updating(function (Translation $translation) {
            if ($translation->isDirty('key')) {
                $translation->key = str($translation->key)->trim()->lower()->toString();
            }
        });
    }

    /**
     * Get the locale that owns the translation.
     */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }

    /**
     * Get the user who verified this translation.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the tags associated with the translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TranslationTag::class, 'translation_translation_tag');
    }

    /**
     * Scope: Active translations only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Find by key
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', 'like', "%{$key}%");
    }

    /**
     * Scope: Find by exact key
     */
    public function scopeByExactKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope: Find by locale
     */
    public function scopeByLocale(Builder $query, string $localeCode): Builder
    {
        return $query->whereHas('locale', function (Builder $q) use ($localeCode) {
            $q->where('code', $localeCode);
        });
    }

    /**
     * Scope: Find by tag
     */
    public function scopeByTag(Builder $query, string $tagSlug): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    /**
     * Scope: Search in value content
     */
    public function scopeSearchValue(Builder $query, string $search): Builder
    {
        return $query->where('value', 'like', "%{$search}%");
    }

    /**
     * Scope: Verified translations only
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope: Unverified translations only
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Get formatted key attribute
     */
    protected function formattedKey(): Attribute
    {
        return Attribute::make(
            get: fn () => str($this->key)->title()->replace('.', ' → ')->toString()
        );
    }

    /**
     * Get short value attribute for listings
     */
    protected function shortValue(): Attribute
    {
        return Attribute::make(
            get: fn () => str($this->value)->limit(100)->toString()
        );
    }

    /**
     * Check if translation is verified
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Mark translation as verified
     */
    public function markAsVerified(int $userId = null): void
    {
        $this->update([
            'verified_at' => Carbon::now(),
            'verified_by' => $userId,
        ]);
    }

    /**
     * Mark translation as unverified
     */
    public function markAsUnverified(): void
    {
        $this->update([
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    /**
     * Get all translations grouped by locale for a specific key
     */
    public static function getByKeyGroupedByLocale(string $key): array
    {
        return static::with('locale')
            ->byExactKey($key)
            ->active()
            ->get()
            ->groupBy('locale.code')
            ->map(fn ($translations) => $translations->first())
            ->toArray();
    }
}
