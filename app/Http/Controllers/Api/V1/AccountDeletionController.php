<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountDeletionController extends Controller
{
    /**
     * Soumettre une demande de suppression de compte
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'username' => 'required|string|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'utilisateur existe
        $user = User::where('email', $request->email)
            ->where('username', $request->username)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte ne correspond à ces informations. Veuillez vérifier votre email et nom d\'utilisateur.'
            ], 404);
        }

        // Vérifier s'il y a déjà une demande en attente pour cet utilisateur
        $existingRequest = AccountDeletionRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà une demande de suppression en cours. Veuillez patienter ou contactez le support.'
            ], 409);
        }

        // Créer la demande de suppression
        $deletionRequest = AccountDeletionRequest::create([
            'user_id' => $user->id,
            'email' => $request->email,
            'username' => $request->username,
            'reason' => $request->reason,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // TODO: Envoyer un email de confirmation à l'utilisateur
        // Mail::to($user->email)->send(new AccountDeletionRequestMail($deletionRequest));

        // TODO: Notifier les administrateurs
        // Notification::send(User::admins()->get(), new AccountDeletionRequestNotification($deletionRequest));

        return response()->json([
            'success' => true,
            'message' => 'Votre demande de suppression a été enregistrée avec succès. Vous recevrez un email de confirmation sous 48 heures.',
            'data' => [
                'request_id' => $deletionRequest->id,
                'status' => $deletionRequest->status,
                'created_at' => $deletionRequest->created_at->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }

    /**
     * Annuler une demande de suppression de compte
     *
     * @param Request $request
     * @param string $requestId
     * @return JsonResponse
     */
    public function cancel(Request $request, string $requestId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $deletionRequest = AccountDeletionRequest::where('id', $requestId)
            ->where('email', $request->email)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if (!$deletionRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de suppression non trouvée ou déjà traitée.'
            ], 404);
        }

        $deletionRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // TODO: Envoyer un email de confirmation d'annulation
        // Mail::to($deletionRequest->email)->send(new AccountDeletionCancelledMail($deletionRequest));

        return response()->json([
            'success' => true,
            'message' => 'Votre demande de suppression a été annulée avec succès.',
        ]);
    }

    /**
     * Vérifier le statut d'une demande de suppression
     *
     * @param Request $request
     * @param string $requestId
     * @return JsonResponse
     */
    public function status(Request $request, string $requestId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        $deletionRequest = AccountDeletionRequest::where('id', $requestId)
            ->where('email', $request->email)
            ->first();

        if (!$deletionRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de suppression non trouvée.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'request_id' => $deletionRequest->id,
                'status' => $deletionRequest->status,
                'reason' => $deletionRequest->reason,
                'created_at' => $deletionRequest->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $deletionRequest->updated_at->format('Y-m-d H:i:s'),
                'scheduled_deletion_date' => $deletionRequest->scheduled_deletion_date
                    ? $deletionRequest->scheduled_deletion_date->format('Y-m-d H:i:s')
                    : null,
            ]
        ]);
    }
}
