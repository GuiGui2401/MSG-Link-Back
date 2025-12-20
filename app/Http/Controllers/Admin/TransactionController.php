<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\CinetPayTransferService;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(20);

        $types = Transaction::distinct()->pluck('type');
        $statuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];

        return view('admin.transactions.index', compact('transactions', 'types', 'statuses'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('user');
        return view('admin.transactions.show', compact('transaction'));
    }

    public function approve(Transaction $transaction)
    {
        if ($transaction->type === 'withdrawal' && $transaction->status === 'pending') {
            try {
                $user = $transaction->user;
                $amount = abs($transaction->amount);

                Log::info('=== ADMIN APPROVAL START ===', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'admin_user' => auth()->id()
                ]);

                // Vérifier que l'utilisateur a encore le solde
                $availableForWithdrawal = max(0, $user->wallet_balance);
                if ($amount > $availableForWithdrawal) {
                    Log::warning('Insufficient balance for withdrawal approval', [
                        'user_wallet_balance' => $user->wallet_balance,
                        'requested_amount' => $amount
                    ]);
                    return back()->with('error', 'L\'utilisateur n\'a plus le solde suffisant pour ce retrait.');
                }

                // Déduire le montant du solde
                $balanceBeforeDecrement = $user->wallet_balance;
                $user->decrement('wallet_balance', $amount);
                $user->refresh();

                Log::info('Wallet balance decremented', [
                    'user_id' => $user->id,
                    'wallet_balance_before' => $balanceBeforeDecrement,
                    'wallet_balance_after' => $user->wallet_balance
                ]);

                // Mettre à jour les métadonnées
                $meta = $transaction->meta ?? [];
                $meta['admin_validated'] = true;
                $meta['validated_at'] = now()->toISOString();
                $meta['validated_by'] = auth()->id();

                $transaction->update([
                    'status' => 'processing',
                    'meta' => $meta
                ]);

                // Exécuter le transfert CinetPay de manière SYNCHRONE
                $transferService = new CinetPayTransferService();
                $result = $transferService->executeTransfer(
                    $transaction,
                    $user,
                    $amount,
                    $meta['phone_number'] ?? null,
                    $meta['operator'] ?? null
                );

                Log::info('Transfert CinetPay exécuté depuis admin', [
                    'transaction_id' => $transaction->id,
                    'success' => $result['success'],
                    'result' => $result
                ]);

                if ($result['success']) {
                    return back()->with('success', 'Retrait approuvé et transfert CinetPay initié avec succès ! Transfer ID: ' . ($result['transfer_id'] ?? 'N/A'));
                } else {
                    // Le service a déjà remboursé l'utilisateur
                    return back()->with('error', 'Le transfert CinetPay a échoué. L\'utilisateur a été remboursé. Message: ' . ($result['message'] ?? 'Erreur inconnue'));
                }

            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'approbation admin du retrait', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id,
                    'trace' => $e->getTraceAsString()
                ]);

                // Rembourser l'utilisateur si le solde a été déduit
                if (isset($balanceBeforeDecrement) && $balanceBeforeDecrement > $user->fresh()->wallet_balance) {
                    $user->increment('wallet_balance', $amount);
                    Log::info('Utilisateur remboursé après erreur', [
                        'user_id' => $user->id,
                        'amount' => $amount
                    ]);
                }

                return back()->with('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Impossible d\'approuver cette transaction.');
    }

    public function reject(Transaction $transaction)
    {
        if ($transaction->type === 'withdrawal' && $transaction->status === 'pending') {
            // Ne PAS rembourser car le montant n'a jamais été débité lors de la création de la demande
            // Le solde reste intact, on change juste le statut
            $transaction->update(['status' => 'cancelled']);

            return back()->with('success', 'Retrait annulé.');
        }

        return back()->with('error', 'Impossible de rejeter cette transaction.');
    }

    public function pendingWithdrawals()
    {
        $transactions = Transaction::where('type', 'withdrawal')
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.transactions.pending-withdrawals', compact('transactions'));
    }
}
