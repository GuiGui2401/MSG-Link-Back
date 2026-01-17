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
            'content' => 'nullable|string|max:2000',
            'type' => 'required|in:private,public',
            'recipient_username' => 'required_if:type,private|nullable|string|exists:users,username',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska|max:102400',
            'is_anonymous' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Au moins un contenu (texte, image ou vidéo) doit être fourni
            if (empty($this->content) && !$this->hasFile('image') && !$this->hasFile('video')) {
                $validator->errors()->add('content', 'Veuillez fournir du texte, une image ou une vidéo.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'content.max' => 'La confession ne peut pas dépasser 2000 caractères.',
            'type.required' => 'Le type de confession est obligatoire.',
            'type.in' => 'Le type doit être "private" ou "public".',
            'recipient_username.required_if' => 'Un destinataire est requis pour une confession privée.',
            'recipient_username.exists' => 'Le destinataire n\'existe pas.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne peut pas dépasser 10 Mo.',
            'video.mimetypes' => 'Le fichier doit être une vidéo (MP4, MOV, AVI, WebM).',
            'video.max' => 'La vidéo ne peut pas dépasser 100 Mo.',
        ];
    }
}
