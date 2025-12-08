<?php

namespace App\Http\Requests\Confession;

use Illuminate\Foundation\Http\FormRequest;

class CreateConfessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:10|max:2000',
            'type' => 'required|in:private,public',
            'recipient_username' => 'required_if:type,private|nullable|string|exists:users,username',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Le contenu de la confession est obligatoire.',
            'content.min' => 'La confession doit contenir au moins 10 caractères.',
            'content.max' => 'La confession ne peut pas dépasser 2000 caractères.',
            'type.required' => 'Le type de confession est obligatoire.',
            'type.in' => 'Le type doit être "private" ou "public".',
            'recipient_username.required_if' => 'Un destinataire est requis pour une confession privée.',
            'recipient_username.exists' => 'Le destinataire n\'existe pas.',
        ];
    }
}
