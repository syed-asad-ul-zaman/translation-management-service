<?php

declare(strict_types=1);

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Export Tags Request
 *
 * Validates parameters for exporting translations by tags.
 *
 * @author Syed Asad
 */
class ExportTagsRequest extends FormRequest
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
            'locales.array' => 'Locales must be provided as an array.',
            'locales.*.exists' => 'The selected locale does not exist.',
            'format.in' => 'The format must be either "flat" or "nested".',
            'include_metadata.boolean' => 'The include_metadata field must be true or false.',
        ];
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
