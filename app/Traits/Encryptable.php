<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * Boot du trait
     */
    public static function bootEncryptable(): void
    {
        // Chiffrer automatiquement avant la sauvegarde
        static::saving(function ($model) {
            $model->encryptAttributes();
        });

        // Déchiffrer automatiquement après la récupération
        static::retrieved(function ($model) {
            $model->decryptAttributes();
        });
    }

    /**
     * Obtenir les attributs à chiffrer
     */
    protected function getEncryptableAttributes(): array
    {
        return $this->encryptable ?? [];
    }

    /**
     * Chiffrer les attributs
     */
    protected function encryptAttributes(): void
    {
        foreach ($this->getEncryptableAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                // Vérifier si déjà chiffré (pour éviter double chiffrement)
                if (!$this->isEncrypted($this->attributes[$attribute])) {
                    $this->attributes[$attribute] = Crypt::encryptString($this->attributes[$attribute]);
                }
            }
        }
    }

    /**
     * Déchiffrer les attributs
     */
    protected function decryptAttributes(): void
    {
        foreach ($this->getEncryptableAttributes() as $attribute) {
            if (isset($this->attributes[$attribute]) && !empty($this->attributes[$attribute])) {
                try {
                    if ($this->isEncrypted($this->attributes[$attribute])) {
                        $this->attributes[$attribute] = Crypt::decryptString($this->attributes[$attribute]);
                    }
                } catch (\Exception $e) {
                    // Si le déchiffrement échoue, garder la valeur telle quelle
                    // Cela peut arriver pour d'anciennes données non chiffrées
                }
            }
        }
    }

    /**
     * Vérifier si une valeur est chiffrée
     */
    protected function isEncrypted(string $value): bool
    {
        // Les valeurs chiffrées par Laravel commencent par "eyJ"
        return str_starts_with($value, 'eyJ');
    }

    /**
     * Obtenir un attribut déchiffré (utile pour les requêtes)
     */
    public function getDecryptedAttribute(string $attribute): ?string
    {
        $value = $this->attributes[$attribute] ?? null;

        if (!$value) {
            return null;
        }

        try {
            if ($this->isEncrypted($value)) {
                return Crypt::decryptString($value);
            }
            return $value;
        } catch (\Exception $e) {
            return $value;
        }
    }
}
