<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FlameStatusSeeder extends Seeder
{
    private const MAIN_USERNAME = 'infodjstar7';

    /**
     * Seed helper data to validate the flame/streak UI
     */
    public function run(): void
    {
        $mainUser = $this->ensureUser(self::MAIN_USERNAME, [
            'first_name' => 'Info',
            'last_name' => 'Djstar',
            'email' => 'infodjstar7@example.com',
            'wallet_balance' => 15000,
        ]);

        $reference = Carbon::now();

        $scenarios = [
            [
                'username' => 'flame_purple_5days',
                'flame_level' => Conversation::FLAME_PURPLE,
                'streak_count' => 32,
                'last_message_at' => $reference->copy()->subMinutes(15),
                'message_history' => [
                    // Day -5
                    ['timestamp' => $reference->copy()->subDays(5)->subHours(8), 'sender' => 'other', 'content' => 'Tu veux qu’on garde la flamme pendant 5 jours d’affilée ?'],
                    ['timestamp' => $reference->copy()->subDays(5)->subHours(7), 'sender' => 'main', 'content' => 'Oui, je t’envoie un message tous les matins.'],
                    // Day -4
                    ['timestamp' => $reference->copy()->subDays(4)->subHours(6), 'sender' => 'other', 'content' => 'Jour 2, t’as vu la story que j’ai postée ?'],
                    ['timestamp' => $reference->copy()->subDays(4)->subHours(5), 'sender' => 'main', 'content' => 'Je suis sur le coup, je t’écris dès que j’ai fini une réunion.'],
                    // Day -3
                    ['timestamp' => $reference->copy()->subDays(3)->subHours(4), 'sender' => 'other', 'content' => 'Jour 3, on tient la cadence ?'],
                    ['timestamp' => $reference->copy()->subDays(3)->subHours(3), 'sender' => 'main', 'content' => 'Toujours prêt, je t’envoie un TikTok sympa.'],
                    // Day -2
                    ['timestamp' => $reference->copy()->subDays(2)->subHours(4), 'sender' => 'other', 'content' => 'Jour 4, on va finir la semaine sur une bonne note.'],
                    ['timestamp' => $reference->copy()->subDays(2)->subHours(2), 'sender' => 'main', 'content' => 'Je suis chaud, la flamme est solide.'],
                    // Day -1
                    ['timestamp' => $reference->copy()->subDays(1)->subHours(3), 'sender' => 'other', 'content' => 'Jour 5, c’est presque un record.'],
                    ['timestamp' => $reference->copy()->subDays(1)->subHours(1), 'sender' => 'main', 'content' => 'Je verrouille ma réponse avant d’aller dormir.'],
                    // Now
                    ['timestamp' => $reference->copy()->subMinutes(15), 'sender' => 'other', 'content' => 'Demain on remet ça ?', 'is_read' => false],
                ],
            ],
            [
                'username' => 'flame_orange_expired',
                'flame_level' => Conversation::FLAME_ORANGE,
                'streak_count' => 6,
                'last_message_at' => $reference->copy()->subDays(3)->subHours(2),
                'message_history' => [
                    ['timestamp' => $reference->copy()->subDays(4)->subHours(5), 'sender' => 'other', 'content' => 'Tu te souviens de notre streak ?'],
                    ['timestamp' => $reference->copy()->subDays(4)->subHours(4), 'sender' => 'main', 'content' => 'Oui, on avait dépassé les 6 jours.'],
                    ['timestamp' => $reference->copy()->subDays(3)->subHours(3), 'sender' => 'other', 'content' => 'Je t’envoie un message pour rester dans le rythme.'],
                    ['timestamp' => $reference->copy()->subDays(3)->subHours(2), 'sender' => 'main', 'content' => 'Désolé, je n’ai pas eu le temps d’enchaîner.'],
                ],
            ],
            [
                'username' => 'flame_yellow_single',
                'flame_level' => Conversation::FLAME_YELLOW,
                'streak_count' => 1,
                'last_message_at' => $reference->copy()->subHours(2),
                'message_history' => [
                    ['timestamp' => $reference->copy()->subHours(5), 'sender' => 'other', 'content' => 'Tu veux essayer une petite flamme aujourd’hui ?'],
                    ['timestamp' => $reference->copy()->subHours(4), 'sender' => 'main', 'content' => 'Oui, je suis parti pour un seul jour pour l’instant.'],
                    ['timestamp' => $reference->copy()->subHours(2), 'sender' => 'other', 'content' => 'Alors on valide cette première journée ?', 'is_read' => false],
                ],
            ],
            [
                'username' => 'flame_none',
                'flame_level' => Conversation::FLAME_NONE,
                'streak_count' => 0,
                'last_message_at' => $reference->copy()->subDays(7),
                'message_history' => [
                    ['timestamp' => $reference->copy()->subDays(8)->subHours(2), 'sender' => 'other', 'content' => 'Tu es revenu sur le jeu ?'],
                    ['timestamp' => $reference->copy()->subDays(8)->subHours(1), 'sender' => 'main', 'content' => 'Pas encore, je te tiens au courant.'],
                    ['timestamp' => $reference->copy()->subDays(7), 'sender' => 'other', 'content' => 'On fera une autre tentative une fois dispo.'],
                ],
            ],
            [
                'username' => 'flame_orange_gap',
                'flame_level' => Conversation::FLAME_ORANGE,
                'streak_count' => 4,
                'last_message_at' => $reference->copy()->subHours(26),
                'message_history' => [
                    ['timestamp' => $reference->copy()->subDays(2)->subHours(3), 'sender' => 'other', 'content' => 'Tu veux qu’on bloque un créneau la semaine prochaine ?'],
                    ['timestamp' => $reference->copy()->subDays(2)->subHours(2), 'sender' => 'main', 'content' => 'Oui, je te confirme ça demain.'],
                    ['timestamp' => $reference->copy()->subDays(1)->subHours(4), 'sender' => 'other', 'content' => 'Tu es dispo pour continuer la flamme aujourd’hui ?'],
                    ['timestamp' => $reference->copy()->subDays(1)->subHours(3), 'sender' => 'main', 'content' => 'Je répondrai après le boulot.'],
                    ['timestamp' => $reference->copy()->subHours(26), 'sender' => 'other', 'content' => 'Pas de nouvelle depuis 24h, la flamme va se refroidir.', 'is_read' => false],
                ],
            ],
        ];

        foreach ($scenarios as $scenario) {
            $other = $this->ensureUser($scenario['username'], [
                'first_name' => ucfirst(str_replace('_', ' ', $scenario['username'])),
                'last_name' => 'Tester',
                'email' => "{$scenario['username']}@example.test",
                'wallet_balance' => 5000,
            ]);

            $this->createFlameConversation($mainUser, $other, $scenario);
        }
    }

    private function ensureUser(string $username, array $attributes): User
    {
        $basePhone = '600' . str_pad(abs(crc32($username)) % 10000000, 7, '0', STR_PAD_LEFT);

        return User::updateOrCreate(
            ['username' => $username],
            array_merge([
                'first_name' => Str::title($username),
                'last_name' => 'Bot',
                'email' => "{$username}@example.test",
                'phone' => $basePhone,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'wallet_balance' => 10000,
                'last_seen_at' => Carbon::now(),
                'role' => 'user',
                'is_verified' => true,
            ], $attributes)
        );
    }

    /**
     * Build a deterministic conversation for each flame scenario.
     */
    private function createFlameConversation(User $main, User $other, array $scenario): void
    {
        $minId = min($main->id, $other->id);
        $maxId = max($main->id, $other->id);

        Conversation::between($minId, $maxId)->delete();

        $history = $this->buildMessageHistory($scenario);

        usort($history, fn ($a, $b) => $a['timestamp']->getTimestamp() <=> $b['timestamp']->getTimestamp());

        $firstMessage = reset($history);
        $lastMessage = end($history);

        $conversation = Conversation::create([
            'participant_one_id' => $minId,
            'participant_two_id' => $maxId,
            'last_message_at' => $lastMessage['timestamp'],
            'streak_count' => $scenario['streak_count'],
            'streak_updated_at' => $lastMessage['timestamp'],
            'flame_level' => $scenario['flame_level'],
            'message_count' => 0,
            'created_at' => $firstMessage['timestamp'],
            'updated_at' => Carbon::now(),
        ]);

        foreach ($history as $entry) {
            $this->createChatMessage($conversation->id, $entry, $main, $other);
        }

        $conversation->update([
            'message_count' => count($history),
            'last_message_at' => $lastMessage['timestamp'],
        ]);
    }

    private function buildMessageHistory(array $scenario): array
    {
        if (! empty($scenario['message_history'])) {
            return $scenario['message_history'];
        }

        $lastMessageAt = $scenario['last_message_at'] ?? Carbon::now();

        return [
            [
                'timestamp' => $lastMessageAt->copy()->subMinutes(12),
                'sender' => 'main',
                'content' => 'Salut, tu es dispo ?',
            ],
            [
                'timestamp' => $lastMessageAt,
                'sender' => 'other',
                'content' => 'Oui, je suis dans l’équipe, prêt pour la flame !',
                'is_read' => false,
            ],
        ];
    }

    private function createChatMessage(int $conversationId, array $entry, User $main, User $other): void
    {
        $isRead = $entry['is_read'] ?? true;

        ChatMessage::create([
            'conversation_id' => $conversationId,
            'sender_id' => $entry['sender'] === 'main' ? $main->id : $other->id,
            'content' => $entry['content'],
            'type' => ChatMessage::TYPE_TEXT,
            'is_read' => $isRead,
            'read_at' => $entry['read_at'] ?? ($isRead ? $entry['timestamp']->copy()->addMinutes($entry['read_offset_minutes'] ?? 1) : null),
            'created_at' => $entry['timestamp'],
            'updated_at' => $entry['timestamp'],
        ]);
    }
}
