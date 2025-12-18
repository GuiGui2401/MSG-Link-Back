<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\WithdrawalRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Models\Payment;
use App\Services\Payment\CinetPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function __construct(
        private CinetPayService $cinetPayService
    ) {}

    /**
     * Obtenir le solde et les informations du wallet
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'wallet' => [
                'balance' => $user->wallet_balance,
                'formatted_balance' => $user->formatted_balance,
                'currency' => 'XAF',
            ],
            'stats' => [
                'total_earnings' => $user->total_earnings,
                'total_withdrawals' => $user->total_withdrawals,
                'pending_withdrawals' => Withdrawal::byUser($user->id)->pending()->sum('amount'),
            ],
        ]);
    }

    /**
     * Historique des transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WalletTransaction::byUser($user->id)
            ->orderBy('created_at', 'desc');

        // Filtres optionnels
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $transactions = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'transactions' => WalletTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Demander un retrait
     */
    public function withdraw(WithdrawalRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $amount = $validated['amount'];

        // Vérifier le solde
        if (!$user->hasEnoughBalance($amount)) {
            return response()->json([
                'message' => 'Solde insuffisant.',
                'current_balance' => $user->wallet_balance,
            ], 422);
        }

        // Vérifier le montant minimum
        if ($amount < Withdrawal::MIN_WITHDRAWAL_AMOUNT) {
            return response()->json([
                'message' => "Le montant minimum de retrait est de " . number_format(Withdrawal::MIN_WITHDRAWAL_AMOUNT) . " FCFA.",
            ], 422);
        }

        // Vérifier s'il n'y a pas de retrait en cours
        $pendingWithdrawal = Withdrawal::byUser($user->id)->pending()->first();
        if ($pendingWithdrawal) {
            return response()->json([
                'message' => 'Vous avez déjà une demande de retrait en cours.',
                'pending_withdrawal' => new WithdrawalResource($pendingWithdrawal),
            ], 422);
        }

        // Calculer les montants
        $amounts = Withdrawal::calculateAmounts($amount);

        // Créer la demande de retrait
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $amounts['amount'],
            'fee' => $amounts['fee'],
            'net_amount' => $amounts['net_amount'],
            'phone_number' => $validated['phone_number'],
            'provider' => $validated['provider'],
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Demande de retrait créée avec succès.',
            'withdrawal' => new WithdrawalResource($withdrawal),
        ], 201);
    }

    /**
     * Initier un dépôt vers le wallet
     */
    public function deposit(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validation
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'integer', 'min:100', 'max:10000000'],
            'provider' => ['nullable', 'string', 'in:cinetpay,ligosapp'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation échouée.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $amount = $request->amount;
        $provider = $request->provider ?? 'cinetpay';

        // Vérifier que le montant est un multiple de 5
        if ($amount % 5 !== 0) {
            return response()->json([
                'message' => 'Le montant doit être un multiple de 5 FCFA.',
            ], 422);
        }

        try {
            // Créer un enregistrement Payment en pending
            $payment = Payment::create([
                'user_id' => $user->id,
                'type' => Payment::TYPE_DEPOSIT,
                'provider' => $provider,
                'amount' => $amount,
                'currency' => 'XAF',
                'status' => Payment::STATUS_PENDING,
                'metadata' => [
                    'purpose' => 'wallet_deposit',
                ],
            ]);

            // Générer une référence unique avec préfixe DEPOSIT-
            $reference = 'DEPOSIT-' . $payment->id;
            $payment->update(['reference' => $reference]);

            // Initier le paiement via CinetPay
            $paymentResult = $this->cinetPayService->initiatePayment([
                'reference' => $reference,
                'amount' => $amount,
                'currency' => 'XAF',
                'description' => "Recharge de wallet - {$amount} FCFA",
                'user' => $user,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'type' => 'deposit',
                ],
            ]);

            if (!$paymentResult['success']) {
                $payment->markAsFailed('Échec de l\'initiation du paiement');
                return response()->json([
                    'message' => 'Impossible d\'initier le paiement.',
                ], 500);
            }

            return response()->json([
                'message' => 'Paiement initié avec succès.',
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ],
                'payment_url' => $paymentResult['payment_url'],
                'payment_token' => $paymentResult['payment_token'] ?? null,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'initiation du dépôt.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un dépôt
     */
    public function checkDepositStatus(Request $request, string $reference): JsonResponse
    {
        $user = $request->user();

        // Vérifier que la référence commence par DEPOSIT-
        if (!str_starts_with($reference, 'DEPOSIT-')) {
            return response()->json([
                'message' => 'Référence invalide.',
            ], 400);
        }

        // Récupérer le paiement
        $payment = Payment::where('reference', $reference)
            ->where('user_id', $user->id)
            ->where('type', Payment::TYPE_DEPOSIT)
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Paiement introuvable.',
            ], 404);
        }

        try {
            // Vérifier le statut auprès de CinetPay
            $statusResult = $this->cinetPayService->checkPaymentStatus($reference);

            // Mettre à jour le statut du paiement si nécessaire
            if ($statusResult['status'] === 'completed' && $payment->status !== Payment::STATUS_COMPLETED) {
                // Le paiement est confirmé, créditer le wallet
                $this->processDepositPayment($payment);
            } elseif ($statusResult['status'] === 'failed' && $payment->status !== Payment::STATUS_FAILED) {
                $payment->markAsFailed('Paiement refusé par CinetPay');
            }

            // Recharger le paiement
            $payment->refresh();

            return response()->json([
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'completed_at' => $payment->completed_at,
                ],
                'wallet_balance' => $user->fresh()->wallet_balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la vérification du statut.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traiter un paiement de dépôt confirmé
     */
    private function processDepositPayment(Payment $payment): void
    {
        if ($payment->status === Payment::STATUS_COMPLETED) {
            return; // Déjà traité
        }

        // Marquer le paiement comme complété
        $payment->markAsCompleted();

        // Créditer le wallet de l'utilisateur
        $user = $payment->user;
        $user->creditWallet(
            $payment->amount,
            "Dépôt sur le wallet - {$payment->reference}",
            $payment
        );
    }

    /**
     * Liste des demandes de retrait
     */
    public function withdrawals(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Withdrawal::byUser($user->id)
            ->orderBy('created_at', 'desc');

        // Filtre par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'withdrawals' => WithdrawalResource::collection($withdrawals),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }

    /**
     * Détail d'une demande de retrait
     */
    public function showWithdrawal(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $user = $request->user();

        if ($withdrawal->user_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        return response()->json([
            'withdrawal' => new WithdrawalResource($withdrawal),
        ]);
    }

    /**
     * Annuler une demande de retrait (si encore en attente)
     */
    public function cancelWithdrawal(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $user = $request->user();

        if ($withdrawal->user_id !== $user->id) {
            return response()->json([
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        if ($withdrawal->status !== Withdrawal::STATUS_PENDING) {
            return response()->json([
                'message' => 'Impossible d\'annuler cette demande de retrait.',
            ], 422);
        }

        $withdrawal->delete();

        return response()->json([
            'message' => 'Demande de retrait annulée.',
        ]);
    }

    /**
     * Statistiques du wallet
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revenus par période
        $last30Days = WalletTransaction::byUser($user->id)
            ->credits()
            ->recent(30)
            ->sum('amount');

        $last7Days = WalletTransaction::byUser($user->id)
            ->credits()
            ->recent(7)
            ->sum('amount');

        $today = WalletTransaction::byUser($user->id)
            ->credits()
            ->whereDate('created_at', today())
            ->sum('amount');

        return response()->json([
            'balance' => $user->wallet_balance,
            'earnings' => [
                'total' => $user->total_earnings,
                'last_30_days' => $last30Days,
                'last_7_days' => $last7Days,
                'today' => $today,
            ],
            'withdrawals' => [
                'total' => Withdrawal::byUser($user->id)->completed()->sum('net_amount'),
                'pending' => Withdrawal::byUser($user->id)->pending()->sum('amount'),
                'count' => Withdrawal::byUser($user->id)->completed()->count(),
            ],
            'transactions_count' => WalletTransaction::byUser($user->id)->count(),
        ]);
    }

    /**
     * Obtenir les méthodes de retrait disponibles
     */
    public function withdrawalMethods(): JsonResponse
    {
        return response()->json([
            'methods' => [
                [
                    'id' => Withdrawal::PROVIDER_MTN_MOMO,
                    'name' => 'MTN Mobile Money',
                    'icon' => 'mtn_momo',
                    'is_available' => true,
                ],
                [
                    'id' => Withdrawal::PROVIDER_ORANGE_MONEY,
                    'name' => 'Orange Money',
                    'icon' => 'orange_money',
                    'is_available' => true,
                ],
            ],
            'minimum_amount' => Withdrawal::MIN_WITHDRAWAL_AMOUNT,
            'fee' => Withdrawal::WITHDRAWAL_FEE,
            'currency' => 'XAF',
        ]);
    }
}
