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
        $faker = fake('fr_FR');
        $confessionStories = $this->confessionStories();
        $commentStories = $this->commentStories();
        $chatLines = $this->chatLines();
        $groupNames = $this->groupNames();
        $groupDescriptions = $this->groupDescriptions();
        $userBios = $this->userBios();

        $this->command->info('Creating demo users...');
        $users = User::factory()
            ->count($count)
            ->state(function () use ($faker, $userBios) {
                $first = $faker->firstName();
                $last = $faker->lastName();
                return [
                    'first_name' => $first,
                    'last_name' => $last,
                    'username' => Str::slug($first . $last) . $faker->numberBetween(10, 999),
                    'bio' => $faker->boolean(80)
                        ? $faker->randomElement($userBios)
                        : null,
                    'wallet_balance' => $faker->numberBetween(0, 200000),
                ];
            })
            ->create();

        $this->command->info('Ensuring demo gifts...');
        $this->seedGifts($count);

        $this->command->info('Creating demo confessions...');
        $confessions = Confession::factory()
            ->count($count)
            ->state(function () use ($users, $confessionStories, $faker) {
                $author = $users->random();
                $type = $faker->randomElement([Confession::TYPE_PUBLIC, Confession::TYPE_PRIVATE]);
                $recipientId = null;
                if ($type === Confession::TYPE_PRIVATE) {
                    $recipientId = $users->where('id', '!=', $author->id)->random()->id;
                }

                return [
                    'author_id' => $author->id,
                    'recipient_id' => $recipientId,
                    'type' => $type,
                    'content' => $faker->randomElement($confessionStories),
                ];
            })
            ->create();

        $this->command->info('Creating demo confession comments...');
        ConfessionComment::factory()
            ->count($count)
            ->state(function () use ($users, $confessions, $commentStories, $faker) {
                return [
                    'confession_id' => $confessions->random()->id,
                    'author_id' => $users->random()->id,
                    'content' => $faker->randomElement($commentStories),
                ];
            })
            ->create();

        $this->command->info('Creating demo conversations (streaks included)...');
        $conversations = $this->createConversations($users, $count);

        $this->command->info('Creating demo chat messages...');
        $this->createChatMessages($conversations, $chatLines);

        $this->command->info('Creating demo groups...');
        $groups = Group::factory()
            ->count($count)
            ->state(function () use ($users, $groupNames, $groupDescriptions, $faker) {
                $creator = $users->random();
                return [
                    'creator_id' => $creator->id,
                    'name' => $faker->randomElement($groupNames),
                    'description' => $faker->randomElement($groupDescriptions),
                ];
            })
            ->create();

        $this->command->info('Creating demo group members and messages...');
        $this->createGroupMembersAndMessages($groups, $users, $count, $chatLines);

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

    private function createChatMessages(Collection $conversations, array $chatLines): void
    {
        $faker = fake('fr_FR');
        foreach ($conversations as $conversation) {
            $senderId = $faker->boolean()
                ? $conversation->participant_one_id
                : $conversation->participant_two_id;

            $type = $faker->randomElement([
                ChatMessage::TYPE_TEXT,
                ChatMessage::TYPE_TEXT,
                ChatMessage::TYPE_TEXT,
                ChatMessage::TYPE_IMAGE,
                ChatMessage::TYPE_VOICE,
                ChatMessage::TYPE_VIDEO,
            ]);
            $message = ChatMessage::factory()->state([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'type' => $type,
                'content' => $type === ChatMessage::TYPE_TEXT
                    ? $faker->randomElement($chatLines)
                    : null,
            ])->create();

            $conversation->update([
                'last_message_at' => $message->created_at,
                'message_count' => $conversation->message_count + 1,
            ]);
        }
    }

    private function createGroupMembersAndMessages(
        Collection $groups,
        Collection $users,
        int $count,
        array $chatLines
    ): void
    {
        $faker = fake('fr_FR');
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
                $type = $faker->randomElement([
                    GroupMessage::TYPE_TEXT,
                    GroupMessage::TYPE_TEXT,
                    GroupMessage::TYPE_TEXT,
                    GroupMessage::TYPE_IMAGE,
                    GroupMessage::TYPE_VOICE,
                    GroupMessage::TYPE_VIDEO,
                ]);
                $message = GroupMessage::factory()->state([
                    'group_id' => $group->id,
                    'sender_id' => $senderId,
                    'type' => $type,
                    'content' => $type === GroupMessage::TYPE_TEXT
                        ? $faker->randomElement($chatLines)
                        : null,
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
        $faker = fake('fr_FR');

        foreach ($giftTransactions->take((int) floor($count / 2)) as $transaction) {
            WalletTransaction::factory()->state(function () use ($transaction, $faker) {
                return [
                    'user_id' => $transaction->recipient_id,
                    'type' => WalletTransaction::TYPE_CREDIT,
                    'amount' => $transaction->net_amount,
                    'balance_before' => $faker->numberBetween(0, 200000),
                    'balance_after' => $faker->numberBetween(200000, 400000),
                    'description' => 'Cadeau reçu',
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

    private function confessionStories(): array
    {
        return [
            'Hier soir, j’ai croisé un inconnu dans le métro. On a parlé dix minutes, et j’ai eu l’impression de connaître sa vie entière.',
            'J’ai lancé une petite boutique en ligne avec 50 000 FCFA, et c’est devenu mon job principal en trois mois.',
            'J’ai avoué un secret à mon meilleur ami… il a souri et m’a dit qu’il le savait déjà depuis des années.',
            'Elle a annulé notre rendez-vous, puis a réapparu avec une lettre qui a tout changé.',
            'J’ai perdu mon téléphone dans un taxi. Le chauffeur est revenu le lendemain avec un cadeau.',
            'Je pensais détester mon quartier. Un jour, j’ai trouvé un café caché et je m’y sens enfin chez moi.',
            'Mon voisin chante tous les soirs. Aujourd’hui j’ai frappé à sa porte pour l’enregistrer.',
            'J’ai envoyé un message par erreur, et c’est devenu la meilleure conversation de ma semaine.',
            'Quand j’ai arrêté de répondre, j’ai découvert qui tenait vraiment à moi.',
            'On m’a offert un billet pour un concert. C’était mon groupe préféré et personne ne le savait.',
            'J’ai trouvé une boîte de photos dans un marché. L’une d’elles était ma mère à 20 ans.',
            'Ils m’ont dit que c’était impossible. J’ai quand même essayé, et ça a marché.',
            'Je me suis excusé après deux ans de silence. Sa réponse était un simple “merci”.',
            'J’ai quitté mon travail sans plan B. Le lendemain, j’ai reçu l’appel que j’attendais.',
            'Un ami m’a prêté sa caméra. J’ai filmé notre quartier, et tout le monde s’est reconnu.',
        ];
    }

    private function commentStories(): array
    {
        return [
            'Franchement, cette histoire m’a touché.',
            'Je suis passé par la même chose, courage.',
            'C’est incroyable, on dirait un film.',
            'J’adore l’énergie de ce post.',
            'Merci pour ce partage, ça fait du bien.',
            'Ça me rappelle quelqu’un…',
            'Tu devrais en faire une série.',
            'Respect, ce n’était pas facile à dire.',
            'J’espère que tout ira mieux pour toi.',
            'C’est beau ce que tu décris.',
        ];
    }

    private function chatLines(): array
    {
        return [
            'Tu as vu la vidéo ? Elle est folle.',
            'Je passe chez toi dans 20 minutes.',
            'Ce soir on se fait un vocal ?',
            'Je viens de trouver un plan incroyable.',
            'Dis-moi la vérité : tu savais déjà ?',
            'On se capte au café vers 18h.',
            'J’ai besoin d’un conseil rapide.',
            'Tu gères grave, continue comme ça.',
            'J’ai rigolé tout seul en lisant ça.',
            'Tu peux relire ce message quand tu veux.',
        ];
    }

    private function groupNames(): array
    {
        return [
            'Les secrets de la ville',
            'Aventures du soir',
            'Le cercle des ambitieux',
            'Histoires vraies, sans filtre',
            'Créatifs du quotidien',
            'Le salon des confidences',
            'Vibes positives',
            'Les insomniaques',
            'Café du matin',
            'Projet 2026',
        ];
    }

    private function groupDescriptions(): array
    {
        return [
            'On partage nos histoires les plus inattendues.',
            'Des idées, des projets, et beaucoup d’entraide.',
            'Ici, on parle vrai, sans jugement.',
            'Un espace calme pour écrire et se soutenir.',
            'On échange nos meilleurs plans et nos rêves.',
            'Une communauté pour les gens qui n’abandonnent jamais.',
            'Des discussions qui donnent envie d’avancer.',
            'Les rencontres et les opportunités se créent ici.',
            'Des récits courts, mais intenses.',
            'Un groupe pour celles et ceux qui osent.',
        ];
    }

    private function userBios(): array
    {
        return [
            'Passionné de voyages et d’histoires vraies.',
            'Créatrice de contenu, toujours en quête d’inspiration.',
            'Fan de musique, de cinéma et de bons cafés.',
            'Je raconte ce que les autres n’osent pas dire.',
            'Entrepreneur en devenir, ici pour apprendre.',
            'Curieux de tout, surtout des gens.',
            'J’aime les discussions profondes à 2h du matin.',
            'Photographe amateur, chasseuse d’instants.',
            'Si tu lis ça, on est déjà amis.',
            'Team nuits blanches et idées brillantes.',
        ];
    }
}
