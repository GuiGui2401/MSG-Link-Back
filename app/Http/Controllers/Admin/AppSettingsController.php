<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppSettingsController extends Controller
{
    /**
     * Afficher la page des paramètres de l'application
     */
    public function index()
    {
        $settingsGroups = [
            'premium' => [
                'title' => 'Premium & Révélation',
                'icon' => 'crown',
                'settings' => Setting::where('group', 'premium')->get(),
            ],
            'groups' => [
                'title' => 'Groupes',
                'icon' => 'users',
                'settings' => Setting::where('group', 'groups')->get(),
            ],
            'promotions' => [
                'title' => 'Promotions / Boost',
                'icon' => 'rocket',
                'settings' => Setting::where('group', 'promotions')->get(),
            ],
            'wallet' => [
                'title' => 'Portefeuille',
                'icon' => 'wallet',
                'settings' => Setting::where('group', 'wallet')->get(),
            ],
            'gifts' => [
                'title' => 'Cadeaux',
                'icon' => 'gift',
                'settings' => Setting::where('group', 'gifts')->get(),
            ],
            'chat' => [
                'title' => 'Chat & Flames',
                'icon' => 'fire',
                'settings' => Setting::where('group', 'chat')->get(),
            ],
            'rate_limits' => [
                'title' => 'Limites de taux',
                'icon' => 'shield',
                'settings' => Setting::where('group', 'rate_limits')->get(),
            ],
            'moderation' => [
                'title' => 'Modération',
                'icon' => 'eye',
                'settings' => Setting::where('group', 'moderation')->get(),
            ],
            'security' => [
                'title' => 'Sécurité',
                'icon' => 'lock',
                'settings' => Setting::where('group', 'security')->get(),
            ],
            'monetization' => [
                'title' => 'Monétisation',
                'icon' => 'dollar-sign',
                'settings' => Setting::where('group', 'monetization')->get(),
            ],
            'general' => [
                'title' => 'Général',
                'icon' => 'settings',
                'settings' => Setting::where('group', 'general')->get(),
            ],
        ];

        return view('admin.settings.app-settings', compact('settingsGroups'));
    }

    /**
     * Mettre à jour un paramètre
     */
    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string|exists:settings,key',
            'value' => 'required',
        ]);

        $setting = Setting::where('key', $request->key)->first();

        if (!$setting) {
            return back()->with('error', 'Paramètre non trouvé.');
        }

        // Convertir la valeur selon le type
        $value = $request->value;
        if ($setting->type === 'boolean') {
            $value = $value === 'true' || $value === '1' || $value === true ? '1' : '0';
        }

        $setting->update(['value' => $value]);

        // Vider le cache pour ce paramètre
        Cache::forget("setting.{$setting->key}");

        return back()->with('success', "Paramètre '{$setting->description}' mis à jour avec succès.");
    }

    /**
     * Mettre à jour plusieurs paramètres en une fois
     */
    public function updateBatch(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'required',
        ]);

        $updated = 0;

        foreach ($request->settings as $settingData) {
            $setting = Setting::where('key', $settingData['key'])->first();

            if ($setting) {
                $value = $settingData['value'];
                if ($setting->type === 'boolean') {
                    $value = $value === 'true' || $value === '1' || $value === true ? '1' : '0';
                }

                $setting->update(['value' => $value]);
                Cache::forget("setting.{$setting->key}");
                $updated++;
            }
        }

        return back()->with('success', "{$updated} paramètres mis à jour avec succès.");
    }

    /**
     * API: Obtenir tous les paramètres publics pour le mobile
     */
    public function getPublicSettings()
    {
        $publicSettings = [
            // Premium
            'premium_monthly_price' => (float) Setting::get('premium_monthly_price', 450),
            'reveal_anonymous_price' => (float) Setting::get('reveal_anonymous_price', 500),
            'premium_enabled' => (bool) Setting::get('premium_enabled', true),

            // Groups
            'group_max_members_default' => (int) Setting::get('group_max_members_default', 50),
            'group_max_members_premium' => (int) Setting::get('group_max_members_premium', 200),

            // Wallet
            'deposit_min_amount' => (float) Setting::get('deposit_min_amount', 500),
            'wallet_min_withdrawal' => (float) Setting::get('wallet_min_withdrawal', 1000),

            // Promotions
            'promotion_min_budget' => (float) Setting::get('promotion_min_budget', 1000),
            'promotion_allow_images' => (bool) Setting::get('promotion_allow_images', true),

            // Gifts
            'gift_price_bronze' => (float) Setting::get('gift_price_bronze', 1000),
            'gift_price_silver' => (float) Setting::get('gift_price_silver', 5000),
            'gift_price_gold' => (float) Setting::get('gift_price_gold', 25000),
            'gift_price_diamond' => (float) Setting::get('gift_price_diamond', 50000),
        ];

        return response()->json([
            'success' => true,
            'settings' => $publicSettings,
        ]);
    }
}
