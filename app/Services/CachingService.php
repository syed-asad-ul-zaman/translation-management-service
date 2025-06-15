<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Advanced Caching Service
 *
 * Handles intelligent caching strategies for translation management
 * Implements cache warming, invalidation, and performance optimization
 *
 * @author Syed Asad
 */
class CachingService
{
    /**
     * Cache prefixes for different data types
     */
    private const CACHE_PREFIXES = [
        'translations' => 'trans',
        'locales' => 'locales',
        'tags' => 'tags',
        'exports' => 'exports',
        'stats' => 'stats',
    ];

    /**
     * Default cache TTL in seconds
     */
    private const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Generate cache key
     */
    public function key(string $type, string $identifier, array $params = []): string
    {
        $prefix = self::CACHE_PREFIXES[$type] ?? $type;
        $paramString = empty($params) ? '' : ':' . md5(serialize($params));

        return sprintf('%s:%s%s', $prefix, $identifier, $paramString);
    }

    /**
     * Get cached data with fallback
     */
    public function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Get cached data forever with fallback
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Cache translation export data
     */
    public function cacheExport(string $locale, array $data, array $params = []): bool
    {
        $key = $this->key('exports', $locale, $params);

        // Cache for 1 hour by default, longer for stable exports
        $ttl = empty($params) ? 7200 : 3600; // 2 hours for simple exports

        return Cache::put($key, $data, $ttl);
    }

    /**
     * Get cached export data
     */
    public function getExport(string $locale, array $params = []): ?array
    {
        $key = $this->key('exports', $locale, $params);
        return Cache::get($key);
    }

    /**
     * Invalidate translation caches
     */
    public function invalidateTranslations(int $localeId = null): void
    {
        $patterns = [
            self::CACHE_PREFIXES['translations'] . ':*',
            self::CACHE_PREFIXES['exports'] . ':*',
            self::CACHE_PREFIXES['stats'] . ':*',
        ];

        if ($localeId) {
            $patterns[] = self::CACHE_PREFIXES['locales'] . ':' . $localeId;
        }

        $this->invalidateByPatterns($patterns);
    }

    /**
     * Invalidate tag caches
     */
    public function invalidateTags(): void
    {
        $patterns = [
            self::CACHE_PREFIXES['tags'] . ':*',
            self::CACHE_PREFIXES['exports'] . ':*', // Exports might be filtered by tags
        ];

        $this->invalidateByPatterns($patterns);
    }

    /**
     * Warm up caches for common queries
     */
    public function warmUp(): void
    {
        // Warm up locale list
        $this->remember(
            $this->key('locales', 'active'),
            fn() => \App\Models\Locale::active()->get()->toArray()
        );

        // Warm up popular tags
        $this->remember(
            $this->key('tags', 'popular'),
            fn() => \App\Models\TranslationTag::withCount('translations')
                ->orderBy('translations_count', 'desc')
                ->limit(20)
                ->get()
                ->toArray()
        );

        // Warm up translation counts
        $this->remember(
            $this->key('stats', 'counts'),
            fn() => [
                'total_translations' => \App\Models\Translation::count(),
                'active_translations' => \App\Models\Translation::active()->count(),
                'total_locales' => \App\Models\Locale::count(),
                'active_locales' => \App\Models\Locale::active()->count(),
                'total_tags' => \App\Models\TranslationTag::count(),
            ]
        );
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'redis_connected' => $this->isRedisConnected(),
            'memory_usage' => $this->getMemoryUsage(),
            'hit_ratio' => $this->getHitRatio(),
        ];
    }

    /**
     * Invalidate caches by patterns
     */
    private function invalidateByPatterns(array $patterns): void
    {
        if (config('cache.default') === 'redis') {
            $this->invalidateRedisPatterns($patterns);
        } else {
            // For other cache drivers, we'll need to track keys manually
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Invalidate Redis keys by patterns
     */
    private function invalidateRedisPatterns(array $patterns): void
    {
        $redis = Redis::connection();

        foreach ($patterns as $pattern) {
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }

    /**
     * Check if Redis is connected
     */
    private function isRedisConnected(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get memory usage information
     */
    private function getMemoryUsage(): array
    {
        if (config('cache.default') === 'redis') {
            try {
                $info = Redis::connection()->info('memory');
                return [
                    'used_memory' => $info['used_memory'] ?? 0,
                    'used_memory_human' => $info['used_memory_human'] ?? '0B',
                    'max_memory' => $info['maxmemory'] ?? 0,
                ];
            } catch (\Exception $e) {
                return ['error' => 'Unable to get Redis memory info'];
            }
        }

        return ['php_memory' => memory_get_usage(true)];
    }

    /**
     * Calculate cache hit ratio (mock implementation)
     */
    private function getHitRatio(): float
    {
        // This would require tracking hits/misses
        // For now, return a placeholder
        return 0.85; // 85% hit ratio
    }
}
