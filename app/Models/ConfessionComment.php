<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Encryptable;

class ConfessionComment extends Model
{
    use HasFactory, SoftDeletes, Encryptable;

    protected $fillable = [
        'confession_id',
        'author_id',
        'content',
        'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    /**
     * Champs à chiffrer
     */
    protected $encryptable = ['content'];

    // ==================== RELATIONS ====================

    /**
     * Confession commentée
     */
    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    /**
     * Auteur du commentaire
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Initiale de l'auteur
     */
    public function getAuthorInitialAttribute(): string
    {
        if ($this->is_anonymous) {
            return '?';
        }
        return $this->author->initial ?? '?';
    }

    /**
     * Nom de l'auteur (anonyme ou non)
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonyme';
        }
        return $this->author->first_name ?? 'Utilisateur';
    }
}
