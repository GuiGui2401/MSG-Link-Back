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
        $reset = 0;

        foreach ($conversations as $conversation) {
            // Si le streak n'a pas été mis à jour depuis plus de 48h, le réinitialiser
            if ($conversation->streak_updated_at && 
                $conversation->streak_updated_at->diffInHours(now()) > 48) {
                
                $conversation->resetStreak();
                $reset++;
                
                Log::info('Streak reset', [
                    'conversation_id' => $conversation->id,
                    'previous_streak' => $conversation->streak_count,
                ]);
            } else {
                // Mettre à jour le niveau de flamme selon le streak
                $oldLevel = $conversation->flame_level;
                $newLevel = $this->calculateFlameLevel($conversation->streak_count);
                
                if ($oldLevel !== $newLevel) {
                    $conversation->update(['flame_level' => $newLevel]);
                    $updated++;
                }
            }
        }

        $this->info("Updated {$updated} flame levels, reset {$reset} streaks.");

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
