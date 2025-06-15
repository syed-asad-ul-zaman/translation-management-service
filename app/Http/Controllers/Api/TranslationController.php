<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translation\IndexTranslationRequest;
use App\Http\Requests\Translation\SearchTranslationRequest;
use App\Http\Requests\Translation\BulkTranslationRequest;
use App\Http\Requests\Translation\StoreTranslationRequest;
use App\Http\Requests\Translation\UpdateTranslationRequest;
use App\Http\Resources\Translation\TranslationResource;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Translation API Controller
 *
 * Handles all translation-related API operations including CRUD, search, and bulk operations.
 * Implements performance optimizations through eager loading and caching strategies.
 * * @author Syed Asad
 */
class TranslationController extends Controller
{

    public function index(IndexTranslationRequest $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = Translation::with([
            'locale',
            'tags:id,name,slug,description,color,is_active,created_at,updated_at',
            'verifier:id,name'
        ])
            ->select([
                'translations.*',
                DB::raw('CASE WHEN verified_at IS NOT NULL THEN 1 ELSE 0 END as is_verified_sort')
            ]);

        $this->applyFilters($query, $request);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['key', 'value', 'created_at', 'updated_at', 'verified_at', 'is_verified_sort'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $translations = $query->paginate($perPage);

        return TranslationResource::collection($translations);
    }


    public function store(StoreTranslationRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $translation = Translation::create($request->validated());

            if ($request->has('tag_ids')) {
                $translation->tags()->sync($request->input('tag_ids'));
            }

            $translation->load(['locale', 'tags']);

            DB::commit();

            $this->clearTranslationCaches($translation->locale->code);

            return response()->json([
                'message' => 'Translation created successfully.',
                'data' => new TranslationResource($translation),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create translation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
    public function show(Translation $translation): JsonResponse
    {
        $translation->load(['locale', 'tags', 'verifier:id,name']);

        return response()->json([
            'data' => new TranslationResource($translation),
        ]);
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): JsonResponse
    {
        DB::beginTransaction();

        try {
            $oldLocaleCode = $translation->locale->code;

            $translation->update($request->validated());

            if ($request->has('tag_ids')) {
                $translation->tags()->sync($request->input('tag_ids'));
            }

            $translation->load(['locale', 'tags']);

            DB::commit();

            $this->clearTranslationCaches($oldLocaleCode);
            if ($translation->locale->code !== $oldLocaleCode) {
                $this->clearTranslationCaches($translation->locale->code);
            }

            return response()->json([
                'message' => 'Translation updated successfully.',
                'data' => new TranslationResource($translation),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update translation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(Translation $translation): JsonResponse
    {
        try {
            $localeCode = $translation->locale->code;

            $translation->delete();

            $this->clearTranslationCaches($localeCode);

            return response()->json([
                'message' => 'Translation deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete translation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function search(SearchTranslationRequest $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->get('per_page', 15), 100);
        $search = $request->get('q');

        $query = Translation::with(['locale', 'tags'])
            ->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->active();

        if ($locale = $request->get('locale')) {
            $query->byLocale($locale);
        }

        if ($tag = $request->get('tag')) {
            $query->byTag($tag);
        }

        $translations = $query->orderByRaw("
            CASE
                WHEN `key` LIKE ? THEN 1
                WHEN `value` LIKE ? THEN 2
                ELSE 3
            END, created_at DESC
        ", ["{$search}%", "{$search}%"])
            ->paginate($perPage);

        return TranslationResource::collection($translations);
    }
    public function bulk(BulkTranslationRequest $request): JsonResponse
    {
        $action = $request->get('action');
        $ids = $request->get('ids');

        DB::beginTransaction();

        try {
            $count = 0;

            switch ($action) {
                case 'delete':
                    $count = Translation::whereIn('id', $ids)->delete();
                    break;

                case 'activate':
                    $count = Translation::whereIn('id', $ids)->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $count = Translation::whereIn('id', $ids)->update(['is_active' => false]);
                    break;

                case 'verify':
                    $count = Translation::whereIn('id', $ids)->update([
                        'verified_at' => now(),
                        'verified_by' => auth()->id(),
                    ]);
                    break;

                case 'unverify':
                    $count = Translation::whereIn('id', $ids)->update([
                        'verified_at' => null,
                        'verified_by' => null,
                    ]);
                    break;
            }

            DB::commit();

            Cache::tags(['translations'])->flush();

            return response()->json([
                'message' => "Bulk {$action} completed successfully.",
                'affected_count' => $count,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => "Bulk {$action} failed.",
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($locale = $request->get('locale')) {
            $query->byLocale($locale);
        }

        if ($tag = $request->get('tag')) {
            $query->byTag($tag);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->get('is_active'));
        }

        if ($request->has('is_verified')) {
            if ((bool) $request->get('is_verified')) {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        if ($from = $request->get('created_from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->get('created_to')) {
            $query->where('created_at', '<=', $to);
        }
    }

    private function clearTranslationCaches(string $localeCode): void
    {
        $cacheKeys = [
            "translations.export.{$localeCode}",
            "translations.export.all",
            "translations.stats",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Cache::tags(['translations', "locale:{$localeCode}"])->flush();
    }
}
