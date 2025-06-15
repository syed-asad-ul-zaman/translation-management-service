<?php

declare(strict_types=1);

namespace App\Http\Requests\Locale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class for updating locales
 *
 * Handles validation for updating existing locales with proper
 * uniqueness rules and business constraints.
 *
 * @author Syed Asad
 */
class UpdateLocaleRequest extends FormRequest
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
        $localeId = $this->route('locale')->id ?? $this->route('locale');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'size:2',
                'regex:/^[a-z]{2}$/',
                Rule::unique('locales', 'code')->ignore($localeId)
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100'
            ],
            'native_name' => [
                'sometimes',
                'required',
                'string',
                'max:100'
            ],
            'is_active' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean'
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

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active')
            ]);
        }

        if ($this->has('is_default')) {
            $this->merge([
                'is_default' => $this->boolean('is_default')
            ]);
        }
    }
}
