<?php

declare(strict_types=1);

namespace App\Http\Resources\Translation;

use App\Http\Resources\Locale\LocaleResource;
use App\Http\Resources\TranslationTag\TranslationTagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Translation API Resource
 *
 * Transforms translation data for consistent API responses
 * Follows Laravel 12 resource patterns
 *
 * @author Syed Asad
 */
class TranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'is_active' => $this->is_active,
            'is_verified' => $this->isVerified(),
            'verified_at' => $this->verified_at?->toISOString(),
            'formatted_key' => $this->formatted_key,
            'short_value' => $this->short_value,

            'locale' => new LocaleResource($this->whenLoaded('locale')),
            'tags' => TranslationTagResource::collection($this->whenLoaded('tags')),
            'verifier' => $this->whenLoaded('verifier', function () {
                return [
                    'id' => $this->verifier->id,
                    'name' => $this->verifier->name,
                ];
            }),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'generated_at' => now()->toISOString(),
            ],
        ];
    }
}
