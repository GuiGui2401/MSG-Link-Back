<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawalResource;
use App\Models\Withdrawal;
use App\Models\AdminLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Liste des demandes de retrait
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,processing,completed,failed,rejected,all',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Withdrawal::with([
            'user:id,first_name,last_name,username,email,phone',
            'processedBy:id,first_name,last_name,username',
        ]);

        $status = $request->get('status', 'pending');
        if ($status === 'all') {
            // Pas de filtre
        } elseif ($status === 'pending') {
            $query->whereIn('status', [Withdrawal::STATUS_PENDING, Withdrawal::STATUS_PROCESSING]);
        } else {
            $query->where('status', $status);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'withdrawals' => WithdrawalResource::collection($withdrawals),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
            'summary' => [
                'pending_count' => Withdrawal::pending()->count(),
                'pending_amount' => Withdrawal::pending()->sum('amount'),
                'completed_today' => Withdrawal::completed()
                    ->whereDate('processed_at', today())
                    ->sum('net_amount'),
            ],
        ]);
    }

    /**
     * Détail d'une demande de retrait
     */
    public function show(Withdrawal $withdrawal): JsonResponse
    {
        $withdrawal->load([
            'user:id,first_name,last_name,username,email,phone,wallet_balance',
            'processedBy:id,first_name,last_name,username',
        ]);

        // Historique des retraits de cet utilisateur
        $userWithdrawalHistory = Withdrawal::where('user_id', $withdrawal->user_id)
            ->where('id', '!=', $withdrawal->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'withdrawal' => new WithdrawalResource($withdrawal),
            'user_history' => WithdrawalResource::collection($userWithdrawalHistory),
            'user_balance' => $withdrawal->user->wallet_balance,
        ]);
    }

    /**
     * Traiter (approuver) une demande de retrait
     */
    public function process(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $admin = $request->user();

        if (!$withdrawal->is_pending) {
            return response()->json([
                'message' => 'Cette demande de retrait a déjà été traitée.',
            ], 422);
        }

        // Vérifier que l'utilisateur a toujours le solde suffisant
        if (!$withdrawal->user->hasEnoughBalance($withdrawal->amount)) {
            return response()->json([
                'message' => 'L\'utilisateur n\'a plus le solde suffisant.',
                'current_balance' => $withdrawal->user->wallet_balance,
            ], 422);
        }

        // Mettre à jour les notes si fournies
        if ($request->has('notes')) {
            $withdrawal->update(['notes' => $request->notes]);
        }

        // Traiter le retrait
        $withdrawal->process($admin, $request->transaction_reference);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_PROCESS_WITHDRAWAL, $withdrawal, [], [
            'transaction_reference' => $request->transaction_reference,
        ]);

        // Notification à l'utilisateur
        $this->notificationService->sendWithdrawalProcessedNotification($withdrawal);

        return response()->json([
            'message' => 'Retrait traité avec succès.',
            'withdrawal' => new WithdrawalResource($withdrawal->fresh()),
        ]);
    }

    /**
     * Rejeter une demande de retrait
     */
    public function reject(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $admin = $request->user();

        if (!$withdrawal->is_pending) {
            return response()->json([
                'message' => 'Cette demande de retrait a déjà été traitée.',
            ], 422);
        }

        $withdrawal->reject($admin, $request->reason);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_REJECT_WITHDRAWAL, $withdrawal, [], [
            'reason' => $request->reason,
        ]);

        // Notification à l'utilisateur
        $this->notificationService->sendWithdrawalRejectedNotification($withdrawal);

        return response()->json([
            'message' => 'Demande de retrait rejetée.',
            'withdrawal' => new WithdrawalResource($withdrawal->fresh()),
        ]);
    }

    /**
     * Statistiques des retraits
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return response()->json([
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'total_pending' => Withdrawal::pending()->count(),
                'total_pending_amount' => Withdrawal::pending()->sum('amount'),
            ],
            'processed' => [
                'count' => Withdrawal::completed()
                    ->whereBetween('processed_at', [$from, $to])
                    ->count(),
                'total_amount' => Withdrawal::completed()
                    ->whereBetween('processed_at', [$from, $to])
                    ->sum('net_amount'),
            ],
            'rejected' => [
                'count' => Withdrawal::where('status', Withdrawal::STATUS_REJECTED)
                    ->whereBetween('processed_at', [$from, $to])
                    ->count(),
            ],
            'by_provider' => Withdrawal::completed()
                ->whereBetween('processed_at', [$from, $to])
                ->selectRaw('provider, COUNT(*) as count, SUM(net_amount) as total')
                ->groupBy('provider')
                ->get(),
        ]);
    }

    /**
     * Export des retraits (CSV)
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,completed,rejected,all',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $query = Withdrawal::with('user:id,first_name,last_name,username,phone');

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->from) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->to) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->get();

        // Préparer les données pour l'export
        $exportData = $withdrawals->map(function ($w) {
            return [
                'id' => $w->id,
                'user' => $w->user->full_name,
                'username' => $w->user->username,
                'phone' => $w->phone_number,
                'provider' => $w->provider_label,
                'amount' => $w->amount,
                'fee' => $w->fee,
                'net_amount' => $w->net_amount,
                'status' => $w->status_label,
                'created_at' => $w->created_at->format('Y-m-d H:i:s'),
                'processed_at' => $w->processed_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'data' => $exportData,
            'count' => $exportData->count(),
        ]);
    }
}
