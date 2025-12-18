<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class ReportMessageRequest extends FormRequest
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
            'reason' => ['required', 'string', 'in:spam,harassment,inappropriate,other'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'La raison du signalement est requise.',
            'reason.in' => 'Raison de signalement invalide.',
        ];
    }
}
