<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\Conversation;
use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\PostPromotion;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $count = 50;

        $this->command->info('Creating demo users...');
        $users = User::factory()
            ->count($count)
            ->state(fn () => [
                'wallet_balance' => fake()->numberBetween(0, 200000),
            ])
            ->create();

        $this->command->info('Ensuring demo gifts...');
        $this->seedGifts($count);

        $this->command->info('Creating demo confessions...');
        $confessions = Confession::factory()
            ->count($count)
            ->state(function () use ($users) {
                $author = $users->random();
                $type = fake()->randomElement([Confession::TYPE_PUBLIC, Confession::TYPE_PRIVATE]);
                $recipientId = $type === Confession::TYPE_PRIVATE
                    ? $this->pickDifferentUserId($users, $author->id)
                    : null;

                return [
                    'author_id' => $author->id,
                    'recipient_id' => $recipientId,
                    'type' => $type,
                ];
            })
            ->create();

        $this->command->info('Creating demo confession comments...');
        ConfessionComment::factory()
            ->count($count)
            ->state(function () use ($users, $confessions) {
                return [
                    'confession_id' => $confessions->random()->id,
                    'author_id' => $users->random()->id,
                ];
            })
            ->create();

        $this->command->info('Creating demo conversations (streaks included)...');
        $conversations = $this->createConversations($users, $count);

        $this->command->info('Creating demo chat messages...');
        $this->createChatMessages($conversations);

        $this->command->info('Creating demo groups...');
        $groups = Group::factory()
            ->count($count)
            ->state(function () use ($users) {
                $creator = $users->random();
                return [
                    'creator_id' => $creator->id,
                ];
            })
            ->create();

        $this->command->info('Creating demo group members and messages...');
        $this->createGroupMembersAndMessages($groups, $users, $count);

        $this->command->info('Creating demo promotions...');
        PostPromotion::factory()
            ->count($count)
            ->state(function () use ($confessions) {
                $confession = $confessions->random();
                return [
                    'confession_id' => $confession->id,
                    'user_id' => $confession->author_id,
                ];
            })
            ->create();

        $this->command->info('Creating demo gift transactions...');
        $giftTransactions = $this->createGiftTransactions($users, $conversations, $count);

        $this->command->info('Creating demo wallet transactions...');
        $this->createWalletTransactions($users, $giftTransactions, $count);

        $this->command->info('Demo data seeding completed!');
    }

    private function seedGifts(int $targetCount): void
    {
        $currentCount = Gift::count();
        if ($currentCount >= $targetCount) {
            return;
        }

        Gift::factory()->count($targetCount - $currentCount)->create();
    }

    private function createConversations(Collection $users, int $count): Collection
    {
        $pairs = [];
        $conversations = collect();

        while ($conversations->count() < $count) {
            $userA = $users->random();
            $userB = $users->random();

            if ($userA->id === $userB->id) {
                continue;
            }

            $minId = min($userA->id, $userB->id);
            $maxId = max($userA->id, $userB->id);
            $key = "{$minId}:{$maxId}";

            if (isset($pairs[$key])) {
                continue;
            }

            $pairs[$key] = true;

            $streakCount = $this->pickStreakCount();
            $streakUpdatedAt = $streakCount > 0
                ? Carbon::now()->subHours(fake()->numberBetween(1, 20))
                : null;

            $conversation = Conversation::factory()->state([
                'participant_one_id' => $minId,
                'participant_two_id' => $maxId,
                'streak_count' => $streakCount,
                'streak_updated_at' => $streakUpdatedAt,
                'flame_level' => $this->calculateFlameLevel($streakCount),
            ])->create();

            $conversations->push($conversation);
        }

        return $conversations;
    }

    private function createChatMessages(Collection $conversations): void
    {
        foreach ($conversations as $conversation) {
            $senderId = fake()->boolean()
                ? $conversation->participant_one_id
                : $conversation->participant_two_id;

            $message = ChatMessage::factory()->state([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
            ])->create();

            $conversation->update([
                'last_message_at' => $message->created_at,
                'message_count' => $conversation->message_count + 1,
            ]);
        }
    }

    private function createGroupMembersAndMessages(Collection $groups, Collection $users, int $count): void
    {
        $groupMessagesCreated = 0;

        foreach ($groups as $group) {
            $members = $users->random(min(5, $users->count()));
            $creatorId = $group->creator_id;

            if (!$members->contains('id', $creatorId)) {
                $members = $members->push($users->firstWhere('id', $creatorId));
            }

            $members = $members->unique('id')->values();

            foreach ($members as $member) {
                GroupMember::factory()->state([
                    'group_id' => $group->id,
                    'user_id' => $member->id,
                    'role' => $member->id === $creatorId
                        ? GroupMember::ROLE_ADMIN
                        : GroupMember::ROLE_MEMBER,
                ])->create();
            }

            $group->update([
                'members_count' => $members->count(),
            ]);

            if ($groupMessagesCreated < $count) {
                $senderId = $members->random()->id;
                $message = GroupMessage::factory()->state([
                    'group_id' => $group->id,
                    'sender_id' => $senderId,
                ])->create();

                $groupMessagesCreated++;

                $group->update([
                    'messages_count' => $group->messages_count + 1,
                    'last_message_at' => $message->created_at,
                ]);
            }
        }
    }

    private function createGiftTransactions(Collection $users, Collection $conversations, int $count): Collection
    {
        $giftTransactions = collect();

        for ($i = 0; $i < $count; $i++) {
            $conversation = $conversations->random();
            $senderId = $conversation->participant_one_id;
            $recipientId = $conversation->participant_two_id;

            if (fake()->boolean()) {
                [$senderId, $recipientId] = [$recipientId, $senderId];
            }

            $transaction = GiftTransaction::factory()->state([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
            ])->create();

            $giftTransactions->push($transaction);
        }

        return $giftTransactions;
    }

    private function createWalletTransactions(Collection $users, Collection $giftTransactions, int $count): void
    {
        $remaining = $count;

        foreach ($giftTransactions->take((int) floor($count / 2)) as $transaction) {
            WalletTransaction::factory()->state(function () use ($transaction) {
                return [
                    'user_id' => $transaction->recipient_id,
                    'type' => WalletTransaction::TYPE_CREDIT,
                    'amount' => $transaction->net_amount,
                    'balance_before' => fake()->numberBetween(0, 200000),
                    'balance_after' => fake()->numberBetween(200000, 400000),
                    'description' => 'Cadeau recu',
                    'transactionable_type' => GiftTransaction::class,
                    'transactionable_id' => $transaction->id,
                    'reference' => 'wallet_' . Str::uuid(),
                ];
            })->create();
            $remaining--;
        }

        if ($remaining <= 0) {
            return;
        }

        WalletTransaction::factory()
            ->count($remaining)
            ->state(function () use ($users) {
                return [
                    'user_id' => $users->random()->id,
                ];
            })
            ->create();
    }

    private function pickDifferentUserId(Collection $users, int $excludedId): int
    {
        return $users->where('id', '!=', $excludedId)->random()->id;
    }

    private function pickStreakCount(): int
    {
        return fake()->randomElement([0, 1, 2, 3, 5, 7, 10, 15, 30, 50, 100]);
    }

    private function calculateFlameLevel(int $streakCount): string
    {
        if ($streakCount >= 30) {
            return Conversation::FLAME_PURPLE;
        }

        if ($streakCount >= 7) {
            return Conversation::FLAME_ORANGE;
        }

        if ($streakCount >= 2) {
            return Conversation::FLAME_YELLOW;
        }

        return Conversation::FLAME_NONE;
    }
}
