<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Locale\IndexLocaleRequest;
use App\Http\Requests\Locale\StoreLocaleRequest;
use App\Http\Requests\Locale\UpdateLocaleRequest;
use App\Http\Resources\Locale\LocaleResource;
use App\Models\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Locale API Controller
 *
 * Manages CRUD operations for locales with performance optimizations
 * Follows PSR-12 standards and SOLID principles
 *
 * @author Syed Asad
 */
class LocaleController extends Controller
{

    public function index(IndexLocaleRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        $includeInactive = $request->get('include_inactive', false);
        $withStats = $request->get('with_stats', false);

        $query = Locale::query();

        if (!$includeInactive) {
            $query->active();
        }

        if ($withStats) {
            $query->withCount(['translations', 'activeTranslations']);
        }

        $locales = $query->orderBy('name')->paginate($perPage);

        return LocaleResource::collection($locales);
    }

    public function store(StoreLocaleRequest $request): JsonResponse
    {

        try {
            $locale = Locale::create([
                'code' => strtolower($request->input('code')),
                'name' => $request->input('name'),
                'native_name' => $request->input('native_name'),
                'is_active' => $request->input('is_active', true),
            ]);

            Cache::tags(['locales'])->flush();

            return response()->json([
                'message' => 'Locale created successfully.',
                'data' => new LocaleResource($locale),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create locale.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function show(Locale $locale): JsonResponse
    {
        $locale->loadCount(['translations', 'activeTranslations']);

        return response()->json([
            'data' => new LocaleResource($locale),
        ]);
    }

    public function update(UpdateLocaleRequest $request, Locale $locale): JsonResponse
    {

        try {
            $oldCode = $locale->code;

            $locale->update($request->only([
                'code',
                'name',
                'native_name',
                'is_active'
            ]));

            Cache::tags(['locales', "locale:{$oldCode}"])->flush();
            if ($locale->code !== $oldCode) {
                Cache::tags(["locale:{$locale->code}"])->flush();
            }

            return response()->json([
                'message' => 'Locale updated successfully.',
                'data' => new LocaleResource($locale),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update locale.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy(Locale $locale): JsonResponse
    {
        try {
            if ($locale->hasTranslations()) {
                return response()->json([
                    'message' => 'Cannot delete locale with existing translations.',
                ], 400);
            }

            if ($locale->is_default) {
                return response()->json([
                    'message' => 'Cannot delete the default locale.',
                ], 400);
            }

            $localeCode = $locale->code;
            $locale->delete();

            Cache::tags(['locales', "locale:{$localeCode}"])->flush();

            return response()->json([
                'message' => 'Locale deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete locale.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
