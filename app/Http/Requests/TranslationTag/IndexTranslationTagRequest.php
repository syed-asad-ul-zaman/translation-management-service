<?php

declare(strict_types=1);

namespace App\Http\Requests\TranslationTag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for listing translation tags
 *
 * Handles validation for tag listing with filtering,
 * searching, and pagination parameters.
 *
 * @author Syed Asad
 */
class IndexTranslationTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'include_inactive' => 'nullable|boolean',
            'with_counts' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:name,created_at,updated_at',
            'sort_direction' => 'nullable|in:asc,desc',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Items per page must be a number.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
            'search.max' => 'Search term cannot exceed 255 characters.',
            'sort_by.in' => 'Invalid sort field. Use name, created_at, or updated_at.',
            'sort_direction.in' => 'Sort direction must be asc or desc.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_inactive' => $this->boolean('include_inactive', false),
            'with_counts' => $this->boolean('with_counts', false),
            'sort_by' => $this->get('sort_by', 'name'),
            'sort_direction' => $this->get('sort_direction', 'asc'),
        ]);
    }
}
