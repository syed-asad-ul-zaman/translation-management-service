<?php

declare(strict_types=1);

namespace App\Http\Requests\Translation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Translation Request
 *
 * Handles validation for bulk operations on translations including delete, activate, deactivate, verify, and unverify.
 *
 * @author Syed Asad
 */
class BulkTranslationRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => 'required|in:delete,activate,deactivate,verify,unverify',
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:translations,id',
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'The action is required.',
            'action.in' => 'The action must be one of: delete, activate, deactivate, verify, unverify.',
            'ids.required' => 'The translation IDs are required.',
            'ids.array' => 'The translation IDs must be an array.',
            'ids.min' => 'At least one translation ID must be provided.',
            'ids.max' => 'No more than 100 translation IDs can be processed at once.',
            'ids.*.integer' => 'Each translation ID must be an integer.',
            'ids.*.exists' => 'One or more of the selected translations is invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ids' => 'translation IDs',
        ];
    }
}
