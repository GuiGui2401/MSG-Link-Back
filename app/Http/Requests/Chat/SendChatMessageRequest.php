<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|min:1|max:5000',
            'reply_to_id' => 'nullable|exists:anonymous_messages,id',
            'image' => 'nullable|image|max:10240',
            'voice' => 'nullable|file|mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/wave,audio/vnd.wave,audio/m4a,audio/x-m4a,audio/mp4,audio/aac,audio/x-aac,audio/ogg,audio/webm|max:10240',
            'video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska|max:51200',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Le message ne peut pas être vide.',
            'content.max' => 'Le message ne peut pas dépasser 5000 caractères.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasContent = $this->input('content');
            $hasMedia = $this->hasFile('image') || $this->hasFile('voice') || $this->hasFile('video');
            if (!$hasContent && !$hasMedia) {
                $validator->errors()->add('content', 'Le message ne peut pas être vide.');
            }
        });
    }
}
