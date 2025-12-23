<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = LegalPage::orderBy('order', 'asc')->get();
        return view('admin.legal-pages.index', compact('pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.legal-pages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:legal_pages,slug',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        LegalPage::create($validated);

        return redirect()->route('admin.legal-pages.index')
            ->with('success', 'Page légale créée avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(LegalPage $legalPage)
    {
        return view('admin.legal-pages.show', compact('legalPage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LegalPage $legalPage)
    {
        return view('admin.legal-pages.edit', compact('legalPage'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LegalPage $legalPage)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:legal_pages,slug,' . $legalPage->id,
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? $legalPage->order;

        $legalPage->update($validated);

        return redirect()->route('admin.legal-pages.index')
            ->with('success', 'Page légale mise à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LegalPage $legalPage)
    {
        $legalPage->delete();

        return redirect()->route('admin.legal-pages.index')
            ->with('success', 'Page légale supprimée avec succès');
    }
}
