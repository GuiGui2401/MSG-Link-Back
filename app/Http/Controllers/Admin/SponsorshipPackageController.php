<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SponsorshipPackage;
use Illuminate\Http\Request;

class SponsorshipPackageController extends Controller
{
    public function index()
    {
        $packages = SponsorshipPackage::query()->latest()->paginate(20);
        return view('admin.sponsorship-packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.sponsorship-packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reach_min' => 'required|integer|min:1',
            'reach_max' => 'nullable|integer|gte:reach_min',
            'price' => 'required|integer|min:0',
            'duration_days' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        SponsorshipPackage::create($validated);

        return redirect()->route('admin.sponsorship-packages.index')
            ->with('success', 'Package sponsoring créé avec succès');
    }

    public function edit(SponsorshipPackage $package)
    {
        return view('admin.sponsorship-packages.edit', compact('package'));
    }

    public function update(Request $request, SponsorshipPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reach_min' => 'required|integer|min:1',
            'reach_max' => 'nullable|integer|gte:reach_min',
            'price' => 'required|integer|min:0',
            'duration_days' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $package->update($validated);

        return redirect()->route('admin.sponsorship-packages.index')
            ->with('success', 'Package sponsoring mis à jour avec succès');
    }

    public function destroy(SponsorshipPackage $package)
    {
        $package->delete();

        return redirect()->route('admin.sponsorship-packages.index')
            ->with('success', 'Package sponsoring supprimé avec succès');
    }
}
