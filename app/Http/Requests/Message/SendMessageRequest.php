<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'content' => ['nullable', 'string', 'max:5000'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:anonymous_messages,id'],
            'media_type' => ['nullable', 'in:none,audio,image'],
            'media' => ['nullable', 'file', 'max:20480'], // 20MB max
            'voice_type' => ['nullable', 'in:normal,robot,alien,mystery,chipmunk'],
            'gift_id' => ['nullable', 'integer', 'exists:gifts,id'],
            'gift_message' => ['nullable', 'string', 'max:500'],
            'reveal_identity_with_gift' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'content.max' => 'Le message ne peut pas dépasser 5000 caractères.',
            'reply_to_message_id.exists' => 'Le message auquel vous répondez n\'existe pas.',
            'media_type.in' => 'Le type de média doit être none, audio ou image.',
            'media.file' => 'Le fichier média est invalide.',
            'media.max' => 'Le fichier média ne peut pas dépasser 20MB.',
            'voice_type.in' => 'Le type de voix est invalide.',
            'gift_id.exists' => 'Le cadeau sélectionné n\'existe pas.',
            'gift_message.max' => 'Le message du cadeau ne peut pas dépasser 500 caractères.',
        ];
    }
}
