<?php

declare(strict_types=1);

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Export Keys Request
 *
 * Validates parameters for exporting specific translations by keys.
 *
 * @author Syed Asad
 */
class ExportKeysRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'keys' => 'required|array|min:1|max:100',
            'keys.*' => 'string|max:255',
            'locales' => 'nullable|array',
            'locales.*' => 'string|exists:locales,code',
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
            'keys.required' => 'At least one translation key is required.',
            'keys.array' => 'Keys must be provided as an array.',
            'keys.min' => 'At least one translation key is required.',
            'keys.max' => 'Maximum of 100 translation keys allowed.',
            'keys.*.string' => 'Each key must be a string.',
            'keys.*.max' => 'Each key must not exceed 255 characters.',
            'locales.array' => 'Locales must be provided as an array.',
            'locales.*.exists' => 'The selected locale does not exist.',
            'format.in' => 'The format must be either "flat" or "nested".',
            'include_metadata.boolean' => 'The include_metadata field must be true or false.',
        ];
    }

    /**
     * Get the validated translation keys.
     *
     * @return array<string>
     */
    public function getKeys(): array
    {
        return $this->input('keys', []);
    }

    /**
     * Get the validated locale codes.
     *
     * @return array<string>|null
     */
    public function getLocales(): ?array
    {
        return $this->input('locales');
    }

    /**
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
