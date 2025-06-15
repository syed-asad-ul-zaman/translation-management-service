<?php

declare(strict_types=1);

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Export Locale Request
 *
 * Validates parameters for exporting translations for a specific locale.
 *
 * @author Syed Asad
 */
class ExportLocaleRequest extends FormRequest
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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'locale' => $this->route('locale'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => [
                'required',
                'string',
                'regex:/^[a-z]{2,3}$/',
                Rule::exists('locales', 'code')->where('is_active', true)
            ],
            'tags' => 'nullable|string',
            'include_metadata' => 'nullable|boolean',
            'format' => 'nullable|in:flat,nested',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'locale.required' => 'The locale parameter is required.',
            'locale.regex' => 'The locale must be a valid 2-3 character language code.',
            'locale.exists' => 'The specified locale does not exist or is not active.',
            'format.in' => 'The format must be either "flat" or "nested".',
            'include_metadata.boolean' => 'The include_metadata field must be true or false.',
        ];
    }

    /**
     * Get the validated tags as an array.
     *
     * @return array<string>
     */
    public function getTagsArray(): array
    {
        $tags = $this->input('tags');

        if (empty($tags)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $tags)));
    }    /**
     * Get the export format with default value.
     *
     * @return string
     */
    public function getExportFormat(): string
    {
        return $this->input('format', 'flat');
    }

    /**
     * Check if metadata should be included.
     *
     * @return bool
     */
    public function shouldIncludeMetadata(): bool
    {
        return (bool) $this->input('include_metadata', false);
    }
}
