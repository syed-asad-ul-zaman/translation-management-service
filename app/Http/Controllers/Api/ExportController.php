<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportAllRequest;
use App\Http\Requests\Export\ExportKeysRequest;
use App\Http\Requests\Export\ExportLocaleRequest;
use App\Http\Requests\Export\ExportTagsRequest;
use App\Models\Translation;
use App\Models\Locale;
use App\Services\CdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;



/**
 * Export Controller
 *
 * Handles JSON export endpoints for frontend applications.
 * Optimized for performance with caching and efficient queries.
 *
 * @author Syed Asad
 */
class ExportController extends Controller
{

    protected CdnService $cdnService;

    public function __construct(CdnService $cdnService)
    {
        $this->cdnService = $cdnService;
    }

    public function locale(ExportLocaleRequest $request, string $localeCode): JsonResponse
    {
        $cacheKey = $this->buildCacheKey('locale', $localeCode, $request->all());

        $cacheTtl = config('app.env') === 'production' ? 3600 : 300;

        $data = Cache::remember($cacheKey, $cacheTtl, function () use ($localeCode, $request) {
            return $this->fetchLocaleTranslations($localeCode, $request);
        });

        // Cache to CDN if enabled
        if ($this->cdnService->isEnabled()) {
            $this->cdnService->cacheTranslationExport($localeCode, $data);
        }

        $response = [
            'locale' => $localeCode,
            'translations' => $data['translations'],
            'meta' => [
                'total_count' => $data['total_count'],
                'active_count' => $data['active_count'],
                'last_updated' => $data['last_updated'],
                'generated_at' => now()->toISOString(),
                'cache_key' => $cacheKey,
            ],
        ];

        // Add CDN URL if enabled
        if ($this->cdnService->isEnabled()) {
            $response['meta']['cdn_url'] = $this->cdnService->translationExportUrl($localeCode, $request->all());
        }

        return response()->json($response);
    }

