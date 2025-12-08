<?php

namespace App\Http\Requests\Wallet;

use App\Models\Withdrawal;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:' . Withdrawal::MIN_WITHDRAWAL_AMOUNT,
            'phone_number' => 'required|string|regex:/^[0-9]{9,15}$/',
            'provider' => 'required|in:mtn_momo,orange_money',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.integer' => 'Le montant doit être un nombre entier.',
            'amount.min' => 'Le montant minimum de retrait est de ' . number_format(Withdrawal::MIN_WITHDRAWAL_AMOUNT) . ' FCFA.',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire.',
            'phone_number.regex' => 'Le numéro de téléphone n\'est pas valide.',
            'provider.required' => 'Le mode de paiement est obligatoire.',
            'provider.in' => 'Le mode de paiement doit être MTN Mobile Money ou Orange Money.',
        ];
    }
}
