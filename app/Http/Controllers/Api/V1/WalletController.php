<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\WithdrawalRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Http\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Services\Payment\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private DepositService $depositService
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
                    'amount' => $t->amount,
                    'description' => $t->description,
                    'status' => 'completed', // Les wallet_transactions sont toujours completed
                    'created_at' => $t->created_at,
                    'reference' => $t->reference,
                    'meta' => null,
                ];
            });

        // 2. Récupérer les transactions (withdrawals, deposits via CinetPay)
        $regularTransactions = \App\Models\Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($t) {
                return [
                    'id' => 't_' . $t->id,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'description' => $t->description,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                    'reference' => null,
                    'meta' => $t->meta,
                ];
            });

        // 3. Fusionner et trier par date
        $allTransactions = $walletTransactions->merge($regularTransactions)
            ->sortByDesc('created_at')
            ->values();

        // 4. Filtres optionnels
        if ($request->has('type')) {
            $allTransactions = $allTransactions->filter(function($t) use ($request) {
                return $t['type'] === $request->type;
            })->values();
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

    /**
     * Initier un dépôt sur le wallet
     */
    public function initiateDeposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'phone_number' => 'nullable|string',
        ]);

        $user = $request->user();
        $amount = (float) $request->amount;
        $phoneNumber = $request->phone_number;

        $result = $this->depositService->initiateDeposit($user, $amount, $phoneNumber);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de l\'initiation du dépôt',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dépôt initié avec succès',
            'data' => [
                'provider' => $result['provider'],
                'payment_url' => $result['payment_url'] ?? null,
                'payment_token' => $result['payment_token'] ?? null,
                'reference' => $result['reference'],
                'payment_id' => $result['payment_id'],
            ],
        ], 201);
    }
}
