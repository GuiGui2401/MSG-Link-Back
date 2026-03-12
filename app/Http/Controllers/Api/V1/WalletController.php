<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\WithdrawalRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Services\Payment\FreemopayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    private FreemopayService $freemopayService;

    public function __construct()
    {
        $this->freemopayService = new FreemopayService();
    }

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
     * Historique des transactions (wallet_transactions + transactions)
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        // 1. Récupérer les wallet_transactions (deposits, credits, debits)
        $walletTransactions = WalletTransaction::byUser($user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($t) {
                return [
                    'id' => 'wt_' . $t->id,
                    'type' => $t->type,
                    'amount' => (float) $t->amount,
                    'description' => $t->description,
                    'status' => 'completed', // Les wallet_transactions sont toujours completed
                    'created_at' => $t->created_at,
                    'reference' => $t->reference,
                    'meta' => null,
                ];
            })
            ->toArray();

        // 2. Récupérer les transactions (withdrawals, deposits via CinetPay)
        $regularTransactions = \App\Models\Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($t) {
                return [
                    'id' => 't_' . $t->id,
                    'type' => $t->type,
                    'amount' => (float) $t->amount,
                    'description' => $t->description,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                    'reference' => null,
                    'meta' => $t->meta ? json_decode($t->meta, true) : null,
                ];
            })
            ->toArray();

        // 3. Fusionner et trier par date
        $allTransactions = collect(array_merge($walletTransactions, $regularTransactions))
            ->sortByDesc('created_at')
            ->values();

        // 4. Filtres optionnels
        if ($request->has('type')) {
            $type = (string) $request->type;

            // Mobile UI uses "deposit"/"withdrawal" filters, but our merged history can contain:
            // - wallet_transactions: credit/debit
            // - transactions: deposit/withdrawal (and others)
            // So we support grouping for a consistent UX.
            $depositTypes = ['credit', 'deposit'];
            $withdrawalTypes = ['debit', 'withdrawal'];

            if ($type === 'deposit') {
                $allTransactions = $allTransactions->filter(fn($t) => in_array($t['type'], $depositTypes, true))
                    ->values();
            } elseif ($type === 'withdrawal') {
                $allTransactions = $allTransactions->filter(fn($t) => in_array($t['type'], $withdrawalTypes, true))
                    ->values();
            } else {
                $allTransactions = $allTransactions->filter(fn($t) => $t['type'] === $type)
                    ->values();
            }
        }

        if ($request->has('from')) {
            $from = \Carbon\Carbon::parse($request->from)->startOfDay();
            $allTransactions = $allTransactions->filter(function($t) use ($from) {
                return \Carbon\Carbon::parse($t['created_at'])->gte($from);
            })->values();
        }

        if ($request->has('to')) {
            $to = \Carbon\Carbon::parse($request->to)->endOfDay();
            $allTransactions = $allTransactions->filter(function($t) use ($to) {
                return \Carbon\Carbon::parse($t['created_at'])->lte($to);
            })->values();
        }

        // 5. Pagination manuelle
        $total = $allTransactions->count();
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedTransactions = $allTransactions->slice($offset, $perPage)->values();

        return response()->json([
            'transactions' => $paginatedTransactions,
            'meta' => [
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => (int) $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Demander un retrait via FreeMoPay
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

        // Vérifier le montant minimum (depuis les settings)
        $minimumAmount = Withdrawal::getMinimumAmount();
        if ($amount < $minimumAmount) {
            return response()->json([
                'message' => "Le montant minimum de retrait est de " . number_format($minimumAmount) . " FCFA.",
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

        // Générer un ID externe unique
        $externalId = $this->generateWithdrawalExternalId();

        Log::info('💸 [WALLET] Initiating withdrawal via FreeMoPay', [
            'user_id' => $user->id,
            'amount' => $amount,
            'phone' => $validated['phone_number'],
            'external_id' => $externalId,
        ]);

        try {
            // Appeler l'API FreeMoPay pour initier le retrait
            $freemopayResult = $this->freemopayService->initWithdrawal(
                $validated['phone_number'],
                $amount,
                $externalId
            );

            $reference = $freemopayResult['reference'];

            Log::info('✅ [WALLET] FreeMoPay withdrawal initiated', [
                'external_id' => $externalId,
                'reference' => $reference,
                'status' => $freemopayResult['status'],
            ]);

            // Créer la demande de retrait avec la référence FreeMoPay
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'amount' => $amounts['amount'],
                'fee' => $amounts['fee'],
                'net_amount' => $amounts['net_amount'],
                'phone_number' => $validated['phone_number'],
                'provider' => $validated['provider'],
                'status' => Withdrawal::STATUS_PENDING,
                'transaction_reference' => $reference, // Référence FreeMoPay
                'notes' => "External ID: {$externalId}",
            ]);

            Log::info('✅ [WALLET] Withdrawal record created', [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $reference,
            ]);

            $freshUser = $user->fresh();

            return response()->json([
                'message' => 'Demande de retrait créée avec succès. Le traitement peut prendre quelques minutes.',
                'withdrawal' => new WithdrawalResource($withdrawal),
                // Keep mobile clients in sync (some expect `wallet.balance` in the response)
                'wallet' => [
                    'balance' => (int) round($freshUser->wallet_balance),
                    'formatted_balance' => $freshUser->formatted_balance,
                    'currency' => 'XAF',
                ],
                // Backward-compat: some clients look for this key.
                'current_balance' => (int) round($freshUser->wallet_balance),
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ [WALLET] Error initiating withdrawal', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'initiation du retrait: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Générer un ID externe unique pour le withdrawal
     */
    private function generateWithdrawalExternalId(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = substr(uniqid(), -4);
        return "WDR-{$timestamp}-{$random}";
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
            // Mobile expects `status=pending` to include both pending + processing.
            if ($request->status === Withdrawal::STATUS_PENDING) {
                $query->pending();
            } else {
                $query->where('status', $request->status);
            }
        }

        $withdrawals = $query->paginate($request->get('per_page', 20));

        return response()->json([
            // Return a flat array for mobile clients (avoid ResourceCollection wrapping under `data`),
            // while still letting JsonResource filter out MissingValue keys produced by `when()`.
            'withdrawals' => WithdrawalResource::collection($withdrawals->getCollection())
                ->resolve($request),
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
            'minimum_amount' => Withdrawal::getMinimumAmount(),
            'fee' => Withdrawal::WITHDRAWAL_FEE,
            'currency' => 'XAF',
        ]);
    }

    /**
     * Initier un dépôt sur le wallet via FreeMoPay
     */
    public function initiateDeposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'phone_number' => 'required|string',
        ]);

        $user = $request->user();
        $amount = (float) $request->amount;
        $phoneNumber = $request->phone_number;

        Log::info('💰 [WALLET] Initiation dépôt FreeMoPay', [
            'user_id' => $user->id,
            'amount' => $amount,
            'phone' => $phoneNumber,
        ]);

        try {
            $transaction = $this->freemopayService->initPayment(
                $user,
                $amount,
                $phoneNumber,
                'Dépôt de fonds wallet Weylo'
            );

            Log::info('✅ [WALLET] Dépôt FreeMoPay initié', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'USSD envoyé sur votre téléphone. Veuillez composer le code et confirmer le paiement.',
                'data' => [
                    'provider' => 'freemopay',
                    'transaction_id' => $transaction->id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ [WALLET] Erreur dépôt FreeMoPay', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Vérifier le statut d'un dépôt FreeMoPay
     */
    public function checkDepositStatus(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|integer|exists:transactions,id',
        ]);

        $user = $request->user();
        $transaction = \App\Models\Transaction::find($request->transaction_id);

        if (!$transaction || $transaction->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée',
            ], 404);
        }

        if ($transaction->type !== 'deposit') {
            return response()->json([
                'success' => false,
                'message' => 'Cette transaction n\'est pas un dépôt',
            ], 422);
        }

        Log::info('🔍 [WALLET] Vérification statut dépôt', [
            'transaction_id' => $transaction->id,
            'current_status' => $transaction->status,
        ]);

        $meta = json_decode($transaction->meta, true);
        $reference = $meta['provider_reference'] ?? null;

        // Si déjà complété ou échoué, retourner directement
        if (in_array($transaction->status, ['completed', 'failed', 'cancelled'])) {
            return response()->json([
                'success' => true,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at,
                'completed_at' => $transaction->completed_at,
            ]);
        }

        // Si pas de référence, retourner pending
        if (!$reference) {
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'En attente d\'initialisation',
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at,
            ]);
        }

        // Vérifier avec l'API FreeMoPay
        try {
            $statusResponse = $this->freemopayService->checkPaymentStatus($reference);
            $freemopayStatus = strtoupper($statusResponse['status'] ?? 'UNKNOWN');
            $message = $statusResponse['message'] ?? '';

            Log::info('📊 [WALLET] Statut FreeMoPay reçu', [
                'transaction_id' => $transaction->id,
                'freemopay_status' => $freemopayStatus,
            ]);

            $successStatuses = ['SUCCESS', 'SUCCESSFUL', 'COMPLETED'];
            $failedStatuses = ['FAILED', 'FAILURE', 'ERROR', 'REJECTED', 'CANCELLED', 'CANCELED'];

            // Mettre à jour si statut changé
            if (in_array($freemopayStatus, $successStatuses) && $transaction->status !== 'completed') {
                $transaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'meta' => json_encode(array_merge($meta, [
                        'status_response' => $statusResponse,
                        'verified_at' => now()->toISOString(),
                    ])),
                ]);

                // Créditer le wallet
                $this->processDeposit($transaction);

                Log::info('✅ [WALLET] Dépôt complété', [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                ]);

            } elseif (in_array($freemopayStatus, $failedStatuses) && $transaction->status !== 'failed') {
                $transaction->update([
                    'status' => 'failed',
                    'meta' => json_encode(array_merge($meta, [
                        'failure_reason' => $message,
                        'status_response' => $statusResponse,
                    ])),
                ]);

                Log::error('❌ [WALLET] Dépôt échoué', [
                    'transaction_id' => $transaction->id,
                    'reason' => $message,
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $transaction->fresh()->status,
                'freemopay_status' => $freemopayStatus,
                'message' => $message,
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at,
                'completed_at' => $transaction->completed_at,
            ]);

        } catch (\Exception $e) {
            Log::warning('⚠️ [WALLET] Erreur vérification statut', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            // Retourner le statut actuel sans erreur
            return response()->json([
                'success' => true,
                'status' => $transaction->status,
                'message' => 'En cours de traitement',
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at,
            ]);
        }
    }

    /**
     * Traiter un dépôt et créditer le wallet
     */
    private function processDeposit(\App\Models\Transaction $transaction): void
    {
        if ($transaction->type !== 'deposit') {
            return;
        }

        $user = $transaction->user;
        $balanceBefore = $user->wallet_balance;

        $user->increment('wallet_balance', $transaction->amount);

        $balanceAfter = $user->fresh()->wallet_balance;

        WalletTransaction::create([
            'user_id' => $user->id,
            'type' => WalletTransaction::TYPE_CREDIT,
            'amount' => $transaction->amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => 'Dépôt via FreeMoPay',
            'reference' => json_decode($transaction->meta, true)['external_id'] ?? null,
            'transactionable_type' => \App\Models\Transaction::class,
            'transactionable_id' => $transaction->id,
        ]);

        Log::info('💰 [WALLET] Solde crédité', [
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ]);
    }
}
