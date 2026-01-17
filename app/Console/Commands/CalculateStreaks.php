<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateStreaks extends Command
{
    protected $signature = 'chat:calculate-streaks';

    protected $description = 'Calculate and update conversation streaks (Flame system)';

    public function handle(): int
    {
        $this->info('Calculating conversation streaks...');

        $conversations = Conversation::withStreak()->get();

        $updated = 0;
        $decremented = 0;
        $reset = 0;

        foreach ($conversations as $conversation) {
            if (!$conversation->streak_updated_at) {
                continue;
            }

            $now = now();
            $hoursSinceUpdate = $conversation->streak_updated_at->diffInHours($now);

            // Si plus de 24h sans mise à jour, décrémenter
            if ($hoursSinceUpdate > 24) {
                $daysMissed = floor($hoursSinceUpdate / 24);
                $oldStreakCount = $conversation->streak_count;

                // Décrémenter le streak en fonction du nombre de jours manqués
                $newStreakCount = max(0, $conversation->streak_count - $daysMissed);

                if ($newStreakCount !== $oldStreakCount) {
                    $conversation->update([
                        'streak_count' => $newStreakCount,
                        'streak_updated_at' => $now,
                        'flame_level' => $this->calculateFlameLevel($newStreakCount),
                    ]);

                    if ($newStreakCount === 0) {
                        $reset++;
                        Log::info('Streak reset to 0', [
                            'conversation_id' => $conversation->id,
                            'previous_streak' => $oldStreakCount,
                            'days_missed' => $daysMissed,
                        ]);
                    } else {
                        $decremented++;
                        Log::info('Streak decremented', [
                            'conversation_id' => $conversation->id,
                            'previous_streak' => $oldStreakCount,
                            'new_streak' => $newStreakCount,
                            'days_missed' => $daysMissed,
                        ]);
                    }
                }
            } else {
                // Mettre à jour le niveau de flamme selon le streak si nécessaire
                $oldLevel = $conversation->flame_level;
                $newLevel = $this->calculateFlameLevel($conversation->streak_count);

                if ($oldLevel !== $newLevel) {
                    $conversation->update(['flame_level' => $newLevel]);
                    $updated++;
                }
            }
        }

        $this->info("Updated {$updated} flame levels, decremented {$decremented} streaks, reset {$reset} streaks to 0.");

        return Command::SUCCESS;
    }

    private function calculateFlameLevel(int $streakCount): string
    {
        return match (true) {
            $streakCount >= 30 => Conversation::FLAME_PURPLE,
            $streakCount >= 7 => Conversation::FLAME_ORANGE,
            $streakCount >= 2 => Conversation::FLAME_YELLOW,
            default => Conversation::FLAME_NONE,
        };
    }
}
