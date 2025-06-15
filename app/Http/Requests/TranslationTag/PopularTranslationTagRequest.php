<?php

declare(strict_types=1);

namespace App\Http\Requests\TranslationTag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for popular translation tags
 *
 * Handles validation for retrieving popular tags with limit parameter.
 *
 * @author Syed Asad
 */
class PopularTranslationTagRequest extends FormRequest
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
            'limit' => 'nullable|integer|min:1|max:50',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'limit.integer' => 'Limit must be a number.',
            'limit.min' => 'Limit must be at least 1.',
            'limit.max' => 'Limit cannot exceed 50.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'limit' => min((int) $this->get('limit', 10), 50),
        ]);
    }
}
