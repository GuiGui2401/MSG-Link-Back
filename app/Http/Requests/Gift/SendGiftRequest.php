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
        // Vérifier si on est dans une conversation (route parameter présent)
        $conversation = $this->route('conversation');
        $isInConversation = $conversation !== null;

        return [
            'gift_id' => 'required|integer|exists:gifts,id',
            // Si on est dans une conversation, recipient_username n'est pas requis
            'recipient_username' => $isInConversation
                ? 'nullable|string|exists:users,username'
                : 'required|string|exists:users,username',
            'message' => 'nullable|string|max:200',
            'is_anonymous' => 'nullable|boolean',
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
