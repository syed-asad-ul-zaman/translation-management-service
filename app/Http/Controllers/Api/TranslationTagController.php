<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TranslationTag\IndexTranslationTagRequest;
use App\Http\Requests\TranslationTag\StoreTranslationTagRequest;
use App\Http\Requests\TranslationTag\UpdateTranslationTagRequest;
use App\Http\Requests\TranslationTag\PopularTranslationTagRequest;
use App\Http\Resources\TranslationTag\TranslationTagResource;
use App\Models\TranslationTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Translation Tag API Controller
 *
 * Provides comprehensive CRUD operations for translation tags including:
 * - Paginated listing with search and filtering capabilities
 * - Create, read, update, and delete operations
 * - Advanced sorting and statistics
 * - Cache management for optimal performance
 *
 * All operations are secured with authentication middleware and include
 * proper validation, error handling, and API documentation.
 *
 * @package App\Http\Controllers\Api
 * @author Syed Asad
 * @version 1.0.0
 * @since 2024-01-15
 */
class TranslationTagController extends Controller
{
    public function index(IndexTranslationTagRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $includeInactive = $request->input('include_inactive', false);
        $withCounts = $request->input('with_counts', false);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        $query = TranslationTag::query();

        if (!$includeInactive) {
            $query->active();
        }

        if ($withCounts) {
            $query->withCount(['translations', 'activeTranslations']);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $tags = $query->orderBy($sortBy, $sortDirection)->paginate($perPage);

        return TranslationTagResource::collection($tags);
    }

    public function store(StoreTranslationTagRequest $request): JsonResponse
    {
        try {
            $tag = TranslationTag::create([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description'),
                'color' => $request->input('color', '#6366f1'),
                'is_active' => $request->input('is_active', true),
            ]);

            Cache::tags(['tags'])->flush();

            return response()->json([
                'message' => 'Translation tag created successfully.',
                'data' => new TranslationTagResource($tag),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create translation tag.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(TranslationTag $tag): JsonResponse
    {
        $tag->loadCount(['translations', 'activeTranslations']);

        return response()->json([
            'data' => new TranslationTagResource($tag),
        ]);
    }

    public function update(UpdateTranslationTagRequest $request, TranslationTag $tag): JsonResponse
    {
        try {
            $tag->update($request->validated());

            Cache::tags(['tags'])->flush();

            return response()->json([
                'message' => 'Translation tag updated successfully.',
                'data' => new TranslationTagResource($tag),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update translation tag.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(TranslationTag $tag): JsonResponse
    {
        try {
            if ($tag->hasTranslations()) {
                return response()->json([
                    'message' => 'Cannot delete tag that is used by translations. Please remove tag from translations first.',
                ], 422);
            }

            $tag->delete();

            Cache::tags(['tags'])->flush();

            return response()->json([
                'message' => 'Translation tag deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete translation tag.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function popular(PopularTranslationTagRequest $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $cacheKey = "tags.popular.{$limit}";

        $popularTags = Cache::remember($cacheKey, 3600, function () use ($limit) {
            return TranslationTag::getMostUsed($limit);
        });

        return response()->json([
            'data' => $popularTags,
            'meta' => [
                'limit' => $limit,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }
}
