<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\GiftCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GiftManagementController extends Controller
{
    public function index()
    {
        $gifts = Gift::with('category')->latest()->paginate(20);
        return view('admin.gift-management.index', compact('gifts'));
    }

    public function create()
    {
        $categories = GiftCategory::where('is_active', true)->get();
        return view('admin.gift-management.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gift_category_id' => 'nullable|exists:gift_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|string|max:10',
            'background_color' => 'required|string|max:7',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Générer un slug unique
        $slug = Str::slug($validated['name']);
        $baseSlug = $slug;
        $counter = 1;

        while (Gift::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        Gift::create($validated);

        return redirect()->route('admin.gift-management.index')
            ->with('success', 'Cadeau créé avec succès');
    }

    public function edit(Gift $gift)
    {
        $categories = GiftCategory::where('is_active', true)->get();
        return view('admin.gift-management.edit', compact('gift', 'categories'));
    }

    public function update(Request $request, Gift $gift)
    {
        $validated = $request->validate([
            'gift_category_id' => 'nullable|exists:gift_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|string|max:10',
            'background_color' => 'required|string|max:7',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Générer un nouveau slug si le nom a changé
        if ($validated['name'] !== $gift->name) {
            $slug = Str::slug($validated['name']);
            $baseSlug = $slug;
            $counter = 1;

            while (Gift::where('slug', $slug)->where('id', '!=', $gift->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        $gift->update($validated);

        return redirect()->route('admin.gift-management.index')
            ->with('success', 'Cadeau mis à jour avec succès');
    }

    public function destroy(Gift $gift)
    {
        $gift->delete();

        return redirect()->route('admin.gift-management.index')
            ->with('success', 'Cadeau supprimé avec succès');
    }
}
