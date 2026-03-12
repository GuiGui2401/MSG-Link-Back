<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SponsorshipPackageResource;
use App\Models\SponsorshipPackage;
use Illuminate\Http\JsonResponse;

class SponsorshipPackageController extends Controller
{
    /**
     * Liste des packages sponsoring actifs (catalogue)
     */
    public function index(): JsonResponse
    {
        $packages = SponsorshipPackage::query()
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'packages' => SponsorshipPackageResource::collection($packages),
            'currency' => 'XAF',
        ]);
    }
}

