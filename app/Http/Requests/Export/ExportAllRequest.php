<?php

declare(strict_types=1);

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Export All Request
 *
 * Validates parameters for exporting all translations grouped by locale.
 *
 * @author Syed Asad
 */
class ExportAllRequest extends FormRequest
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
            'tags' => 'nullable|string',
            'include_metadata' => 'nullable|boolean',
            'format' => 'nullable|in:flat,nested',
            'active_only' => 'nullable|boolean',
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
            'format.in' => 'The format must be either "flat" or "nested".',
            'include_metadata.boolean' => 'The include_metadata field must be true or false.',
            'active_only.boolean' => 'The active_only field must be true or false.',
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

    /**
     * Check if only active translations should be exported.
     *
     * @return bool
     */
    public function shouldExportActiveOnly(): bool
    {
        return (bool) $this->input('active_only', true);
    }
}
