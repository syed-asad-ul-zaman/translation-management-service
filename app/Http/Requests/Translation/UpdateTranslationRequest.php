<?php

declare(strict_types=1);

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Translation Request
 *
 * Validates data for updating existing translations
 * Follows security best practices and Laravel 12 validation features
 *
 * @author Syed Asad
 */
class UpdateTranslationRequest extends FormRequest
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
        $translationId = $this->route('translation');

        return [
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('translations')->where(function ($query) {
                    return $query->where('locale_id', $this->input('locale_id'));
                })->ignore($translationId),
            ],
            'value' => [
                'sometimes',
                'required',
                'string',
                'max:65535',
            ],
            'locale_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:locales,id',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'metadata' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'metadata.*' => [
                'string',
                'max:500',
            ],
            'tag_ids' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'tag_ids.*' => [
                'integer',
                'exists:translation_tags,id',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'key.required' => 'Translation key is required.',
            'key.regex' => 'Translation key can only contain letters, numbers, dots, hyphens, and underscores.',
            'key.unique' => 'A translation with this key already exists for the selected locale.',
            'value.required' => 'Translation value is required.',
            'value.max' => 'Translation value cannot exceed 65,535 characters.',
            'locale_id.required' => 'Locale is required.',
            'locale_id.exists' => 'The selected locale does not exist.',
            'tag_ids.*.exists' => 'One or more selected tags do not exist.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'locale_id' => 'locale',
            'tag_ids' => 'tags',
            'tag_ids.*' => 'tag',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('key')) {
            $this->merge([
                'key' => strtolower(trim($this->input('key'))),
            ]);
        }
    }
}
