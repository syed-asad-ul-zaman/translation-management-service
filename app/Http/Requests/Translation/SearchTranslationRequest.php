<?php

declare(strict_types=1);

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Search Translation Request
 *
 * Handles validation for translation search operations with filtering capabilities.
 *
 * @author Syed Asad
 */
class SearchTranslationRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => 'required|string|min:2|max:255',
            'locale' => 'nullable|string|exists:locales,code',
            'tag' => 'nullable|string|exists:translation_tags,slug',
            'per_page' => 'nullable|integer|min:1|max:100',
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
            'q.required' => 'The search query is required.',
            'q.min' => 'The search query must be at least 2 characters.',
            'q.max' => 'The search query may not be greater than 255 characters.',
            'locale.exists' => 'The selected locale is invalid.',
            'tag.exists' => 'The selected tag is invalid.',
            'per_page.min' => 'The per page value must be at least 1.',
            'per_page.max' => 'The per page value may not be greater than 100.',
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
            'q' => 'search query',
            'per_page' => 'items per page',
        ];
    }
}
