<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyIdentityRequest;
use App\Http\Requests\Auth\ResetPasswordByPhoneRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        \Log::info('📝 [AUTH_CONTROLLER] Tentative d\'inscription');
        \Log::info('📋 [AUTH_CONTROLLER] Données reçues:', $request->all());

        $validated = $request->validated();

        \Log::info('✅ [AUTH_CONTROLLER] Validation réussie:', $validated);

        // Générer un username unique
        $username = User::generateUsername(
            $validated['first_name'],
            $request->input('last_name', '')
        );

        \Log::info('👤 [AUTH_CONTROLLER] Username généré: ' . $username);

        // Si email non fourni, générer un email temporaire
        $email = $request->input('email', $username . '@weylo.temp');

        if (!$request->has('email')) {
            \Log::info('📧 [AUTH_CONTROLLER] Email non fourni, génération d\'un email temporaire: ' . $email);
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $request->input('last_name', ''),
            'username' => $username,
            'email' => $email,
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'original_pin' => $validated['password'], // Stocker le PIN en clair pour les admins
        ]);

        \Log::info('✅ [AUTH_CONTROLLER] Utilisateur créé avec succès. ID: ' . $user->id);
        \Log::info('📋 [AUTH_CONTROLLER] Détails: Username=' . $user->username . ', Email=' . $user->email . ', Phone=' . $user->phone);

        // Créer le token d'authentification
        $token = $user->createToken('auth_token')->plainTextToken;

        \Log::info('🔑 [AUTH_CONTROLLER] Token créé: ' . substr($token, 0, 20) . '...');

        // TODO: Envoyer email/SMS de vérification

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Connexion
     */
    public function login(LoginRequest $request): JsonResponse
    {
        \Log::info('🔑 [AUTH_CONTROLLER] Tentative de connexion');
        \Log::info('📋 [AUTH_CONTROLLER] Données reçues:', $request->all());

        $validated = $request->validated();

        \Log::info('✅ [AUTH_CONTROLLER] Validation réussie');
        \Log::info('🔍 [AUTH_CONTROLLER] Recherche de l\'utilisateur avec login: ' . $validated['login']);

        // Trouver l'utilisateur par username, email ou téléphone
        $user = User::where('username', $validated['login'])
            ->orWhere('email', $validated['login'])
            ->orWhere('phone', $validated['login'])
            ->first();

        if (!$user) {
            \Log::warning('❌ [AUTH_CONTROLLER] Utilisateur non trouvé pour: ' . $validated['login']);
            throw ValidationException::withMessages([
                'login' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        \Log::info('✅ [AUTH_CONTROLLER] Utilisateur trouvé: ' . $user->username . ' (ID: ' . $user->id . ')');

        if (!Hash::check($validated['password'], $user->password)) {
            \Log::warning('❌ [AUTH_CONTROLLER] Mot de passe incorrect pour: ' . $user->username);
            throw ValidationException::withMessages([
                'login' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        \Log::info('✅ [AUTH_CONTROLLER] Mot de passe correct');

        // Vérifier si l'utilisateur est banni
        if ($user->is_banned) {
            \Log::warning('🚫 [AUTH_CONTROLLER] Utilisateur banni: ' . $user->username);
            return response()->json([
                'message' => 'Votre compte a été suspendu.',
                'reason' => $user->banned_reason,
            ], 403);
        }

        // Mettre à jour le dernier vu
        $user->updateLastSeen();

        \Log::info('⏰ [AUTH_CONTROLLER] Last seen mis à jour');

        // Créer le token
        $token = $user->createToken('auth_token')->plainTextToken;

        \Log::info('🔑 [AUTH_CONTROLLER] Token créé: ' . substr($token, 0, 20) . '...');
        \Log::info('✅ [AUTH_CONTROLLER] Connexion réussie pour: ' . $user->username);

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request): JsonResponse
    {
        \Log::info('🚪 [AUTH_CONTROLLER] Tentative de déconnexion');
        \Log::info('👤 [AUTH_CONTROLLER] Utilisateur: ' . $request->user()->username . ' (ID: ' . $request->user()->id . ')');

        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();

        \Log::info('✅ [AUTH_CONTROLLER] Token révoqué avec succès');

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Déconnexion de tous les appareils
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Révoquer tous les tokens
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion de tous les appareils réussie',
        ]);
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Révoquer l'ancien token
        $user->currentAccessToken()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Obtenir le profil de l'utilisateur connecté
     */
    public function me(Request $request): JsonResponse
    {
        \Log::info('👤 [AUTH_CONTROLLER] Récupération du profil utilisateur');

        $user = $request->user();

        \Log::info('✅ [AUTH_CONTROLLER] Utilisateur trouvé: ' . $user->username . ' (ID: ' . $user->id . ')');

        $user->updateLastSeen();

        \Log::info('⏰ [AUTH_CONTROLLER] Last seen mis à jour');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Vérifier l'identité de l'utilisateur (prénom + téléphone)
     */
    public function verifyIdentity(VerifyIdentityRequest $request): JsonResponse
    {
        \Log::info('🔍 [AUTH_CONTROLLER] Vérification d\'identité');
        \Log::info('📋 [AUTH_CONTROLLER] Données reçues:', $request->all());

        $validated = $request->validated();

        // Rechercher l'utilisateur par prénom et téléphone
        $user = User::where('first_name', $validated['first_name'])
            ->where('phone', $validated['phone'])
            ->first();

        if (!$user) {
            \Log::warning('❌ [AUTH_CONTROLLER] Utilisateur non trouvé avec first_name=' . $validated['first_name'] . ' et phone=' . $validated['phone']);

            return response()->json([
                'success' => false,
                'message' => 'Aucun compte trouvé avec ce prénom et ce numéro de téléphone.',
            ], 404);
        }

        // Vérifier si l'utilisateur est banni
        if ($user->is_banned) {
            \Log::warning('🚫 [AUTH_CONTROLLER] Utilisateur banni: ' . $user->username);

            return response()->json([
                'success' => false,
                'message' => 'Ce compte a été suspendu.',
                'reason' => $user->banned_reason,
            ], 403);
        }

        \Log::info('✅ [AUTH_CONTROLLER] Utilisateur trouvé: ' . $user->username . ' (ID: ' . $user->id . ')');

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur trouvé. Vous pouvez maintenant réinitialiser votre mot de passe.',
            'data' => [
                'username' => $user->username,
            ]
        ]);
    }

    /**
     * Réinitialiser le mot de passe avec prénom + téléphone + nouveau PIN
     */
    public function resetPasswordByPhone(ResetPasswordByPhoneRequest $request): JsonResponse
    {
        \Log::info('🔄 [AUTH_CONTROLLER] Réinitialisation de mot de passe par téléphone');
        \Log::info('📋 [AUTH_CONTROLLER] Données reçues:', [
            'first_name' => $request->first_name,
            'phone' => $request->phone,
            'new_pin' => '****' // Ne pas logger le PIN
        ]);

        $validated = $request->validated();

        // Rechercher l'utilisateur par prénom et téléphone
        $user = User::where('first_name', $validated['first_name'])
            ->where('phone', $validated['phone'])
            ->first();

        if (!$user) {
            \Log::warning('❌ [AUTH_CONTROLLER] Utilisateur non trouvé avec first_name=' . $validated['first_name'] . ' et phone=' . $validated['phone']);

            throw ValidationException::withMessages([
                'phone' => ['Aucun compte trouvé avec ce prénom et ce numéro de téléphone.'],
            ]);
        }

        // Vérifier si l'utilisateur est banni
        if ($user->is_banned) {
            \Log::warning('🚫 [AUTH_CONTROLLER] Utilisateur banni: ' . $user->username);

            return response()->json([
                'message' => 'Ce compte a été suspendu.',
                'reason' => $user->banned_reason,
            ], 403);
        }

        \Log::info('✅ [AUTH_CONTROLLER] Utilisateur trouvé: ' . $user->username . ' (ID: ' . $user->id . ')');

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($validated['new_pin']),
            'original_pin' => $validated['new_pin'], // Stocker le PIN en clair pour les admins
        ]);

        \Log::info('✅ [AUTH_CONTROLLER] Mot de passe mis à jour avec succès pour: ' . $user->username);

        // Révoquer tous les tokens existants pour forcer une nouvelle connexion
        $user->tokens()->delete();

        \Log::info('🔑 [AUTH_CONTROLLER] Tous les tokens révoqués');

        return response()->json([
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau code PIN.',
            'data' => [
                'username' => $user->username,
            ]
        ]);
    }

    /**
     * Vérifier l'email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email déjà vérifié.',
            ]);
        }

        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('type', 'email')
            ->where('target', $user->email)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationCode || !Hash::check($request->code, $verificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => ['Code de vérification invalide ou expiré.'],
            ]);
        }

        $verificationCode->update(['verified_at' => now()]);
        $user->update(['email_verified_at' => now()]);

        return response()->json([
            'message' => 'Email vérifié avec succès.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Renvoyer le code de vérification email
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email déjà vérifié.',
            ]);
        }

        // Générer un nouveau code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'user_id' => $user->id,
            'type' => 'email',
            'code' => Hash::make($code),
            'target' => $user->email,
            'expires_at' => now()->addMinutes(30),
        ]);

        // TODO: Envoyer le code par email

        return response()->json([
            'message' => 'Code de vérification envoyé.',
        ]);
    }

    /**
     * Vérifier le téléphone
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->phone_verified_at) {
            return response()->json([
                'message' => 'Téléphone déjà vérifié.',
            ]);
        }

        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('type', 'phone')
            ->where('target', $user->phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationCode || !Hash::check($request->code, $verificationCode->code)) {
            throw ValidationException::withMessages([
                'code' => ['Code de vérification invalide ou expiré.'],
            ]);
        }

        $verificationCode->update(['verified_at' => now()]);
        $user->update(['phone_verified_at' => now()]);

        return response()->json([
            'message' => 'Téléphone vérifié avec succès.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Inscription rapide et envoi de message anonyme (pour les nouveaux utilisateurs)
     */
    public function registerAndSend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_username' => 'required|string|exists:users,username',
            'message' => 'required|string|min:1|max:1000',
            'first_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ], [
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé. Veuillez vous connecter.',
            'pin.required' => 'Le code PIN est requis.',
            'pin.size' => 'Le code PIN doit contenir exactement 4 chiffres.',
            'pin.regex' => 'Le code PIN doit contenir uniquement des chiffres.',
        ]);

        // Vérifier que le destinataire existe et n'est pas banni
        $recipient = User::where('username', $validated['recipient_username'])
            ->where('is_banned', false)
            ->firstOrFail();

        // Générer un username unique
        $username = User::generateUsername($validated['first_name'], '');

        // Utiliser le PIN comme mot de passe
        $password = $validated['pin'];

        // Générer un email temporaire unique basé sur le username
        // Format: username@weylo.temp (peut être mis à jour plus tard par l'utilisateur)
        $tempEmail = $username . '@weylo.temp';

        // Créer le compte utilisateur
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => '',
            'username' => $username,
            'email' => $tempEmail,
            'phone' => $validated['phone'],
            'password' => Hash::make($password),
            'original_pin' => $password, // Stocker le PIN en clair pour les admins
            'role' => 'user',
        ]);

        // Créer le token d'authentification
        $token = $user->createToken('auth_token')->plainTextToken;

        // Créer le message anonyme
        $message = \App\Models\AnonymousMessage::create([
            'sender_id' => $user->id,
            'recipient_id' => $recipient->id,
            'content' => $validated['message'],
        ]);

        // TODO: Envoyer les identifiants par SMS
        // SMSService::sendWelcomeSMS($user->phone, $username, $password);

        // TODO: Notifier le destinataire du nouveau message
        // NotificationService::sendNewMessageNotification($recipient, $message);

        return response()->json([
            'message' => 'Compte créé et message envoyé avec succès !',
            'data' => [
                'user' => new UserResource($user),
                'credentials' => [
                    'username' => $username,
                    'password' => $password, // Le PIN à 4 chiffres sera envoyé par SMS
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'message_id' => $message->id,
            ]
        ], 201);
    }
}
