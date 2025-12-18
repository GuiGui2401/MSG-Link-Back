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
            'content' => ['required', 'string', 'max:5000'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:anonymous_messages,id'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Le contenu du message est requis.',
            'content.max' => 'Le message ne peut pas dépasser 5000 caractères.',
            'reply_to_message_id.exists' => 'Le message auquel vous répondez n\'existe pas.',
        ];
    }
}
