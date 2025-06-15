<?php

declare(strict_types=1);

namespace App\Http\Requests\TranslationTag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class for updating translation tags
 *
 * Handles validation for updating existing translation tags
 * with proper uniqueness rules and business constraints.
 *
 * @author Syed Asad
 */
class UpdateTranslationTagRequest extends FormRequest
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
        $tagId = $this->route('tag')->id ?? $this->route('tag');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('translation_tags', 'name')->ignore($tagId)
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('translation_tags', 'slug')->ignore($tagId),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'
            ],
            'description' => 'sometimes|nullable|string|max:255',
            'color' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^#[a-fA-F0-9]{6}$/'
            ],
            'is_active' => 'sometimes|boolean'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tag name is required.',
            'name.unique' => 'A tag with this name already exists.',
            'slug.unique' => 'A tag with this slug already exists.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733).'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name') && !$this->has('slug')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name)
            ]);
        }

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active')
            ]);
        }
    }
}
