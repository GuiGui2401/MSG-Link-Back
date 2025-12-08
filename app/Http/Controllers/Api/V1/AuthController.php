<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
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
        $validated = $request->validated();

        // Générer un username unique
        $username = User::generateUsername($validated['first_name'], $validated['last_name']);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $username,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        // Créer le token d'authentification
        $token = $user->createToken('auth_token')->plainTextToken;

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
        $validated = $request->validated();

        // Trouver l'utilisateur par email ou téléphone
        $user = User::where('email', $validated['login'])
            ->orWhere('phone', $validated['login'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Vérifier si l'utilisateur est banni
        if ($user->is_banned) {
            return response()->json([
                'message' => 'Votre compte a été suspendu.',
                'reason' => $user->banned_reason,
            ], 403);
        }

        // Mettre à jour le dernier vu
        $user->updateLastSeen();

        // Créer le token
        $token = $user->createToken('auth_token')->plainTextToken;

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
        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();

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
        $user = $request->user();
        $user->updateLastSeen();

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            // Pour des raisons de sécurité, on renvoie toujours un succès
            return response()->json([
                'message' => 'Si cet email existe, vous recevrez un lien de réinitialisation.',
            ]);
        }

        // Générer un code de vérification
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'user_id' => $user->id,
            'type' => 'password_reset',
            'code' => Hash::make($code),
            'target' => $validated['email'],
            'expires_at' => now()->addMinutes(30),
        ]);

        // TODO: Envoyer le code par email

        return response()->json([
            'message' => 'Si cet email existe, vous recevrez un code de réinitialisation.',
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Utilisateur non trouvé.'],
            ]);
        }

        // Vérifier le code
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('type', 'password_reset')
            ->where('target', $validated['email'])
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationCode || !Hash::check($validated['code'], $verificationCode->code)) {
            // Incrémenter les tentatives
            if ($verificationCode) {
                $verificationCode->increment('attempts');
            }

            throw ValidationException::withMessages([
                'code' => ['Code de vérification invalide ou expiré.'],
            ]);
        }

        // Marquer le code comme utilisé
        $verificationCode->update(['verified_at' => now()]);

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Révoquer tous les tokens existants
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès.',
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
}
