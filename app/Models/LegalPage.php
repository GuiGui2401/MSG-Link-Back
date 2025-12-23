<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope pour récupérer uniquement les pages actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour récupérer les pages avec du contenu
     */
    public function scopeWithContent($query)
    {
        return $query->whereNotNull('content')->where('content', '!=', '');
    }

    /**
     * Scope pour ordonner par ordre d'affichage
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Récupérer les pages actives pour le footer (avec contenu)
     */
    public static function getActivePages()
    {
        return self::active()
            ->withContent()
            ->ordered()
            ->get(['id', 'slug', 'title', 'order']);
    }

    /**
     * Récupérer une page par son slug
     */
    public static function getBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }
}
