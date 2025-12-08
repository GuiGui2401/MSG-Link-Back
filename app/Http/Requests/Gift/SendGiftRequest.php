<?php

namespace App\Http\Requests\Gift;

use Illuminate\Foundation\Http\FormRequest;

class SendGiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gift_id' => 'required|integer|exists:gifts,id',
            'recipient_username' => 'required_without:conversation_id|nullable|string|exists:users,username',
            'message' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'gift_id.required' => 'Le cadeau est obligatoire.',
            'gift_id.exists' => 'Ce cadeau n\'existe pas.',
            'recipient_username.required_without' => 'Un destinataire est requis.',
            'recipient_username.exists' => 'Le destinataire n\'existe pas.',
            'message.max' => 'Le message ne peut pas dépasser 200 caractères.',
        ];
    }
}
