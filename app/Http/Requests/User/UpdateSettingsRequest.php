<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notifications_enabled' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
            'push_notifications' => 'nullable|boolean',
            'dark_mode' => 'nullable|boolean',
            'language' => 'nullable|string|in:fr,en',
            'privacy' => 'nullable|array',
            'privacy.show_online_status' => 'nullable|boolean',
            'privacy.allow_messages_from_strangers' => 'nullable|boolean',
        ];
    }
}
