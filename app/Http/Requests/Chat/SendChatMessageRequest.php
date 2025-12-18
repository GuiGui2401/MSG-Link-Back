<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:1000',
            'reply_to_id' => 'nullable|exists:anonymous_messages,id',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Le message ne peut pas être vide.',
            'content.max' => 'Le message ne peut pas dépasser 1000 caractères.',
        ];
    }
}
