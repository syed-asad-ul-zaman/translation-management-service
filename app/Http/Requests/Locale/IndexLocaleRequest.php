<?php

declare(strict_types=1);

namespace App\Http\Requests\Locale;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for listing locales
 *
 * Handles validation for locale listing with filtering,
 * statistics, and pagination parameters.
 *
 * @author Syed Asad
 */
class IndexLocaleRequest extends FormRequest
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
            'include_inactive' => 'nullable|in:true,false,1,0',
            'with_stats' => 'nullable|in:true,false,1,0',
            'per_page' => 'nullable|integer|min:1|max:100',
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
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_inactive' => $this->convertToBoolean($this->include_inactive),
            'with_stats' => $this->convertToBoolean($this->with_stats),
        ]);
    }

    /**
     * Convert string boolean values to actual boolean
     */
    private function convertToBoolean(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return in_array($value, ['true', '1', 1, true], true);
    }
}
