<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCategory;
use Illuminate\Http\Request;

class GiftCategoryController extends Controller
{
    public function index()
    {
        $categories = GiftCategory::withCount('gifts')->latest()->get();
        return view('admin.gift-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.gift-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        GiftCategory::create($validated);

        return redirect()->route('admin.gift-categories.index')
            ->with('success', 'Catégorie créée avec succès');
    }

    public function edit(GiftCategory $giftCategory)
    {
        return view('admin.gift-categories.edit', compact('giftCategory'));
    }

    public function update(Request $request, GiftCategory $giftCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $giftCategory->update($validated);

        return redirect()->route('admin.gift-categories.index')
            ->with('success', 'Catégorie mise à jour avec succès');
    }

    public function destroy(GiftCategory $giftCategory)
    {
        $giftCategory->delete();

        return redirect()->route('admin.gift-categories.index')
            ->with('success', 'Catégorie supprimée avec succès');
    }
}
