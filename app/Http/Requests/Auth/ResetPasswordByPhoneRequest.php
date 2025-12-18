<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordByPhoneRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'new_pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis.',
            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'phone.required' => 'Le numéro de téléphone est requis.',
            'phone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'new_pin.required' => 'Le nouveau code PIN est requis.',
            'new_pin.string' => 'Le nouveau code PIN doit être une chaîne de caractères.',
            'new_pin.size' => 'Le nouveau code PIN doit contenir exactement 4 chiffres.',
            'new_pin.regex' => 'Le nouveau code PIN doit contenir uniquement des chiffres.',
        ];
    }
}
