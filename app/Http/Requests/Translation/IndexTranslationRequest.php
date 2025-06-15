<?php

declare(strict_types=1);

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Index Translation Request
 *
 * Handles validation for translation listing operations with pagination, filtering, and sorting.
 *
 * @author Syed Asad
 */
class IndexTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'locale' => 'nullable|string|exists:locales,code',
            'tag' => 'nullable|string|exists:translation_tags,slug',
            'is_active' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:key,value,created_at,updated_at,verified_at,is_verified_sort',
            'sort_direction' => 'nullable|string|in:asc,desc',
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.min' => 'The per page value must be at least 1.',
            'per_page.max' => 'The per page value may not be greater than 100.',
            'search.max' => 'The search query may not be greater than 255 characters.',
            'locale.exists' => 'The selected locale is invalid.',
            'tag.exists' => 'The selected tag is invalid.',
            'sort_by.in' => 'Invalid sort field. Allowed values: key, value, created_at, updated_at, verified_at, is_verified_sort.',
            'sort_direction.in' => 'Sort direction must be either asc or desc.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'per_page' => 'items per page',
            'sort_by' => 'sort field',
            'sort_direction' => 'sort direction',
        ];
    }
}
