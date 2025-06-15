<?php

declare(strict_types=1);

namespace App\Http\Requests\TranslationTag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for storing translation tags
 *
 * Handles validation for creating new translation tags
 * with proper business rules and constraints.
 *
 * @author Syed Asad
 */
class StoreTranslationTagRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:translation_tags,name'
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'unique:translation_tags,slug',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'
            ],
            'description' => 'nullable|string|max:255',
            'color' => [
                'nullable',
                'string',
                'regex:/^#[a-fA-F0-9]{6}$/'
            ],
            'is_active' => 'nullable|boolean'
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
        if (!$this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name)
            ]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'color' => $this->color ?: '#007bff'
        ]);
    }
}
