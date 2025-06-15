<?php

declare(strict_types=1);

namespace App\Http\Requests\Locale;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for storing locales
 *
 * Handles validation for creating new locales with proper
 * business rules and uniqueness constraints.
 *
 * @author Syed Asad
 */
class StoreLocaleRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[a-z]{2}$/',
                'unique:locales,code'
            ],
            'name' => [
                'required',
                'string',
                'max:100'
            ],
            'native_name' => [
                'required',
                'string',
                'max:100'
            ],
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Locale code is required.',
            'code.size' => 'Locale code must be exactly 2 characters.',
            'code.regex' => 'Locale code must contain only lowercase letters.',
            'code.unique' => 'A locale with this code already exists.',
            'name.required' => 'Locale name is required.',
            'native_name.required' => 'Native name is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtolower($this->code)
            ]);
        }

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_default' => $this->boolean('is_default', false)
        ]);
    }
}
