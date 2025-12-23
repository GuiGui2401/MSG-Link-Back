<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    /**
     * Display maintenance mode management page
     */
    public function index()
    {
        $maintenanceMode = (object) [
            'enabled' => Setting::get('maintenance_mode_enabled', false),
            'message' => Setting::get('maintenance_mode_message', 'Le site est actuellement en maintenance. Nous reviendrons bientôt !'),
            'estimated_end_time' => Setting::get('maintenance_mode_end_time', null),
        ];

        return view('admin.maintenance.index', compact('maintenanceMode'));
    }

    /**
     * Toggle maintenance mode on/off
     */
    public function toggle()
    {
        $currentStatus = Setting::get('maintenance_mode_enabled', false);
        $newStatus = !$currentStatus;

        Setting::set(
            'maintenance_mode_enabled',
            $newStatus ? '1' : '0',
            'boolean',
            'maintenance',
            'Active ou désactive le mode maintenance'
        );

        Setting::clearCache();

        return redirect()->route('admin.maintenance.index')
            ->with('success', $newStatus
                ? 'Mode maintenance activé avec succès !'
                : 'Mode maintenance désactivé avec succès !');
    }

    /**
     * Get maintenance mode status (API)
     */
    public function getStatus()
    {
        $isEnabled = Setting::get('maintenance_mode_enabled', false);
        $message = Setting::get('maintenance_mode_message', 'Le site est actuellement en maintenance. Nous reviendrons bientôt !');
        $estimatedEndTime = Setting::get('maintenance_mode_end_time', null);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $isEnabled,
                'message' => $message,
                'estimated_end_time' => $estimatedEndTime,
            ]
        ]);
    }

    /**
     * Update maintenance mode settings (Web)
     */
    public function updateWeb(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:500',
            'estimated_end_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Erreurs de validation');
        }

        try {
            // Update maintenance message
            Setting::set(
                'maintenance_mode_message',
                $request->message ?? 'Le site est actuellement en maintenance. Nous reviendrons bientôt !',
                'string',
                'maintenance',
                'Message affiché pendant la maintenance'
            );

            // Update estimated end time
            Setting::set(
                'maintenance_mode_end_time',
                $request->estimated_end_time ?? null,
                'string',
                'maintenance',
                'Heure de fin estimée de la maintenance'
            );

            // Clear settings cache
            Setting::clearCache();

            return redirect()->route('admin.maintenance.index')
                ->with('success', 'Paramètres de maintenance mis à jour avec succès !');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Update maintenance mode settings (API)
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'message' => 'nullable|string|max:500',
            'estimated_end_time' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update maintenance mode enabled status
            Setting::set(
                'maintenance_mode_enabled',
                $request->enabled ? '1' : '0',
                'boolean',
                'maintenance',
                'Active ou désactive le mode maintenance'
            );

            // Update maintenance message
            if ($request->has('message')) {
                Setting::set(
                    'maintenance_mode_message',
                    $request->message,
                    'string',
                    'maintenance',
                    'Message affiché pendant la maintenance'
                );
            }

            // Update estimated end time
            if ($request->has('estimated_end_time')) {
                Setting::set(
                    'maintenance_mode_end_time',
                    $request->estimated_end_time,
                    'string',
                    'maintenance',
                    'Heure de fin estimée de la maintenance'
                );
            }

            // Clear settings cache
            Setting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Mode maintenance mis à jour avec succès',
                'data' => [
                    'enabled' => $request->enabled,
                    'message' => $request->message,
                    'estimated_end_time' => $request->estimated_end_time,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }
}