    public function all(ExportAllRequest $request): JsonResponse
    {
        $cacheKey = $this->buildCacheKey('all', 'all', $request->all());

        $cacheTtl = config('app.env') === 'production' ? 3600 : 300;

        $data = Cache::remember($cacheKey, $cacheTtl, function () use ($request) {
            return $this->fetchAllTranslations($request);
        });

        // Cache to CDN if enabled
        if ($this->cdnService->isEnabled()) {
            $this->cdnService->cacheTranslationExport('all', $data);
        }

        $response = [
            'locales' => $data['locales'],
            'translations' => $data['translations'],
            'meta' => [
                'total_locales' => count($data['locales']),
                'total_translations' => $data['total_count'],
                'last_updated' => $data['last_updated'],
                'generated_at' => now()->toISOString(),
                'cache_key' => $cacheKey,
            ],
        ];

        // Add CDN URL if enabled
        if ($this->cdnService->isEnabled()) {
            $response['meta']['cdn_url'] = $this->cdnService->translationExportUrl('all', $request->all());
        }

        return response()->json($response);
    }
    public function keys(ExportKeysRequest $request): JsonResponse
    {
        $keys = $request->getKeys();
        $locales = $request->getLocales();

        $cacheKey = $this->buildCacheKey('keys', implode(',', $keys), $request->all());

        $data = Cache::remember($cacheKey, 300, function () use ($keys, $locales, $request) {
            return $this->fetchTranslationsByKeys($keys, $locales, $request);
        });

        return response()->json([
            'keys' => $keys,
            'translations' => $data['translations'],
            'meta' => [
                'requested_keys' => count($keys),
                'found_translations' => $data['found_count'],
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Export translations by tags.
     *
     * @param ExportTagsRequest $request
     * @param string $tagSlug
     * @return JsonResponse
     */
    public function tags(ExportTagsRequest $request, string $tagSlug): JsonResponse
    {
        $cacheKey = $this->buildCacheKey('tag', $tagSlug, $request->all());

        $data = Cache::remember($cacheKey, 600, function () use ($tagSlug, $request) {
            return $this->fetchTranslationsByTag($tagSlug, $request);
        });

        return response()->json([
            'tag' => $tagSlug,
            'translations' => $data['translations'],
            'meta' => [
                'total_count' => $data['total_count'],
                'locales' => $data['locales'],
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get export statistics and cache status.
     */
    public function stats(): JsonResponse
    {
        $cacheKey = 'translations.export.stats';

        $stats = Cache::remember($cacheKey, 1800, function () {
            return [
                'total_translations' => Translation::count(),
                'active_translations' => Translation::active()->count(),
                'verified_translations' => Translation::verified()->count(),
                'total_locales' => Locale::active()->count(),
                'translations_per_locale' => DB::table('translations')
                    ->join('locales', 'translations.locale_id', '=', 'locales.id')
                    ->where('translations.is_active', true)
                    ->where('locales.is_active', true)
                    ->groupBy('locales.code', 'locales.name')
                    ->selectRaw('locales.code, locales.name, COUNT(*) as count')
                    ->orderBy('count', 'desc')
                    ->get(),
                'last_updated' => Translation::latest('updated_at')->value('updated_at'),
            ];
        });

        return response()->json([
            'stats' => $stats,
            'cache' => [
                'generated_at' => now()->toISOString(),
                'ttl_seconds' => 1800,
            ],
        ]);
    }

    private function fetchLocaleTranslations(string $localeCode, ExportLocaleRequest $request): array
    {
        $query = Translation::with(['tags'])
            ->byLocale($localeCode)
            ->active()
            ->select(['key', 'value', 'metadata', 'updated_at']);

        if ($tags = $request->get('tags')) {
            $tagSlugs = explode(',', $tags);
            $query->whereHas('tags', function ($q) use ($tagSlugs) {
                $q->whereIn('slug', $tagSlugs);
            });
        }

        $translations = $query->get();

        $format = $request->get('format', 'flat');
        $includeMetadata = $request->boolean('include_metadata');

        $formattedTranslations = $this->formatTranslations($translations, $format, $includeMetadata);

        return [
            'translations' => $formattedTranslations,
            'total_count' => $translations->count(),
            'active_count' => $translations->count(),
            'last_updated' => $translations->max('updated_at'),
        ];
    }

    private function fetchAllTranslations(ExportAllRequest $request): array
    {
        $activeOnly = $request->boolean('active_only', true);

        $query = Translation::with(['locale', 'tags'])
            ->select(['translations.key', 'translations.value', 'translations.metadata', 'translations.updated_at', 'translations.locale_id'])
            ->join('locales', 'translations.locale_id', '=', 'locales.id')
            ->where('locales.is_active', true);

        if ($activeOnly) {
            $query->where('translations.is_active', true);
        }

        if ($tags = $request->get('tags')) {
            $tagSlugs = explode(',', $tags);
            $query->whereHas('tags', function ($q) use ($tagSlugs) {
                $q->whereIn('slug', $tagSlugs);
            });
        }

        $translations = $query->get();

        $format = $request->get('format', 'flat');
        $includeMetadata = $request->boolean('include_metadata');

        $groupedTranslations = $translations->groupBy('locale.code');
        $formattedTranslations = [];

        foreach ($groupedTranslations as $localeCode => $localeTranslations) {
            $formattedTranslations[$localeCode] = $this->formatTranslations($localeTranslations, $format, $includeMetadata);
        }

        return [
            'locales' => $groupedTranslations->keys()->toArray(),
            'translations' => $formattedTranslations,
            'total_count' => $translations->count(),
            'last_updated' => $translations->max('updated_at'),
        ];
    }

    private function fetchTranslationsByKeys(array $keys, ?array $locales, ExportKeysRequest $request): array
    {
        $query = Translation::with(['locale'])
            ->whereIn('key', $keys)
            ->active();

        if ($locales) {
            $query->whereHas('locale', function ($q) use ($locales) {
                $q->whereIn('code', $locales);
            });
        }

        $translations = $query->get();

        $includeMetadata = $request->boolean('include_metadata');
        $groupedTranslations = $translations->groupBy(['locale.code', 'key']);

        $formattedTranslations = [];
        foreach ($groupedTranslations as $localeCode => $localeTranslations) {
            foreach ($localeTranslations as $key => $keyTranslations) {
                $translation = $keyTranslations->first();
                $formattedTranslations[$localeCode][$key] = $includeMetadata
                    ? ['value' => $translation->value, 'metadata' => $translation->metadata]
                    : $translation->value;
            }
        }

        return [
            'translations' => $formattedTranslations,
            'found_count' => $translations->count(),
        ];
    }
    private function fetchTranslationsByTag(string $tagSlug, ExportTagsRequest $request): array
    {
        $query = Translation::with(['locale'])
            ->byTag($tagSlug)
            ->active();

        if ($locales = $request->get('locales')) {
            $query->whereHas('locale', function ($q) use ($locales) {
                $q->whereIn('code', $locales);
            });
        }

        $translations = $query->get();

        $format = $request->get('format', 'flat');
        $includeMetadata = $request->boolean('include_metadata');

        $groupedTranslations = $translations->groupBy('locale.code');
        $formattedTranslations = [];

        foreach ($groupedTranslations as $localeCode => $localeTranslations) {
            $formattedTranslations[$localeCode] = $this->formatTranslations($localeTranslations, $format, $includeMetadata);
        }

        return [
            'translations' => $formattedTranslations,
            'total_count' => $translations->count(),
            'locales' => $groupedTranslations->keys()->toArray(),
        ];
    }

    private function formatTranslations($translations, string $format, bool $includeMetadata): array
    {
        $formatted = [];

        foreach ($translations as $translation) {
            $value = $includeMetadata
                ? ['value' => $translation->value, 'metadata' => $translation->metadata]
                : $translation->value;

            if ($format === 'nested') {
                $this->setNestedValue($formatted, $translation->key, $value);
            } else {
                $formatted[$translation->key] = $value;
            }
        }

        return $formatted;
    }

    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    private function buildCacheKey(string $type, string $identifier, array $params): string
    {
        $paramString = http_build_query($params);
        $hash = md5($paramString);

        return "translations.export.{$type}.{$identifier}.{$hash}";
    }
}
