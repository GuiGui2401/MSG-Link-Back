<?php

namespace App\Http\Requests\Confession;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;

class ReportConfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reasons = implode(',', [
            Report::REASON_SPAM,
            Report::REASON_HARASSMENT,
            Report::REASON_HATE_SPEECH,
            Report::REASON_INAPPROPRIATE,
            Report::REASON_IMPERSONATION,
            Report::REASON_OTHER,
        ]);

        return [
            'reason' => "required|in:{$reasons}",
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'La raison du signalement est obligatoire.',
            'reason.in' => 'Raison invalide.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
        ];
    }
}
