<?php

declare(strict_types=1);

namespace App\Http\Resources\TranslationTag;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Translation Tag API Resource
 *
 * Transforms translation tag data for consistent API responses
 *
 * @author Syed Asad
 */
class TranslationTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'is_active' => $this->is_active,

            'translation_count' => $this->when(
                isset($this->translations_count),
                $this->translations_count
            ),
            'active_translation_count' => $this->when(
                isset($this->active_translations_count),
                $this->active_translations_count
            ),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
