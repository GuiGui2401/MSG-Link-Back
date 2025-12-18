<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => 'required|string', // Username, email ou téléphone
            'password' => 'required|string', // PIN ou mot de passe
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Le nom d\'utilisateur, email ou numéro de téléphone est obligatoire.',
            'password.required' => 'Le mot de passe ou PIN est obligatoire.',
        ];
    }
}
