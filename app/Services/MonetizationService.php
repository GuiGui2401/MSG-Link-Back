<?php

namespace App\Services;

use App\Models\MonetizationPayout;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonetizationService
{
    public function getPeriod(string $type): array
    {
        $days = (int) setting($type === MonetizationPayout::TYPE_CREATOR_FUND
            ? 'creator_fund_period_days'
            : 'ads_revenue_period_days', 30);

        $periodEnd = now()->startOfDay();
        $periodStart = $periodEnd->copy()->subDays($days);

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
            'days' => $days,
        ];
    }

    public function calculateEngagement(Carbon $start, Carbon $end): array
    {
        $viewWeight = (float) setting('engagement_view_weight', 1);
        $likeWeight = (float) setting('engagement_like_weight', 3);

        $storyViews = DB::table('story_views')
            ->join('stories', 'stories.id', '=', 'story_views.story_id')
            ->whereBetween('story_views.created_at', [$start, $end])
            ->select('stories.user_id', DB::raw('COUNT(*) as views'))
            ->groupBy('stories.user_id')
            ->get()
            ->keyBy('user_id');

        $confessionViews = DB::table('confession_views')
            ->join('confessions', 'confessions.id', '=', 'confession_views.confession_id')
            ->whereBetween('confession_views.created_at', [$start, $end])
            ->select('confessions.author_id as user_id', DB::raw('COUNT(*) as views'))
            ->groupBy('confessions.author_id')
            ->get()
            ->keyBy('user_id');

        $confessionLikes = DB::table('confession_likes')
            ->join('confessions', 'confessions.id', '=', 'confession_likes.confession_id')
            ->whereBetween('confession_likes.created_at', [$start, $end])
            ->select('confessions.author_id as user_id', DB::raw('COUNT(*) as likes'))
            ->groupBy('confessions.author_id')
            ->get()
            ->keyBy('user_id');

        $userIds = collect()
            ->merge($storyViews->keys())
            ->merge($confessionViews->keys())
            ->merge($confessionLikes->keys())
            ->unique()
            ->values();

        $stats = [];
        foreach ($userIds as $userId) {
            $views = (int) ($storyViews[$userId]->views ?? 0) + (int) ($confessionViews[$userId]->views ?? 0);
            $likes = (int) ($confessionLikes[$userId]->likes ?? 0);
            $score = (int) round(($views * $viewWeight) + ($likes * $likeWeight));

            $stats[$userId] = [
                'views' => $views,
                'likes' => $likes,
                'score' => $score,
            ];
        }

        return $stats;
    }

    public function estimateEarnings(int $userId, string $type): array
    {
        if (!$this->isTypeEnabled($type)) {
            return [
                'period_start' => null,
                'period_end' => null,
                'views' => 0,
                'likes' => 0,
                'score' => 0,
                'total_score' => 0,
                'pool' => 0,
                'estimated_amount' => 0,
            ];
        }

        $period = $this->getPeriod($type);
        $engagement = $this->calculateEngagement($period['start'], $period['end']);
        $totalScore = collect($engagement)->sum('score');

        $userStats = $engagement[$userId] ?? ['views' => 0, 'likes' => 0, 'score' => 0];
        $pool = $this->getPoolAmount($type);

        $amount = $totalScore > 0 ? (int) floor($pool * ($userStats['score'] / $totalScore)) : 0;

        return [
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'views' => $userStats['views'],
            'likes' => $userStats['likes'],
            'score' => $userStats['score'],
            'total_score' => $totalScore,
            'pool' => $pool,
            'estimated_amount' => $amount,
        ];
    }

    public function distribute(string $type): array
    {
        if (!$this->isTypeEnabled($type)) {
            return [
                'created' => 0,
                'skipped' => 0,
                'already_processed' => true,
            ];
        }

        $period = $this->getPeriod($type);

        $alreadyExists = MonetizationPayout::where('type', $type)
            ->where('period_start', $period['start'])
            ->where('period_end', $period['end'])
            ->exists();

        if ($alreadyExists) {
            return [
                'created' => 0,
                'skipped' => 0,
                'already_processed' => true,
            ];
        }

        $engagement = $this->calculateEngagement($period['start'], $period['end']);
        $totalScore = collect($engagement)->sum('score');
        $pool = $this->getPoolAmount($type);
        $minPayout = (int) setting('monetization_min_payout', 100);
        $autoPayout = (bool) setting('monetization_auto_payout', false);

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $engagement,
            $totalScore,
            $pool,
            $type,
            $period,
            $minPayout,
            $autoPayout,
            &$created,
            &$skipped
        ) {
            foreach ($engagement as $userId => $stats) {
                if ($totalScore <= 0) {
                    break;
                }

                $amount = (int) floor($pool * ($stats['score'] / $totalScore));

                if ($amount < $minPayout) {
                    MonetizationPayout::create([
                        'user_id' => $userId,
                        'type' => $type,
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'views_count' => $stats['views'],
                        'likes_count' => $stats['likes'],
                        'engagement_score' => $stats['score'],
                        'total_engagement_score' => $totalScore,
                        'amount' => $amount,
                        'status' => MonetizationPayout::STATUS_SKIPPED,
                        'processed_at' => now(),
                        'metadata' => [
                            'reason' => 'below_minimum',
                        ],
                    ]);
                    $skipped++;
                    continue;
                }

                $payout = MonetizationPayout::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'period_start' => $period['start'],
                    'period_end' => $period['end'],
                    'views_count' => $stats['views'],
                    'likes_count' => $stats['likes'],
                    'engagement_score' => $stats['score'],
                    'total_engagement_score' => $totalScore,
                    'amount' => $amount,
                    'status' => $autoPayout ? MonetizationPayout::STATUS_PAID : MonetizationPayout::STATUS_PENDING,
                    'processed_at' => $autoPayout ? now() : null,
                ]);

                if ($autoPayout) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->creditWallet(
                            $amount,
                            $type === MonetizationPayout::TYPE_CREATOR_FUND
                                ? 'Creator Fund'
                                : 'Partage revenus publicitaires',
                            $payout
                        );
                    }
                }

                $created++;
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'already_processed' => false,
        ];
    }

    private function getPoolAmount(string $type): int
    {
        if (!$this->isTypeEnabled($type)) {
            return 0;
        }

        if ($type === MonetizationPayout::TYPE_CREATOR_FUND) {
            return (int) setting('creator_fund_pool_amount', 0);
        }

        $pool = (int) setting('ads_revenue_pool_amount', 0);
        $sharePercent = (float) setting('ads_revenue_share_percent', 0);

        return (int) floor($pool * ($sharePercent / 100));
    }

    private function isTypeEnabled(string $type): bool
    {
        if (!(bool) setting('monetization_enabled', true)) {
            return false;
        }

        if ($type === MonetizationPayout::TYPE_CREATOR_FUND) {
            return (bool) setting('creator_fund_enabled', true);
        }

        return (bool) setting('ads_revenue_enabled', true);
    }
}
