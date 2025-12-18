<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Service Configuration",
 *     description="API Endpoints pour la configuration et le test des services externes (WhatsApp, Nexah SMS, FreeMoPay)"
 * )
 */
class ServiceConfigController extends Controller
{
    /**
     * Display service configuration page
     */
    public function index()
    {
        $whatsappConfig = ServiceConfiguration::getWhatsAppConfig();
        $nexahConfig = ServiceConfiguration::getNexahConfig();
        $freemopayConfig = ServiceConfiguration::getFreeMoPayConfig();

        return view('admin.service-config.index', compact(
            'whatsappConfig',
            'nexahConfig',
            'freemopayConfig'
        ));
    }

    /**
     * Update WhatsApp configuration
     */
    public function updateWhatsApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_api_token' => 'required|string|min:10',
            'whatsapp_phone_number_id' => 'required|string|min:10',
            'whatsapp_api_version' => 'required|string',
            'whatsapp_template_name' => 'required|string|min:3',
            'whatsapp_language' => 'required|string|max:5',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Erreurs de validation: ' . implode(' | ', $errors));
        }

        try {
            $config = ServiceConfiguration::updateOrCreate(
                ['service_type' => 'whatsapp'],
                [
                    'whatsapp_api_token' => $request->whatsapp_api_token,
                    'whatsapp_phone_number_id' => $request->whatsapp_phone_number_id,
                    'whatsapp_api_version' => $request->whatsapp_api_version,
                    'whatsapp_template_name' => $request->whatsapp_template_name,
                    'whatsapp_language' => $request->whatsapp_language,
                    'is_active' => $request->has('is_active'),
                ]
            );

            // Clear cache
            ServiceConfiguration::clearCache('whatsapp');

            // Validate configuration
            $errors = $config->validateWhatsAppConfig();
            if (!empty($errors)) {
                return redirect()->back()
                    ->with('warning', 'Configuration sauvegardée avec des avertissements: ' . implode(', ', $errors));
            }

            return redirect()->back()
                ->with('success', 'Configuration WhatsApp mise à jour avec succès!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update Nexah SMS configuration
     */
    public function updateNexah(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nexah_base_url' => 'required|url',
            'nexah_send_endpoint' => 'required|string',
            'nexah_credits_endpoint' => 'required|string',
            'nexah_user' => 'required|string|min:3',
            'nexah_password' => 'required|string|min:3',
            'nexah_sender_id' => 'required|string|min:3|max:11',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Erreurs de validation: ' . implode(' | ', $errors));
        }

        try {
            $config = ServiceConfiguration::updateOrCreate(
                ['service_type' => 'nexah_sms'],
                [
                    'nexah_base_url' => $request->nexah_base_url,
                    'nexah_send_endpoint' => $request->nexah_send_endpoint,
                    'nexah_credits_endpoint' => $request->nexah_credits_endpoint,
                    'nexah_user' => $request->nexah_user,
                    'nexah_password' => $request->nexah_password,
                    'nexah_sender_id' => $request->nexah_sender_id,
                    'is_active' => $request->has('is_active'),
                ]
            );

            // Clear cache
            ServiceConfiguration::clearCache('nexah_sms');

            // Validate configuration
            $errors = $config->validateNexahConfig();
            if (!empty($errors)) {
                return redirect()->back()
                    ->with('warning', 'Configuration sauvegardée avec des avertissements: ' . implode(', ', $errors));
            }

            return redirect()->back()
                ->with('success', 'Configuration Nexah SMS mise à jour avec succès!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update FreeMoPay configuration
     */
    public function updateFreeMoPay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'freemopay_base_url' => 'required|url',
            'freemopay_app_key' => 'required|string|min:5',
            'freemopay_secret_key' => 'required|string|min:5',
            'freemopay_callback_url' => 'required|url',
            'freemopay_init_payment_timeout' => 'required|integer|min:1|max:30',
            'freemopay_status_check_timeout' => 'required|integer|min:1|max:30',
            'freemopay_token_timeout' => 'required|integer|min:1|max:30',
            'freemopay_token_cache_duration' => 'required|integer|min:60|max:3600',
            'freemopay_max_retries' => 'required|integer|min:0|max:5',
            'freemopay_retry_delay' => 'required|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Erreurs de validation: ' . implode(' | ', $errors));
        }

        try {
            $config = ServiceConfiguration::updateOrCreate(
                ['service_type' => 'freemopay'],
                [
                    'freemopay_base_url' => $request->freemopay_base_url,
                    'freemopay_app_key' => $request->freemopay_app_key,
                    'freemopay_secret_key' => $request->freemopay_secret_key,
                    'freemopay_callback_url' => $request->freemopay_callback_url,
                    'freemopay_init_payment_timeout' => $request->freemopay_init_payment_timeout,
                    'freemopay_status_check_timeout' => $request->freemopay_status_check_timeout,
                    'freemopay_token_timeout' => $request->freemopay_token_timeout,
                    'freemopay_token_cache_duration' => $request->freemopay_token_cache_duration,
                    'freemopay_max_retries' => $request->freemopay_max_retries,
                    'freemopay_retry_delay' => $request->freemopay_retry_delay,
                    'is_active' => $request->has('is_active'),
                ]
            );

            // Clear cache
            ServiceConfiguration::clearCache('freemopay');

            // Validate configuration
            $errors = $config->validateFreeMoPayConfig();
            if (!empty($errors)) {
                return redirect()->back()
                    ->with('warning', 'Configuration sauvegardée avec des avertissements: ' . implode(', ', $errors));
            }

            return redirect()->back()
                ->with('success', 'Configuration FreeMoPay mise à jour avec succès!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'default_notification_channel' => 'required|in:whatsapp,sms',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Veuillez corriger les erreurs.');
        }

        try {
            ServiceConfiguration::updateOrCreate(
                ['service_type' => 'notification_preferences'],
                [
                    'default_notification_channel' => $request->default_notification_channel,
                    'is_active' => true,
                ]
            );

            return redirect()->back()
                ->with('success', 'Préférences de notification mises à jour avec succès!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test WhatsApp connection
     */
    public function testWhatsApp()
    {
        $config = ServiceConfiguration::getWhatsAppConfig();

        if (!$config || !$config->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration WhatsApp invalide ou incomplète'
            ], 400);
        }

        $whatsappService = new \App\Services\Notifications\WhatsAppService();
        $result = $whatsappService->testConnection();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Test Nexah SMS connection
     */
    public function testNexah()
    {
        $config = ServiceConfiguration::getNexahConfig();

        if (!$config || !$config->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration Nexah invalide ou incomplète'
            ], 400);
        }

        $nexahService = new \App\Services\Notifications\NexahService();
        $result = $nexahService->testConnection();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Test FreeMoPay connection
     */
    public function testFreeMoPay()
    {
        $config = ServiceConfiguration::getFreeMoPayConfig();

        if (!$config || !$config->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration FreeMoPay invalide ou incomplète'
            ], 400);
        }

        // Note: You'll need to implement FreeMoPayService if you want to use this
        // $freemopayService = new \App\Services\Payment\FreeMoPayService();
        // $result = $freemopayService->testConnection();

        return response()->json([
            'success' => false,
            'message' => 'FreeMoPay service not yet implemented'
        ], 501);
    }

    /**
     * Send test WhatsApp message
     */
    public function sendTestWhatsApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides: ' . $validator->errors()->first()
            ], 400);
        }

        $config = ServiceConfiguration::getWhatsAppConfig();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune configuration WhatsApp trouvée. Veuillez d\'abord sauvegarder la configuration.'
            ], 400);
        }

        // Check what's missing in configuration
        $errors = $config->validateWhatsAppConfig();
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration incomplète. Champs manquants: ' . implode(', ', $errors),
                'errors' => $errors,
                'config_debug' => [
                    'has_token' => !empty($config->whatsapp_api_token),
                    'has_phone_id' => !empty($config->whatsapp_phone_number_id),
                    'has_template' => !empty($config->whatsapp_template_name),
                    'is_active' => $config->is_active
                ]
            ], 400);
        }

        try {
            $whatsappService = new \App\Services\Notifications\WhatsAppService();
            $result = $whatsappService->sendOtp(
                $request->input('phone'),
                $request->input('otp')
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test Nexah SMS
     */
    public function sendTestNexah(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides: ' . $validator->errors()->first()
            ], 400);
        }

        $config = ServiceConfiguration::getNexahConfig();

        if (!$config || !$config->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration Nexah invalide ou incomplète. Veuillez d\'abord configurer le service.'
            ], 400);
        }

        try {
            $nexahService = new \App\Services\Notifications\NexahService();
            $result = $nexahService->sendSms(
                $request->input('phone'),
                $request->input('message')
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all service configuration cache
     */
    public function clearCache()
    {
        try {
            ServiceConfiguration::clearCache();

            return redirect()->back()
                ->with('success', 'Cache des configurations nettoyé avec succès!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors du nettoyage du cache: ' . $e->getMessage());
        }
    }
}
