<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConfessionResource;
use App\Http\Resources\ReportResource;
use App\Models\Confession;
use App\Models\Report;
use App\Models\AdminLog;
use App\Models\User;
use App\Models\AnonymousMessage;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    // ==================== CONFESSIONS ====================

    /**
     * Liste des confessions à modérer
     */
    public function confessions(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,approved,rejected,all',
            'type' => 'nullable|in:private,public',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Confession::with(['author:id,first_name,last_name,username', 'recipient:id,first_name,last_name,username']);

        // Par défaut, afficher les confessions en attente
        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $confessions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'confessions' => ConfessionResource::collection($confessions),
            'meta' => [
                'current_page' => $confessions->currentPage(),
                'last_page' => $confessions->lastPage(),
                'per_page' => $confessions->perPage(),
                'total' => $confessions->total(),
            ],
            'counts' => [
                'pending' => Confession::pending()->count(),
                'approved' => Confession::where('status', Confession::STATUS_APPROVED)->count(),
                'rejected' => Confession::where('status', Confession::STATUS_REJECTED)->count(),
            ],
        ]);
    }

    /**
     * Approuver une confession
     */
    public function approveConfession(Request $request, Confession $confession): JsonResponse
    {
        $admin = $request->user();

        if ($confession->status !== Confession::STATUS_PENDING) {
            return response()->json([
                'message' => 'Cette confession a déjà été modérée.',
            ], 422);
        }

        $confession->approve($admin);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_APPROVE_CONFESSION, $confession);

        return response()->json([
            'message' => 'Confession approuvée.',
            'confession' => new ConfessionResource($confession->fresh()),
        ]);
    }

    /**
     * Rejeter une confession
     */
    public function rejectConfession(Request $request, Confession $confession): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $admin = $request->user();

        if ($confession->status !== Confession::STATUS_PENDING) {
            return response()->json([
                'message' => 'Cette confession a déjà été modérée.',
            ], 422);
        }

        $confession->reject($admin, $request->reason);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_REJECT_CONFESSION, $confession, [], [
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Confession rejetée.',
            'confession' => new ConfessionResource($confession->fresh()),
        ]);
    }

    /**
     * Supprimer une confession
     */
    public function deleteConfession(Request $request, Confession $confession): JsonResponse
    {
        $admin = $request->user();

        AdminLog::log($admin, AdminLog::ACTION_DELETE_CONTENT, $confession, $confession->toArray());

        $confession->delete();

        return response()->json([
            'message' => 'Confession supprimée.',
        ]);
    }

    // ==================== SIGNALEMENTS ====================

    /**
     * Liste des signalements
     */
    public function reports(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pending,reviewed,resolved,dismissed,all',
            'reason' => 'nullable|string',
            'type' => 'nullable|string', // reportable_type
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Report::with([
            'reporter:id,first_name,last_name,username',
            'reviewer:id,first_name,last_name,username',
            'reportable',
        ]);

        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->has('reason')) {
            $query->where('reason', $request->reason);
        }

        if ($request->has('type')) {
            $typeMap = [
                'user' => User::class,
                'message' => AnonymousMessage::class,
                'confession' => Confession::class,
                'chat_message' => ChatMessage::class,
            ];
            if (isset($typeMap[$request->type])) {
                $query->where('reportable_type', $typeMap[$request->type]);
            }
        }

        $reports = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'reports' => ReportResource::collection($reports),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
            'counts' => [
                'pending' => Report::pending()->count(),
                'resolved' => Report::where('status', Report::STATUS_RESOLVED)->count(),
                'dismissed' => Report::where('status', Report::STATUS_DISMISSED)->count(),
            ],
        ]);
    }

    /**
     * Détail d'un signalement
     */
    public function showReport(Report $report): JsonResponse
    {
        $report->load([
            'reporter:id,first_name,last_name,username,email',
            'reviewer:id,first_name,last_name,username',
            'reportable',
        ]);

        // Charger des infos supplémentaires selon le type
        $additionalInfo = [];
        
        if ($report->reportable instanceof User) {
            $reportedUser = $report->reportable;
            $additionalInfo = [
                'user_reports_count' => Report::where('reportable_type', User::class)
                    ->where('reportable_id', $reportedUser->id)
                    ->count(),
                'user_is_banned' => $reportedUser->is_banned,
            ];
        }

        return response()->json([
            'report' => new ReportResource($report),
            'additional_info' => $additionalInfo,
        ]);
    }

    /**
     * Résoudre un signalement
     */
    public function resolveReport(Request $request, Report $report): JsonResponse
    {
        $request->validate([
            'action_taken' => 'required|string|max:500',
        ]);

        $admin = $request->user();

        if (!$report->is_pending) {
            return response()->json([
                'message' => 'Ce signalement a déjà été traité.',
            ], 422);
        }

        $report->resolve($admin, $request->action_taken);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_RESOLVE_REPORT, $report, [], [
            'action_taken' => $request->action_taken,
        ]);

        return response()->json([
            'message' => 'Signalement résolu.',
            'report' => new ReportResource($report->fresh()),
        ]);
    }

    /**
     * Rejeter un signalement
     */
    public function dismissReport(Request $request, Report $report): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $admin = $request->user();

        if (!$report->is_pending) {
            return response()->json([
                'message' => 'Ce signalement a déjà été traité.',
            ], 422);
        }

        $report->dismiss($admin, $request->reason);

        // Log
        AdminLog::log($admin, AdminLog::ACTION_DISMISS_REPORT, $report, [], [
            'reason' => $request->reason,
        ]);

        return response()->json([
            'message' => 'Signalement rejeté.',
            'report' => new ReportResource($report->fresh()),
        ]);
    }

    /**
     * Résoudre et bannir l'utilisateur signalé
     */
    public function resolveAndBan(Request $request, Report $report): JsonResponse
    {
        $request->validate([
            'ban_reason' => 'required|string|max:500',
        ]);

        $admin = $request->user();

        if (!$report->is_pending) {
            return response()->json([
                'message' => 'Ce signalement a déjà été traité.',
            ], 422);
        }

        // Identifier l'utilisateur à bannir
        $userToBan = null;

        if ($report->reportable instanceof User) {
            $userToBan = $report->reportable;
        } elseif ($report->reportable instanceof AnonymousMessage) {
            $userToBan = $report->reportable->sender;
        } elseif ($report->reportable instanceof Confession) {
            $userToBan = $report->reportable->author;
        } elseif ($report->reportable instanceof ChatMessage) {
            $userToBan = $report->reportable->sender;
        }

        if (!$userToBan) {
            return response()->json([
                'message' => 'Impossible d\'identifier l\'utilisateur à bannir.',
            ], 422);
        }

        if ($userToBan->is_admin) {
            return response()->json([
                'message' => 'Impossible de bannir un administrateur.',
            ], 403);
        }

        // Bannir l'utilisateur
        $userToBan->ban($request->ban_reason);

        // Résoudre le signalement
        $report->resolve($admin, "Utilisateur banni: {$request->ban_reason}");

        // Logs
        AdminLog::log($admin, AdminLog::ACTION_BAN_USER, $userToBan, [], [
            'reason' => $request->ban_reason,
            'from_report' => $report->id,
        ]);
        AdminLog::log($admin, AdminLog::ACTION_RESOLVE_REPORT, $report);

        return response()->json([
            'message' => 'Signalement résolu et utilisateur banni.',
            'banned_user' => $userToBan->username,
        ]);
    }

    /**
     * Statistiques de modération
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'confessions' => [
                'pending' => Confession::pending()->count(),
                'approved_today' => Confession::where('status', Confession::STATUS_APPROVED)
                    ->whereDate('moderated_at', today())
                    ->count(),
                'rejected_today' => Confession::where('status', Confession::STATUS_REJECTED)
                    ->whereDate('moderated_at', today())
                    ->count(),
            ],
            'reports' => [
                'pending' => Report::pending()->count(),
                'resolved_today' => Report::where('status', Report::STATUS_RESOLVED)
                    ->whereDate('reviewed_at', today())
                    ->count(),
                'by_reason' => Report::pending()
                    ->selectRaw('reason, COUNT(*) as count')
                    ->groupBy('reason')
                    ->pluck('count', 'reason'),
            ],
        ]);
    }
}
