<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    /**
     * Récupérer toutes les pages légales actives (pour le footer)
     */
    public function index()
    {
        try {
            $pages = LegalPage::getActivePages();

            return response()->json([
                'success' => true,
                'pages' => $pages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des pages légales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer une page légale par son slug
     */
    public function show($slug)
    {
        try {
            $page = LegalPage::getBySlug($slug);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page non trouvée'
                ], 404);
            }

            // Vérifier que la page est active et a du contenu
            if (!$page->is_active || empty($page->content)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page non disponible'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'page' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $page->title,
                    'content' => $page->content,
                    'updated_at' => $page->updated_at->format('d/m/Y')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la page',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
