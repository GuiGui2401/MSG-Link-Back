<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AnonymousMessage;
use App\Models\Confession;
use App\Models\Conversation;
use App\Models\GiftTransaction;
use App\Models\PremiumSubscription;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Models\Report;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Statistiques globales du dashboard
     */
    public function index(): JsonResponse
    {
        // Utilisateurs
        $usersStats = [
            'total' => User::count(),
            'active' => User::active()->count(),
            'banned' => User::banned()->count(),
            'today' => User::whereDate('created_at', today())->count(),
            'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'this_month' => User::whereMonth('created_at', now()->month)->count(),
        ];

        // Messages
        $messagesStats = [
            'total' => AnonymousMessage::count(),
            'today' => AnonymousMessage::whereDate('created_at', today())->count(),
            'this_week' => AnonymousMessage::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
        ];

        // Confessions
        $confessionsStats = [
            'total' => Confession::count(),
            'pending' => Confession::pending()->count(),
            'approved' => Confession::where('status', Confession::STATUS_APPROVED)->count(),
            'rejected' => Confession::where('status', Confession::STATUS_REJECTED)->count(),
        ];

        // Conversations & Chat
        $chatStats = [
            'conversations' => Conversation::count(),
            'active_today' => Conversation::whereDate('last_message_at', today())->count(),
        ];

        // Revenus
        $revenueStats = [
            'total_gifts' => GiftTransaction::completed()->sum('amount'),
            'platform_fees' => GiftTransaction::completed()->sum('platform_fee'),
            'total_subscriptions' => PremiumSubscription::whereIn('status', ['active', 'expired'])->sum('amount'),
            'today' => Payment::completed()->whereDate('completed_at', today())->sum('amount'),
            'this_week' => Payment::completed()->whereBetween('completed_at', [now()->startOfWeek(), now()])->sum('amount'),
            'this_month' => Payment::completed()->whereMonth('completed_at', now()->month)->sum('amount'),
        ];

        // Retraits
        $withdrawalsStats = [
            'pending' => Withdrawal::pending()->count(),
            'pending_amount' => Withdrawal::pending()->sum('amount'),
            'completed_this_month' => Withdrawal::completed()
                ->whereMonth('processed_at', now()->month)
                ->sum('net_amount'),
        ];

        // Signalements
        $reportsStats = [
            'pending' => Report::pending()->count(),
            'resolved_today' => Report::where('status', Report::STATUS_RESOLVED)
                ->whereDate('reviewed_at', today())
                ->count(),
        ];

        // Stories
        $storiesStats = [
            'total' => Story::count(),
            'active' => Story::active()->count(),
            'expired' => Story::expired()->count(),
            'today' => Story::whereDate('created_at', today())->count(),
            'this_week' => Story::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'total_views' => Story::sum('views_count'),
            'average_views' => Story::avg('views_count'),
        ];

        return response()->json([
            'users' => $usersStats,
            'messages' => $messagesStats,
            'confessions' => $confessionsStats,
            'chat' => $chatStats,
            'revenue' => $revenueStats,
            'withdrawals' => $withdrawalsStats,
            'reports' => $reportsStats,
            'stories' => $storiesStats,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Analytics détaillées
     */
    public function analytics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7,30,90,365',
        ]);

        $days = $request->get('period', 30);
        $startDate = now()->subDays($days);

        // Inscriptions par jour
        $userRegistrations = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Messages par jour
        $messagesPerDay = AnonymousMessage::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenus par jour
        $revenuePerDay = Payment::select(
            DB::raw('DATE(completed_at) as date'),
            DB::raw('SUM(amount) as total')
        )
            ->completed()
            ->where('completed_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top utilisateurs par messages reçus
        $topUsersByMessages = User::select('users.id', 'first_name', 'last_name', 'username')
            ->withCount('receivedMessages')
            ->orderBy('received_messages_count', 'desc')
            ->limit(10)
            ->get();

        // Top utilisateurs par cadeaux reçus
        $topUsersByGifts = User::select('users.id', 'first_name', 'last_name', 'username')
            ->withSum(['giftsReceived as gifts_value' => function ($query) {
                $query->completed();
            }], 'net_amount')
            ->orderBy('gifts_value', 'desc')
            ->limit(10)
            ->get();

        // Répartition des cadeaux par tier
        $giftsByTier = GiftTransaction::select('gifts.tier', DB::raw('COUNT(*) as count'), DB::raw('SUM(gift_transactions.amount) as total'))
            ->join('gifts', 'gifts.id', '=', 'gift_transactions.gift_id')
            ->completed()
            ->groupBy('gifts.tier')
            ->get();

        // Stories par jour
        $storiesPerDay = Story::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(views_count) as total_views')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top utilisateurs par stories
        $topUsersByStories = User::select('users.id', 'first_name', 'last_name', 'username')
            ->withCount('stories')
            ->withSum('stories', 'views_count')
            ->orderBy('stories_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'period' => $days,
            'charts' => [
                'user_registrations' => $userRegistrations,
                'messages_per_day' => $messagesPerDay,
                'revenue_per_day' => $revenuePerDay,
                'stories_per_day' => $storiesPerDay,
            ],
            'rankings' => [
                'top_by_messages' => $topUsersByMessages,
                'top_by_gifts' => $topUsersByGifts,
                'top_by_stories' => $topUsersByStories,
            ],
            'distributions' => [
                'gifts_by_tier' => $giftsByTier,
            ],
        ]);
    }

    /**
     * Revenus détaillés
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        // Revenus par type
        $revenueByType = Payment::select('type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->completed()
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('type')
            ->get();

        // Revenus par provider
        $revenueByProvider = Payment::select('provider', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->completed()
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('provider')
            ->get();

        // Commissions gagnées (cadeaux)
        $platformFees = GiftTransaction::completed()
            ->whereBetween('created_at', [$from, $to])
            ->sum('platform_fee');

        // Total abonnements
        $subscriptionsRevenue = PremiumSubscription::whereIn('status', ['active', 'expired'])
            ->whereBetween('starts_at', [$from, $to])
            ->sum('amount');

        return response()->json([
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'total' => Payment::completed()->whereBetween('completed_at', [$from, $to])->sum('amount'),
                'platform_fees' => $platformFees,
                'subscriptions' => $subscriptionsRevenue,
                'transactions_count' => Payment::completed()->whereBetween('completed_at', [$from, $to])->count(),
            ],
            'by_type' => $revenueByType,
            'by_provider' => $revenueByProvider,
        ]);
    }

    /**
     * Activité récente
     */
    public function recentActivity(): JsonResponse
    {
        // Derniers utilisateurs inscrits
        $recentUsers = User::select('id', 'first_name', 'last_name', 'username', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Derniers paiements
        $recentPayments = Payment::with('user:id,first_name,last_name,username')
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        // Dernières demandes de retrait
        $recentWithdrawals = Withdrawal::with('user:id,first_name,last_name,username')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Derniers signalements
        $recentReports = Report::with('reporter:id,first_name,last_name,username')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'recent_users' => $recentUsers,
            'recent_payments' => $recentPayments,
            'recent_withdrawals' => $recentWithdrawals,
            'recent_reports' => $recentReports,
        ]);
    }
}
