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
use App\Models\Group;
use App\Models\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class AdminWebController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check() && in_array(Auth::user()->role, ['superadmin', 'admin', 'moderator'])) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Identifiants invalides.']);
        }

        if (!in_array($user->role, ['superadmin', 'admin', 'moderator'])) {
            return back()->withErrors(['email' => 'Accès non autorisé.']);
        }

        if ($user->is_banned) {
            return back()->withErrors(['email' => 'Ce compte a été suspendu.']);
        }

        Auth::login($user, $request->boolean('remember'));

        return redirect()->route('admin.dashboard');
    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }

    /**
     * Dashboard
     */
    public function dashboard()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('is_banned', false)->count(),
                'banned' => User::where('is_banned', true)->count(),
                'today' => User::whereDate('created_at', today())->count(),
                'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            ],
            'messages' => [
                'total' => AnonymousMessage::count(),
                'today' => AnonymousMessage::whereDate('created_at', today())->count(),
            ],
            'confessions' => [
                'total' => Confession::count(),
                'pending' => Confession::where('status', 'pending')->count(),
                'approved' => Confession::where('status', 'approved')->count(),
                'rejected' => Confession::where('status', 'rejected')->count(),
            ],
            'chat' => [
                'conversations' => Conversation::count(),
                'active_today' => Conversation::whereDate('last_message_at', today())->count(),
            ],
            'revenue' => [
                'total_gifts' => GiftTransaction::where('status', 'completed')->sum('amount'),
                'platform_fees' => GiftTransaction::where('status', 'completed')->sum('platform_fee'),
                'total_subscriptions' => PremiumSubscription::whereIn('status', ['active', 'expired'])->sum('amount'),
                'this_month' => Payment::where('status', 'completed')->whereMonth('completed_at', now()->month)->sum('amount'),
            ],
            'withdrawals' => [
                'pending' => Withdrawal::where('status', 'pending')->count(),
                'pending_amount' => Withdrawal::where('status', 'pending')->sum('amount'),
                'completed_this_month' => Withdrawal::where('status', 'completed')
                    ->whereMonth('processed_at', now()->month)
                    ->sum('net_amount'),
            ],
            'reports' => [
                'pending' => Report::where('status', 'pending')->count(),
            ],
            'stories' => [
                'total' => Story::count(),
                'active' => Story::where('status', Story::STATUS_ACTIVE)
                    ->where('expires_at', '>', now())
                    ->count(),
                'expired' => Story::where(function($q) {
                    $q->where('status', Story::STATUS_EXPIRED)
                        ->orWhere('expires_at', '<=', now());
                })->count(),
                'today' => Story::whereDate('created_at', today())->count(),
                'this_week' => Story::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
                'total_views' => Story::sum('views_count'),
                'average_views' => Story::avg('views_count') ?: 0,
            ],
        ];

        // Chart data for last 30 days
        $days = 30;
        $startDate = now()->subDays($days);

        $userRegistrations = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenuePerDay = Payment::select(
            DB::raw('DATE(completed_at) as date'),
            DB::raw('SUM(amount) as total')
        )
            ->where('status', 'completed')
            ->where('completed_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = [
            'users' => [
                'labels' => $userRegistrations->pluck('date')->map(fn($d) => date('d/m', strtotime($d))),
                'data' => $userRegistrations->pluck('count'),
            ],
            'revenue' => [
                'labels' => $revenuePerDay->pluck('date')->map(fn($d) => date('d/m', strtotime($d))),
                'data' => $revenuePerDay->pluck('total'),
            ],
        ];

        // Recent activity
        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $recentPayments = Payment::with('user')
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'chartData', 'recentUsers', 'recentPayments'));
    }

    /**
     * Analytics
     */
    public function analytics(Request $request)
    {
        $days = $request->get('period', 30);
        $startDate = now()->subDays($days);

        $charts = [
            'user_registrations' => User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'messages_per_day' => AnonymousMessage::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'revenue_per_day' => Payment::select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('SUM(amount) as total')
            )
                ->where('status', 'completed')
                ->where('completed_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        $rankings = [
            'top_by_messages' => User::withCount('receivedMessages')
                ->orderBy('received_messages_count', 'desc')
                ->limit(10)
                ->get(),

            'top_by_gifts' => User::withSum(['giftsReceived as gifts_value' => function ($query) {
                    $query->where('status', 'completed');
                }], 'net_amount')
                ->orderBy('gifts_value', 'desc')
                ->limit(10)
                ->get(),
        ];

        $distributions = [
            'gifts_by_tier' => GiftTransaction::select('gifts.tier', DB::raw('COUNT(*) as count'))
                ->join('gifts', 'gifts.id', '=', 'gift_transactions.gift_id')
                ->where('gift_transactions.status', 'completed')
                ->groupBy('gifts.tier')
                ->get(),
        ];

        return view('admin.analytics', compact('charts', 'rankings', 'distributions'));
    }

    /**
     * Revenue
     */
    public function revenue(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $summary = [
            'total' => Payment::where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->sum('amount'),
            'platform_fees' => GiftTransaction::where('status', 'completed')
                ->whereBetween('created_at', [$from, $to])
                ->sum('platform_fee'),
            'subscriptions' => PremiumSubscription::whereIn('status', ['active', 'expired'])
                ->whereBetween('starts_at', [$from, $to])
                ->sum('amount'),
            'transactions_count' => Payment::where('status', 'completed')
                ->whereBetween('completed_at', [$from, $to])
                ->count(),
        ];

        $byType = Payment::select('type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('type')
            ->get();

        $byProvider = Payment::select('provider', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('provider')
            ->get();

        $recentPayments = Payment::with('user')
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.revenue', compact('summary', 'byType', 'byProvider', 'recentPayments'));
    }

    /**
     * Settings
     */
    public function settings()
    {
        return view('admin.settings');
    }

    // ==================== PROFILE ====================

    /**
     * Show profile page
     */
    public function profile()
    {
        $user = auth()->user();
        return view('admin.profile.index', compact('user'));
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'bio' => $validated['bio'],
        ];

        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar si existant
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe mis à jour avec succès.');
    }

    /**
     * Delete avatar
     */
    public function deleteAvatar()
    {
        $user = auth()->user();

        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return back()->with('success', 'Photo de profil supprimée.');
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        return back()->with('success', 'Cache vidé avec succès.');
    }

    /**
     * Clear config cache
     */
    public function clearConfigCache()
    {
        Artisan::call('config:clear');
        return back()->with('success', 'Configuration rechargée avec succès.');
    }

    // ==================== TEAM (ADMINS & MODERATORS) ====================

    public function team()
    {
        $currentUser = auth()->user();

        // Seuls les admins et superadmins peuvent voir la gestion d'équipe
        if (!$currentUser->is_admin) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Accès non autorisé.');
        }

        $teamMembers = User::whereIn('role', ['superadmin', 'admin', 'moderator'])
            ->orderByRaw("FIELD(role, 'superadmin', 'admin', 'moderator')")
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'superadmins' => User::where('role', 'superadmin')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'moderators' => User::where('role', 'moderator')->count(),
        ];

        return view('admin.team.index', compact('teamMembers', 'stats'));
    }

    public function createTeamMember()
    {
        $currentUser = auth()->user();

        // Seuls les admins et superadmins peuvent créer des membres d'équipe
        if (!$currentUser->is_admin) {
            return redirect()->route('admin.team.index')
                ->with('error', 'Accès non autorisé.');
        }

        return view('admin.team.create');
    }

    public function storeTeamMember(Request $request)
    {
        $currentUser = auth()->user();

        // Seuls les admins et superadmins peuvent créer des membres d'équipe
        if (!$currentUser->is_admin) {
            return redirect()->route('admin.team.index')
                ->with('error', 'Accès non autorisé.');
        }

        // Déterminer les rôles autorisés selon le rôle de l'utilisateur actuel
        $allowedRoles = ['moderator'];
        if ($currentUser->is_super_admin) {
            $allowedRoles = ['moderator', 'admin', 'superadmin'];
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now(), // Vérification automatique pour l'équipe
        ]);

        return redirect()->route('admin.team.index')
            ->with('success', "Le membre d'équipe {$user->username} a été créé avec succès.");
    }

    // ==================== USERS ====================

    public function users(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_banned', false);
            } elseif ($status === 'banned') {
                $query->where('is_banned', true);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_banned', false)->count(),
            'banned' => User::where('is_banned', true)->count(),
            'today' => User::whereDate('created_at', today())->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function showUser(User $user)
    {
        $userStats = [
            'messages_received' => $user->receivedMessages()->count(),
            'messages_sent' => $user->sentMessages()->count(),
            'confessions_written' => $user->confessionsWritten()->count(),
            'confessions_received' => $user->confessionsReceived()->count(),
            'wallet_balance' => $user->wallet_balance ?? 0,
            'total_withdrawn' => $user->withdrawals()->where('status', 'completed')->sum('net_amount'),
            'gifts_received' => $user->giftsReceived()->count(),
            'gifts_sent' => $user->giftsSent()->count(),
        ];

        // Signalements contre cet utilisateur (relation polymorphique)
        $reports = Report::where('reportable_type', User::class)
            ->where('reportable_id', $user->id)
            ->with('reporter')
            ->latest()
            ->limit(5)
            ->get();

        $recentActivity = [];

        return view('admin.users.show', compact('user', 'userStats', 'reports', 'recentActivity'));
    }

    public function banUser(Request $request, User $user)
    {
        // Empêcher l'auto-bannissement
        if ($user->id === auth()->id()) {
            return back()->with('error', "Vous ne pouvez pas vous bannir vous-même.");
        }

        // Vérifier les permissions
        if (!auth()->user()->canBan($user)) {
            return back()->with('error', "Vous n'avez pas la permission de bannir cet utilisateur.");
        }

        $user->update([
            'is_banned' => true,
            'banned_at' => now(),
            'banned_reason' => $request->input('banned_reason'),
        ]);

        // Révoquer tous les tokens de l'utilisateur
        $user->tokens()->delete();

        return back()->with('success', "L'utilisateur {$user->username} a été banni.");
    }

    public function unbanUser(User $user)
    {
        // Vérifier les permissions
        if (!auth()->user()->canManage($user)) {
            return back()->with('error', "Vous n'avez pas la permission de débannir cet utilisateur.");
        }

        $user->update([
            'is_banned' => false,
            'banned_at' => null,
            'banned_reason' => null,
        ]);

        return back()->with('success', "L'utilisateur {$user->username} a été débanni.");
    }

    public function editUser(User $user)
    {
        // Vérifier les permissions (on peut s'éditer soi-même)
        if ($user->id !== auth()->id() && !auth()->user()->canManage($user)) {
            return back()->with('error', "Vous n'avez pas la permission de modifier cet utilisateur.");
        }

        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $currentUser = auth()->user();
        $isSelf = $user->id === $currentUser->id;

        // Vérifier les permissions (on peut s'éditer soi-même)
        if (!$isSelf && !$currentUser->canManage($user)) {
            return back()->with('error', "Vous n'avez pas la permission de modifier cet utilisateur.");
        }

        // Déterminer les rôles autorisés selon le rôle de l'utilisateur actuel
        // Inclure le rôle actuel de l'utilisateur édité pour éviter les erreurs de validation
        $allowedRoles = ['user'];
        if ($currentUser->is_super_admin) {
            $allowedRoles = ['user', 'moderator', 'admin', 'superadmin'];
        } elseif ($currentUser->role === 'admin') {
            $allowedRoles = ['user', 'moderator', 'admin']; // admin inclus pour l'édition de soi-même
        } elseif ($currentUser->role === 'moderator') {
            $allowedRoles = ['user', 'moderator']; // moderator inclus pour l'édition de soi-même
        }

        // S'assurer que le rôle actuel de l'utilisateur est toujours autorisé (pour l'édition de soi-même)
        if ($isSelf && !in_array($user->role, $allowedRoles)) {
            $allowedRoles[] = $user->role;
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'role' => 'required|in:' . implode(',', $allowedRoles),
            'password' => 'nullable|string|min:8|confirmed',
            'wallet_balance' => 'nullable|numeric|min:0',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'bio' => $validated['bio'],
            'is_verified' => $request->boolean('is_verified'),
        ];

        // Le rôle ne peut être changé que si on a les permissions et qu'on n'est pas soi-même
        // (sauf pour les superadmins qui peuvent changer leur propre rôle)
        if (!$isSelf && $currentUser->canManage($user)) {
            $data['role'] = $validated['role'];
        } elseif ($isSelf && $currentUser->is_super_admin) {
            // Un superadmin peut changer son propre rôle (dangereux mais autorisé)
            $data['role'] = $validated['role'];
        }
        // Sinon, on ne change pas le rôle (l'utilisateur garde son rôle actuel)

        if ($request->boolean('email_verified') && !$user->email_verified_at) {
            $data['email_verified_at'] = now();
        } elseif (!$request->boolean('email_verified')) {
            $data['email_verified_at'] = null;
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['wallet_balance']) && $currentUser->is_admin) {
            $data['wallet_balance'] = $validated['wallet_balance'];
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "L'utilisateur {$user->username} a été mis à jour.");
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if (!auth()->user()->canManage($user)) {
            return back()->with('error', "Vous n'avez pas la permission de supprimer cet utilisateur.");
        }

        $username = $user->username;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "L'utilisateur {$username} a été supprimé.");
    }

    // ==================== MODERATION ====================

    public function moderation()
    {
        $stats = [
            'reports_pending' => Report::where('status', 'pending')->count(),
            'confessions_pending' => Confession::where('status', 'pending')->count(),
            'resolved_today' => Report::where('status', 'resolved')
                ->whereDate('reviewed_at', today())
                ->count(),
            'bans_today' => User::whereDate('banned_at', today())->count(),
        ];

        $reports = Report::with('reporter')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $pendingConfessions = Confession::with(['author', 'recipient'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.moderation.index', compact('stats', 'reports', 'pendingConfessions'));
    }

    public function showReport(Report $report)
    {
        $report->load(['reporter', 'reportable']);
        return view('admin.moderation.report', compact('report'));
    }

    public function resolveReport(Report $report)
    {
        $report->update([
            'status' => 'resolved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Signalement marqué comme résolu.');
    }

    public function dismissReport(Report $report)
    {
        $report->update([
            'status' => 'dismissed',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Signalement rejeté.');
    }

    public function deleteReportedContent(Report $report)
    {
        if ($report->reportable) {
            $report->reportable->delete();
        }

        $report->update([
            'status' => 'resolved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Contenu supprimé et signalement résolu.');
    }

    public function resolveAndBan(Report $report)
    {
        $contentOwner = $report->reportable?->sender ?? $report->reportable?->author;

        if ($contentOwner) {
            $contentOwner->update([
                'is_banned' => true,
                'banned_at' => now(),
                'ban_reason' => 'Suite au signalement #' . $report->id,
            ]);
        }

        $report->update([
            'status' => 'resolved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Signalement résolu et utilisateur banni.');
    }

    // ==================== CONFESSIONS ====================

    public function confessions(Request $request)
    {
        $query = Confession::with(['author', 'recipient']);

        if ($search = $request->get('search')) {
            $query->where('content', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $confessions = $query->orderBy('created_at', 'desc')->paginate(18)->withQueryString();

        $stats = [
            'total' => Confession::count(),
            'pending' => Confession::where('status', 'pending')->count(),
            'approved' => Confession::where('status', 'approved')->count(),
            'rejected' => Confession::where('status', 'rejected')->count(),
        ];

        return view('admin.confessions.index', compact('confessions', 'stats'));
    }

    public function approveConfession(Confession $confession)
    {
        $confession->update([
            'status' => 'approved',
            'moderated_at' => now(),
            'moderated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Confession approuvée.');
    }

    public function rejectConfession(Confession $confession)
    {
        $confession->update([
            'status' => 'rejected',
            'moderated_at' => now(),
            'moderated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Confession rejetée.');
    }

    public function showConfession(Confession $confession)
    {
        $confession->load(['author', 'recipient', 'moderator']);
        return view('admin.confessions.show', compact('confession'));
    }

    public function destroyConfession(Confession $confession)
    {
        $confession->delete();
        return back()->with('success', 'Confession supprimée.');
    }

    // ==================== PAYMENTS ====================

    public function payments(Request $request)
    {
        $query = Payment::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'completed_amount' => Payment::where('status', 'completed')->sum('amount'),
            'this_month' => Payment::where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->sum('amount'),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'total_count' => Payment::count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function showPayment(Payment $payment)
    {
        $payment->load('user');
        return view('admin.payments.show', compact('payment'));
    }

    // ==================== MESSAGES ====================

    public function messages(Request $request)
    {
        $query = AnonymousMessage::with(['sender', 'recipient']);

        if ($search = $request->get('search')) {
            $query->where('content', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            if ($status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($status === 'unread') {
                $query->whereNull('read_at');
            }
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => AnonymousMessage::count(),
            'today' => AnonymousMessage::whereDate('created_at', today())->count(),
            'read' => AnonymousMessage::whereNotNull('read_at')->count(),
            'reported' => AnonymousMessage::has('reports')->count(),
        ];

        return view('admin.messages.index', compact('messages', 'stats'));
    }

    public function destroyMessage(AnonymousMessage $message)
    {
        $message->delete();
        return back()->with('success', 'Message supprimé.');
    }

    // ==================== STORIES ====================

    public function stories(Request $request)
    {
        $query = Story::with('user');

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('status', Story::STATUS_ACTIVE)
                    ->where('expires_at', '>', now());
            } elseif ($status === 'expired') {
                $query->where(function($q) {
                    $q->where('status', Story::STATUS_EXPIRED)
                        ->orWhere('expires_at', '<=', now());
                });
            }
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $stories = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => Story::count(),
            'active' => Story::where('status', Story::STATUS_ACTIVE)
                ->where('expires_at', '>', now())
                ->count(),
            'expired' => Story::where(function($q) {
                $q->where('status', Story::STATUS_EXPIRED)
                    ->orWhere('expires_at', '<=', now());
            })->count(),
            'today' => Story::whereDate('created_at', today())->count(),
            'total_views' => Story::sum('views_count'),
        ];

        return view('admin.stories.index', compact('stories', 'stats'));
    }

    public function destroyStory(Story $story)
    {
        // Supprimer le fichier media du stockage si existe
        if ($story->media_url) {
            \Storage::disk('public')->delete($story->media_url);
        }
        if ($story->thumbnail_url) {
            \Storage::disk('public')->delete($story->thumbnail_url);
        }

        $story->delete();
        return back()->with('success', 'Story supprimée.');
    }

    // ==================== GROUPS ====================

    public function groups(Request $request)
    {
        $query = Group::with(['creator', 'lastMessage']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($q2) use ($search) {
                        $q2->where('username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        $groups = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => Group::count(),
            'public' => Group::where('is_public', true)->count(),
            'private' => Group::where('is_public', false)->count(),
            'today' => Group::whereDate('created_at', today())->count(),
            'this_week' => Group::whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            'total_members' => Group::sum('members_count'),
            'total_messages' => GroupMessage::count(),
        ];

        return view('admin.groups.index', compact('groups', 'stats'));
    }

    public function showGroup(Group $group)
    {
        $group->load([
            'creator',
            'activeMembers.user',
            'messages' => function ($query) {
                $query->latest()->limit(50);
            },
        ]);

        $stats = [
            'members' => $group->members_count,
            'messages' => $group->messages_count,
            'messages_today' => $group->messages()->whereDate('created_at', today())->count(),
            'last_activity' => $group->last_message_at,
        ];

        return view('admin.groups.show', compact('group', 'stats'));
    }

    public function destroyGroup(Group $group)
    {
        $groupName = $group->name;
        $group->delete();
        return redirect()->route('admin.groups.index')
            ->with('success', "Le groupe \"{$groupName}\" a été supprimé.");
    }

    // ==================== GIFTS ====================

    public function gifts(Request $request)
    {
        $query = GiftTransaction::with(['sender', 'recipient', 'gift']);

        if ($search = $request->get('search')) {
            $query->whereHas('sender', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%");
            })->orWhereHas('recipient', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($tier = $request->get('tier')) {
            $query->whereHas('gift', function ($q) use ($tier) {
                $q->where('tier', $tier);
            });
        }

        $gifts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'total' => GiftTransaction::count(),
            'total_amount' => GiftTransaction::where('status', 'completed')->sum('amount'),
            'platform_fees' => GiftTransaction::where('status', 'completed')->sum('platform_fee'),
            'this_month' => GiftTransaction::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
        ];

        return view('admin.gifts.index', compact('gifts', 'stats'));
    }

    // ==================== WITHDRAWALS ====================

    public function withdrawals(Request $request)
    {
        $query = Withdrawal::with('user');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->get('method')) {
            $query->where('method', $method);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $stats = [
            'pending_count' => Withdrawal::where('status', 'pending')->count(),
            'pending_amount' => Withdrawal::where('status', 'pending')->sum('amount'),
            'completed_today' => Withdrawal::where('status', 'completed')
                ->whereDate('processed_at', today())
                ->count(),
            'completed_this_month' => Withdrawal::where('status', 'completed')
                ->whereMonth('processed_at', now()->month)
                ->sum('net_amount'),
        ];

        return view('admin.withdrawals.index', compact('withdrawals', 'stats'));
    }

    public function showWithdrawal(Withdrawal $withdrawal)
    {
        $withdrawal->load('user');
        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    public function processWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'transaction_reference' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $withdrawal->update([
            'status' => 'completed',
            'transaction_reference' => $request->transaction_reference,
            'notes' => $request->notes,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.withdrawals.index')
            ->with('success', 'Retrait traité avec succès.');
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        // Refund the amount to user's wallet
        if ($withdrawal->user->wallet) {
            $withdrawal->user->wallet->increment('balance', $withdrawal->amount);
        }

        $withdrawal->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.withdrawals.index')
            ->with('success', 'Retrait rejeté et montant recrédité.');
    }

    public function exportWithdrawals(Request $request)
    {
        $withdrawals = Withdrawal::with('user')
            ->where('status', $request->get('status', 'pending'))
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'withdrawals_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($withdrawals) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Utilisateur', 'Montant', 'Net', 'Méthode', 'Téléphone', 'Statut', 'Date']);

            foreach ($withdrawals as $w) {
                fputcsv($file, [
                    $w->id,
                    $w->user->username ?? 'N/A',
                    $w->amount,
                    $w->net_amount,
                    $w->method,
                    $w->phone_number,
                    $w->status,
                    $w->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==================== LINK GENERATOR ====================

    public function linkGenerator()
    {
        return view('admin.link-generator');
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $users = User::where(function ($query) use ($request) {
                $search = $request->q;
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->where('is_banned', false)
            ->select(['id', 'first_name', 'last_name', 'username', 'avatar'])
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                ];
            }),
        ]);
    }
}
