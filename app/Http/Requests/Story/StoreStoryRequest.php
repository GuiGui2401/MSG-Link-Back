<?php

namespace App\Http\Requests\Story;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string', // Règle assouplie
            'media' => 'nullable|file|max:51200',
            'content' => 'nullable|string|max:500',
            'background_color' => 'nullable|string|max:7',
            'duration' => 'nullable|integer|min:3|max:60',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $content = $this->input('content');

            // Validation manuelle de 'type'
            if (empty($type)) {
                $validator->errors()->add('type', 'Le type de story est obligatoire.');
            } elseif (!in_array($type, ['image', 'video', 'text'])) {
                $validator->errors()->add('type', 'Le type doit être "image", "video", ou "text".');
            }

            // Validation manuelle de 'media'
            if (in_array($type, ['image', 'video']) && !$this->hasFile('media')) {
                $validator->errors()->add('media', 'Un fichier media est requis pour ce type de story.');
            }

            // Validation manuelle de 'content'
            if ($type === 'text' && empty($content)) {
                $validator->errors()->add('content', 'Un contenu est requis pour les stories de type texte.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de story est obligatoire.',
            'media.required_if' => 'Un fichier media est requis pour ce type de story.',
            'content.required_if' => 'Un contenu est requis pour les stories de type texte.',
            'media.max' => 'La vidéo ne peut pas dépasser 50 Mo.',
            'content.max' => 'Le texte ne peut pas dépasser 500 caractères.',
        ];
    }
}
