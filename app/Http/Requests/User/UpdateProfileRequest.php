<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $userId,
            'bio' => 'nullable|string|max:300',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.max' => 'Le prénom ne peut pas dépasser 100 caractères.',
            'last_name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'bio.max' => 'La bio ne peut pas dépasser 300 caractères.',
        ];
    }
}
