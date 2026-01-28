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
            'type' => 'nullable|string', // Règle assouplie
            'recipient_username' => 'nullable|string', // Règle assouplie
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'video' => 'nullable|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska|max:102400',
            'is_anonymous' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $content = $this->input('content');
            $recipient = $this->input('recipient_username');

            // Au moins un contenu (texte, image ou vidéo) doit être fourni
            if (empty($content) && !$this->hasFile('image') && !$this->hasFile('video')) {
                $validator->errors()->add('content', 'Veuillez fournir du texte, une image ou une vidéo.');
            }

            // Validation manuelle de 'type'
            if (empty($type)) {
                $validator->errors()->add('type', 'Le type de confession est obligatoire.');
            } elseif (!in_array($type, ['private', 'public'])) {
                $validator->errors()->add('type', 'Le type doit être "private" ou "public".');
            }

            // Validation manuelle de 'recipient_username' si le type est privé
            if ($type === 'private') {
                if (empty($recipient)) {
                    $validator->errors()->add('recipient_username', 'Un destinataire est requis pour une confession privée.');
                } else {
                    // Vérification manuelle de l'existence de l'utilisateur
                    if (!\App\Models\User::where('username', $recipient)->exists()) {
                        $validator->errors()->add('recipient_username', 'Le destinataire n\'existe pas.');
                    }
                }
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
