<?php

declare(strict_types=1);

namespace App\Http\Resources\Locale;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Locale API Resource
 *
 * Transforms locale data for consistent API responses
 *
 * @author Syed Asad
 */
class LocaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->native_name,
            'display_name' => $this->display_name,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'translations_count' => $this->when(
                isset($this->translations_count),
                $this->translations_count
            ),
            'active_translations_count' => $this->when(
                isset($this->active_translations_count),
                $this->active_translations_count
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
