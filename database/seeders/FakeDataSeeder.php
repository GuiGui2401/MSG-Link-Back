<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AnonymousMessage;
use App\Models\Confession;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\PremiumSubscription;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Models\Report;
use App\Models\Notification;
use App\Models\Story;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FakeDataSeeder extends Seeder
{
    /**
     * Seed fake data for testing the dashboard
     */
    public function run(): void
    {
        $this->command->info('Creating fake users...');
        $users = $this->createUsers();

        $this->command->info('Creating stories (24h expiration)...');
        $this->createStories($users);

        $this->command->info('Creating anonymous messages...');
        $this->createAnonymousMessages($users);

        $this->command->info('Creating confessions...');
        $this->createConfessions($users);

        $this->command->info('Creating groups...');
        $this->createGroups($users);

        $this->command->info('Creating conversations and chat messages...');
        $conversations = $this->createConversations($users);

        $this->command->info('Creating gift transactions...');
        $this->createGiftTransactions($users, $conversations);

        $this->command->info('Creating premium subscriptions...');
        $this->createPremiumSubscriptions($users, $conversations);

        $this->command->info('Creating payments...');
        $this->createPayments($users);

        $this->command->info('Creating withdrawals...');
        $this->createWithdrawals($users);

        $this->command->info('Creating reports...');
        $this->createReports($users);

        $this->command->info('Creating notifications...');
        $this->createNotifications($users);

        $this->command->info('Fake data seeding completed!');
    }

    /**
     * Create fake users
     */
    private function createUsers(): array
    {
        $users = [];

        // Prénoms et noms africains/béninois
        $firstNames = [
            'Kofi', 'Ama', 'Kwame', 'Akua', 'Yao', 'Afi', 'Kojo', 'Adjoa',
            'Mensah', 'Abla', 'Kodjo', 'Akossiwa', 'Edem', 'Esi', 'Komlan',
            'Ablavi', 'Sena', 'Ayaba', 'Kossi', 'Dédé', 'Yaovi', 'Mawuli',
            'Femi', 'Amara', 'Chidi', 'Ngozi', 'Emeka', 'Adaeze', 'Obinna',
            'Chiamaka', 'Tunde', 'Folake', 'Segun', 'Yewande', 'Adebayo'
        ];

        $lastNames = [
            'Agossou', 'Hounnou', 'Dossou', 'Ahounou', 'Kpodo', 'Assogba',
            'Houngbo', 'Adjovi', 'Gnansounou', 'Zannou', 'Houénou', 'Soglo',
            'Gangbo', 'Quenum', 'Akplogan', 'Behanzin', 'Topanou', 'Sossou',
            'Akakpo', 'Amoussou', 'Okonkwo', 'Adeyemi', 'Mensah', 'Ofori',
            'Asante', 'Boateng', 'Owusu', 'Nkrumah', 'Diallo', 'Keita'
        ];

        $bios = [
            'Passionné(e) de musique et de voyages',
            'Étudiant(e) en informatique',
            'Entrepreneur(se) dans le digital',
            'Amateur(trice) de bonne cuisine',
            'Fan de football et de NBA',
            'Artiste dans l\'âme',
            'Toujours positif(ve)',
            'La vie est belle',
            'En quête d\'aventures',
            'Photographe amateur',
            null,
            null,
        ];

        // Créer 100 utilisateurs normaux
        for ($i = 0; $i < 100; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $createdAt = Carbon::now()->subDays(rand(1, 90));

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => strtolower($firstName) . rand(100, 9999),
                'email' => strtolower($firstName) . rand(100, 9999) . '@example.com',
                'phone' => '6' . rand(10000000, 99999999),
                'email_verified_at' => rand(0, 10) > 2 ? $createdAt->copy()->addHours(rand(1, 24)) : null,
                'password' => Hash::make('password'),
                'avatar' => null,
                'bio' => $bios[array_rand($bios)],
                'is_verified' => rand(0, 10) > 7,
                'is_banned' => false,
                'wallet_balance' => rand(0, 50000),
                'last_seen_at' => Carbon::now()->subMinutes(rand(1, 10080)),
                'settings' => [],
                'role' => 'user',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $users[] = $user;
        }

        // Créer quelques utilisateurs bannis
        for ($i = 0; $i < 5; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $createdAt = Carbon::now()->subDays(rand(30, 120));

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => 'banned_' . strtolower($firstName) . rand(100, 999),
                'email' => 'banned_' . strtolower($firstName) . rand(100, 999) . '@example.com',
                'phone' => '6' . rand(10000000, 99999999),
                'email_verified_at' => $createdAt,
                'password' => Hash::make('password'),
                'bio' => null,
                'is_banned' => true,
                'banned_reason' => ['Spam', 'Harcèlement', 'Contenu inapproprié', 'Usurpation d\'identité'][rand(0, 3)],
                'banned_at' => Carbon::now()->subDays(rand(1, 30)),
                'wallet_balance' => 0,
                'last_seen_at' => Carbon::now()->subDays(rand(1, 30)),
                'role' => 'user',
                'created_at' => $createdAt,
                'updated_at' => Carbon::now(),
            ]);

            $users[] = $user;
        }

        return $users;
    }

    /**
     * Create stories (24 hour expiration)
     */
    private function createStories(array $users): void
    {
        $storyTexts = [
            'Bonne journée à tous ! ☀️',
            'En train de travailler sur un nouveau projet 💻',
            'Belle vue aujourd\'hui 🌅',
            'Moment de détente ☕',
            'Pensée du jour : Crois en toi !',
            'Nouvelle aventure commence 🚀',
            'Profitez de chaque instant ✨',
            'La vie est belle ! 🌟',
            'Merci pour tout le soutien 🙏',
            'Nouveau défi accepté 💪',
            'Inspiration du matin 🌄',
            'Mode créatif activé 🎨',
            'Gratitude pour cette journée 💫',
            'Toujours positif ! 😊',
            'En route vers mes objectifs 🎯',
            'Moments précieux à partager ❤️',
            'L\'aventure continue ! 🌍',
            'Restez motivés ! 🔥',
            'Petit bonheur du jour 🌸',
            'Merci la vie ! 🌈',
        ];

        $backgroundColors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
            '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B500', '#52BE80',
            '#EC7063', '#5DADE2', '#F4D03F', '#AF7AC5', '#48C9B0',
        ];

        // Create 100 stories with varying expiration times within 24 hours
        for ($i = 0; $i < 100; $i++) {
            $user = $users[array_rand($users)];
            $type = ['text', 'text', 'text', 'image'][rand(0, 3)]; // More text stories for testing

            // Create stories with different ages (from 1 minute ago to 23 hours ago)
            $createdAt = Carbon::now()->subMinutes(rand(1, 1380)); // 0-23 hours ago
            $expiresAt = $createdAt->copy()->addHours(24); // 24 hours from creation

            // Determine if story is still active or expired
            $status = $expiresAt->isFuture() ? 'active' : 'expired';

            $storyData = [
                'user_id' => $user->id,
                'type' => $type,
                'views_count' => rand(0, 200),
                'status' => $status,
                'expires_at' => $expiresAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if ($type === 'text') {
                $storyData['content'] = $storyTexts[array_rand($storyTexts)];
                $storyData['background_color'] = $backgroundColors[array_rand($backgroundColors)];
                $storyData['duration'] = 5; // 5 seconds display
            } else {
                // For image stories (you can add actual images later)
                $storyData['media_url'] = null; // Or use placeholder: 'stories/placeholder.jpg'
                $storyData['duration'] = 5;
            }

            $story = \App\Models\Story::create($storyData);

            // Add some story views
            if ($story->views_count > 0) {
                $viewersCount = min($story->views_count, count($users) - 1);
                $viewers = array_slice($users, 0, $viewersCount);

                foreach ($viewers as $viewer) {
                    if ($viewer->id !== $user->id && rand(0, 100) < 60) { // 60% chance to add view
                        $story->viewedBy()->attach($viewer->id, [
                            'created_at' => $createdAt->copy()->addMinutes(rand(1, 60)),
                            'updated_at' => $createdAt->copy()->addMinutes(rand(1, 60)),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Create groups with members and messages
     */
    private function createGroups(array $users): void
    {
        $groupNames = [
            'Tech Lovers', 'Foodies United', 'Travel Buddies', 'Music Fans',
            'Book Club', 'Fitness Gang', 'Movie Night', 'Game Zone',
            'Art Collective', 'Entrepreneurs Hub', 'Study Group', 'Sports Talk',
            'Photography Club', 'Cooking Masters', 'Fashion Squad', 'Coding Warriors',
            'Dance Crew', 'Writers Circle', 'Yoga & Wellness', 'Pet Lovers',
            'Crypto Traders', 'Startup Ideas', 'Language Exchange', 'DIY Projects',
            'Anime Fans', 'Comedy Central', 'Science Geeks', 'History Buffs',
            'Green Living', 'Investment Club', 'Podcast Lovers', 'Chess Players',
            'Coffee Addicts', 'Night Owls', 'Early Birds', 'Memes Factory',
            'Debate Club', 'Horror Fans', 'Motivation Squad', 'Road Trippers',
            'Beach Lovers', 'Mountain Hikers', 'City Explorers', 'Food Delivery',
            'Weekend Warriors', 'Study Partners', 'Career Growth', 'Self Improvement',
            'Mental Health', 'Positive Vibes',
        ];

        $groupDescriptions = [
            'Un groupe pour discuter et partager',
            'Rejoignez-nous pour des discussions intéressantes',
            'Communauté active et bienveillante',
            'Partagez vos passions avec nous',
            'Ensemble, c\'est mieux !',
            'Bienvenue dans notre communauté',
            null,
            null,
        ];

        $groupMessages = [
            'Salut tout le monde ! 👋',
            'Bienvenue aux nouveaux membres !',
            'Quelqu\'un ici ?',
            'Super groupe ! 😊',
            'J\'adore cette communauté',
            'Qui est actif ce soir ?',
            'Des idées pour ce weekend ?',
            'Merci pour le partage !',
            'Très intéressant !',
            'Je suis d\'accord 👍',
            'Quelqu\'un a essayé ?',
            'Excellente question',
            'Voici mon avis...',
            'Ça me rappelle quelque chose',
            'Trop cool ! 🔥',
            'Haha ! 😂',
            'Vraiment ?',
            'Je ne savais pas',
            'Merci de l\'info',
            'À bientôt !',
        ];

        // Create 50 groups
        for ($i = 0; $i < 50; $i++) {
            $creator = $users[array_rand($users)];
            $createdAt = Carbon::now()->subDays(rand(1, 180));

            $group = \App\Models\Group::create([
                'name' => $groupNames[$i % count($groupNames)] . ($i >= count($groupNames) ? ' ' . ($i + 1) : ''),
                'description' => $groupDescriptions[array_rand($groupDescriptions)],
                'creator_id' => $creator->id,
                'invite_code' => \App\Models\Group::generateInviteCode(),
                'is_public' => rand(0, 10) > 3, // 70% public
                'max_members' => rand(0, 10) > 7 ? \App\Models\Group::MAX_MEMBERS_PREMIUM : \App\Models\Group::MAX_MEMBERS_DEFAULT,
                'members_count' => 0, // Will be updated as we add members
                'messages_count' => 0, // Will be updated as we add messages
                'last_message_at' => null,
                'avatar_url' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Add creator as admin
            \App\Models\GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $creator->id,
                'role' => \App\Models\GroupMember::ROLE_ADMIN,
                'joined_at' => $createdAt,
                'last_read_at' => Carbon::now(),
                'is_muted' => false,
            ]);
            $group->increment('members_count');

            // Add random members (5 to 30 members per group)
            $memberCount = rand(5, 30);
            $memberCount = min($memberCount, $group->max_members - 1, count($users) - 1);

            $shuffledUsers = $users;
            shuffle($shuffledUsers);
            $addedMembers = 0;

            foreach ($shuffledUsers as $user) {
                if ($addedMembers >= $memberCount) break;
                if ($user->id === $creator->id) continue;

                $joinedAt = $createdAt->copy()->addDays(rand(0, 30));

                // Determine role (10% moderator, 90% regular member)
                $role = rand(0, 10) > 9 ? \App\Models\GroupMember::ROLE_MODERATOR : \App\Models\GroupMember::ROLE_MEMBER;

                \App\Models\GroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'joined_at' => $joinedAt,
                    'last_read_at' => Carbon::now()->subHours(rand(0, 48)),
                    'is_muted' => rand(0, 10) > 9, // 10% muted
                ]);
                $group->increment('members_count');
                $addedMembers++;
            }

            // Create messages in the group (10 to 100 messages per group)
            $messageCount = rand(10, 100);
            $groupMembers = \App\Models\GroupMember::where('group_id', $group->id)->get();

            $messageTime = $createdAt->copy()->addHours(rand(1, 24));

            for ($j = 0; $j < $messageCount; $j++) {
                $sender = $groupMembers->random();
                $messageTime = $messageTime->copy()->addMinutes(rand(5, 300));

                if ($messageTime > Carbon::now()) {
                    break;
                }

                \App\Models\GroupMessage::create([
                    'group_id' => $group->id,
                    'sender_id' => $sender->user_id,
                    'content' => $groupMessages[array_rand($groupMessages)],
                    'type' => \App\Models\GroupMessage::TYPE_TEXT,
                    'reply_to_message_id' => null,
                    'created_at' => $messageTime,
                    'updated_at' => $messageTime,
                ]);

                $group->increment('messages_count');
                $group->update(['last_message_at' => $messageTime]);
            }

            $group->refresh();
        }
    }

    /**
     * Create anonymous messages
     */
    private function createAnonymousMessages(array $users): void
    {
        $messages = [
            'Tu es vraiment génial(e), continue comme ça !',
            'J\'aimerais te connaître mieux...',
            'Tu as un sourire magnifique !',
            'Je pense souvent à toi...',
            'Tu es une source d\'inspiration pour moi.',
            'J\'admire ta persévérance.',
            'Tu mérites le meilleur.',
            'Continue de briller !',
            'Tu es plus fort(e) que tu ne le penses.',
            'J\'aime ta façon de voir les choses.',
            'Tu es quelqu\'un d\'exceptionnel.',
            'Ne change jamais !',
            'Tu illumines ma journée.',
            'J\'aimerais qu\'on se parle plus souvent.',
            'Tu as un talent incroyable.',
            'Je crois en toi !',
            'Tu es ma personne préférée.',
            'J\'apprécie vraiment notre amitié.',
            'Tu me rends heureux(se).',
            'Merci d\'être toi.',
        ];

        for ($i = 0; $i < 200; $i++) {
            $sender = $users[array_rand($users)];
            $recipient = $users[array_rand($users)];

            // Éviter de s'envoyer un message à soi-même
            while ($sender->id === $recipient->id) {
                $recipient = $users[array_rand($users)];
            }

            $createdAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));
            $isRead = rand(0, 10) > 3;

            AnonymousMessage::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'content' => $messages[array_rand($messages)],
                'is_read' => $isRead,
                'read_at' => $isRead ? $createdAt->copy()->addMinutes(rand(5, 1440)) : null,
                'is_identity_revealed' => rand(0, 10) > 8,
                'revealed_at' => rand(0, 10) > 8 ? $createdAt->copy()->addDays(rand(1, 7)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * Create confessions
     */
    private function createConfessions(array $users): void
    {
        $confessionContents = [
            'Je t\'aime en secret depuis longtemps...',
            'Tu es la personne la plus belle que je connaisse.',
            'Je rêve de toi chaque nuit.',
            'Mon cœur bat plus fort quand je te vois.',
            'Tu ne sais pas à quel point tu comptes pour moi.',
            'J\'aimerais avoir le courage de te parler.',
            'Tu es mon crush depuis le lycée.',
            'Je pense à toi plus que tu ne l\'imagines.',
            'Tu as changé ma vie sans le savoir.',
            'Je suis tombé(e) amoureux(se) de ton sourire.',
            'Chaque fois que je te vois, mon cœur s\'emballe.',
            'Tu es la raison de mon sourire.',
            'J\'aimerais être plus qu\'un(e) ami(e) pour toi.',
            'Tu mérites tout le bonheur du monde.',
            'Je t\'admire plus que tu ne le sais.',
        ];

        $statuses = ['pending', 'approved', 'approved', 'approved', 'rejected'];

        // Create 250 confessions (many confessions as requested)
        for ($i = 0; $i < 250; $i++) {
            $author = $users[array_rand($users)];
            $recipient = rand(0, 10) > 3 ? $users[array_rand($users)] : null;

            // Éviter de s'envoyer une confession à soi-même
            while ($recipient && $author->id === $recipient->id) {
                $recipient = $users[array_rand($users)];
            }

            $createdAt = Carbon::now()->subDays(rand(0, 45))->subHours(rand(0, 23));
            $status = $statuses[array_rand($statuses)];

            // Get admin users for moderation
            $admins = User::whereIn('role', ['admin', 'superadmin', 'moderator'])->get();
            $moderator = $admins->isNotEmpty() ? $admins->random() : null;

            Confession::create([
                'author_id' => $author->id,
                'recipient_id' => $recipient?->id,
                'content' => $confessionContents[array_rand($confessionContents)],
                'type' => $recipient ? 'private' : 'public',
                'status' => $status,
                'moderated_by' => $status !== 'pending' && $moderator ? $moderator->id : null,
                'moderated_at' => $status !== 'pending' ? $createdAt->copy()->addHours(rand(1, 48)) : null,
                'rejection_reason' => $status === 'rejected' ? 'Contenu inapproprié' : null,
                'is_identity_revealed' => rand(0, 10) > 9,
                'revealed_at' => rand(0, 10) > 9 ? $createdAt->copy()->addDays(rand(1, 5)) : null,
                'likes_count' => rand(0, 150),
                'views_count' => rand(10, 500),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * Create conversations and chat messages
     */
    private function createConversations(array $users): array
    {
        $conversations = [];
        $chatMessages = [
            'Salut, comment ça va ?',
            'Ça va bien et toi ?',
            'Super ! Tu fais quoi ?',
            'Je suis à la maison, tranquille.',
            'On se voit quand ?',
            'Ce weekend si tu veux !',
            'Parfait, à samedi alors !',
            'J\'ai hâte !',
            'Moi aussi !',
            'Bonne nuit',
            'Bonne nuit à toi aussi',
            'Tu me manques...',
            'Toi aussi tu me manques',
            'À demain !',
            'Bisous',
            'Je pense à toi',
            'C\'est gentil',
            'Tu es libre ce soir ?',
            'Oui, pourquoi ?',
            'On pourrait sortir',
        ];

        $flameLevels = ['none', 'none', 'none', 'yellow', 'yellow', 'orange', 'purple'];

        // Créer 40 conversations
        for ($i = 0; $i < 40; $i++) {
            $participant1 = $users[array_rand($users)];
            $participant2 = $users[array_rand($users)];

            // Éviter une conversation avec soi-même
            while ($participant1->id === $participant2->id) {
                $participant2 = $users[array_rand($users)];
            }

            // Vérifier qu'une conversation n'existe pas déjà
            $exists = Conversation::where(function ($q) use ($participant1, $participant2) {
                $q->where('participant_one_id', $participant1->id)
                    ->where('participant_two_id', $participant2->id);
            })->orWhere(function ($q) use ($participant1, $participant2) {
                $q->where('participant_one_id', $participant2->id)
                    ->where('participant_two_id', $participant1->id);
            })->exists();

            if ($exists) {
                continue;
            }

            $createdAt = Carbon::now()->subDays(rand(1, 60));
            $messageCount = rand(5, 50);
            $flameLevel = $flameLevels[array_rand($flameLevels)];

            $conversation = Conversation::create([
                'participant_one_id' => min($participant1->id, $participant2->id),
                'participant_two_id' => max($participant1->id, $participant2->id),
                'last_message_at' => Carbon::now()->subHours(rand(0, 168)),
                'streak_count' => rand(0, 45),
                'streak_updated_at' => Carbon::now()->subDays(rand(0, 3)),
                'flame_level' => $flameLevel,
                'message_count' => $messageCount,
                'created_at' => $createdAt,
                'updated_at' => Carbon::now(),
            ]);

            $conversations[] = $conversation;

            // Créer des messages pour cette conversation
            $participants = [$participant1, $participant2];
            $messageTime = $createdAt->copy();

            for ($j = 0; $j < $messageCount; $j++) {
                $sender = $participants[array_rand($participants)];
                $messageTime = $messageTime->copy()->addMinutes(rand(1, 120));

                if ($messageTime > Carbon::now()) {
                    break;
                }

                ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'content' => $chatMessages[array_rand($chatMessages)],
                    'type' => 'text',
                    'is_read' => rand(0, 10) > 2,
                    'read_at' => rand(0, 10) > 2 ? $messageTime->copy()->addMinutes(rand(1, 60)) : null,
                    'created_at' => $messageTime,
                    'updated_at' => $messageTime,
                ]);
            }
        }

        return $conversations;
    }

    /**
     * Create gift transactions
     */
    private function createGiftTransactions(array $users, array $conversations): void
    {
        $gifts = Gift::all();

        if ($gifts->isEmpty()) {
            $this->command->warn('No gifts found. Run GiftSeeder first.');
            return;
        }

        $giftMessages = [
            'Pour toi avec amour',
            'Tu le mérites !',
            'Juste pour te faire plaisir',
            'Parce que tu es spécial(e)',
            'Un petit cadeau de ma part',
            null,
            null,
        ];

        for ($i = 0; $i < 60; $i++) {
            $gift = $gifts->random();
            $sender = $users[array_rand($users)];
            $recipient = $users[array_rand($users)];

            // Éviter de s'envoyer un cadeau à soi-même
            while ($sender->id === $recipient->id) {
                $recipient = $users[array_rand($users)];
            }

            $createdAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));
            $platformFee = (int) ($gift->price * 0.05);
            $netAmount = $gift->price - $platformFee;

            $status = rand(0, 10) > 1 ? 'completed' : ['pending', 'failed', 'refunded'][rand(0, 2)];

            // Trouver une conversation existante ou null
            $conversation = null;
            if (!empty($conversations) && rand(0, 10) > 5) {
                $conversation = $conversations[array_rand($conversations)];
            }

            GiftTransaction::create([
                'gift_id' => $gift->id,
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'conversation_id' => $conversation?->id,
                'amount' => $gift->price,
                'platform_fee' => $platformFee,
                'net_amount' => $netAmount,
                'status' => $status,
                'payment_reference' => 'GIFT_' . strtoupper(Str::random(12)),
                'message' => $giftMessages[array_rand($giftMessages)],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Mettre à jour le solde du destinataire si complété
            if ($status === 'completed') {
                $recipient->increment('wallet_balance', $netAmount);
            }
        }
    }

    /**
     * Create premium subscriptions
     */
    private function createPremiumSubscriptions(array $users, array $conversations): void
    {
        $statuses = ['active', 'active', 'expired', 'cancelled', 'pending'];

        for ($i = 0; $i < 30; $i++) {
            $subscriber = $users[array_rand($users)];
            $targetUser = $users[array_rand($users)];

            // Éviter de s'abonner à soi-même
            while ($subscriber->id === $targetUser->id) {
                $targetUser = $users[array_rand($users)];
            }

            $createdAt = Carbon::now()->subDays(rand(0, 90));
            $status = $statuses[array_rand($statuses)];
            $type = rand(0, 10) > 5 ? 'conversation' : 'message';

            $startsAt = $status !== 'pending' ? $createdAt->copy()->addHours(rand(1, 24)) : null;
            $expiresAt = $startsAt ? $startsAt->copy()->addDays(30) : null;

            PremiumSubscription::create([
                'subscriber_id' => $subscriber->id,
                'target_user_id' => $targetUser->id,
                'type' => $type,
                'conversation_id' => $type === 'conversation' && !empty($conversations) ? $conversations[array_rand($conversations)]->id : null,
                'message_id' => null,
                'amount' => 450,
                'status' => $status,
                'payment_reference' => 'SUB_' . strtoupper(Str::random(12)),
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'auto_renew' => rand(0, 10) > 7,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * Create payments
     */
    private function createPayments(array $users): void
    {
        $types = ['subscription', 'gift', 'gift', 'gift'];
        $providers = ['ligosapp', 'ligosapp', 'cinetpay', 'intouch'];
        $statuses = ['completed', 'completed', 'completed', 'completed', 'pending', 'failed'];

        for ($i = 0; $i < 100; $i++) {
            $user = $users[array_rand($users)];
            $type = $types[array_rand($types)];
            $createdAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23));
            $status = $statuses[array_rand($statuses)];

            $amount = $type === 'subscription' ? 450 : [1000, 5000, 25000, 50000][rand(0, 3)];

            Payment::create([
                'user_id' => $user->id,
                'type' => $type,
                'provider' => $providers[array_rand($providers)],
                'amount' => $amount,
                'currency' => 'XAF',
                'status' => $status,
                'reference' => 'PAY_' . strtoupper(Str::random(16)),
                'provider_reference' => $status === 'completed' ? 'PROV_' . strtoupper(Str::random(12)) : null,
                'metadata' => [],
                'failure_reason' => $status === 'failed' ? 'Solde insuffisant' : null,
                'completed_at' => $status === 'completed' ? $createdAt->copy()->addMinutes(rand(1, 30)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * Create withdrawals
     */
    private function createWithdrawals(array $users): void
    {
        $providers = ['mtn_momo', 'mtn_momo', 'orange_money'];
        $statuses = ['pending', 'pending', 'completed', 'completed', 'completed', 'rejected', 'failed'];

        // Get admin users for processing
        $admins = User::whereIn('role', ['admin', 'superadmin'])->get();

        for ($i = 0; $i < 40; $i++) {
            $user = $users[array_rand($users)];
            $createdAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));
            $status = $statuses[array_rand($statuses)];

            $amount = [1000, 2000, 5000, 10000, 15000, 20000, 25000][rand(0, 6)];
            $fee = 0; // Pas de frais pour l'instant
            $netAmount = $amount - $fee;

            $processor = $admins->isNotEmpty() && in_array($status, ['completed', 'rejected']) ? $admins->random() : null;

            Withdrawal::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'phone_number' => '229' . $user->phone,
                'provider' => $providers[array_rand($providers)],
                'status' => $status,
                'processed_by' => $processor?->id,
                'processed_at' => in_array($status, ['completed', 'rejected', 'failed']) ? $createdAt->copy()->addHours(rand(1, 48)) : null,
                'notes' => $status === 'completed' ? 'Traité avec succès' : null,
                'rejection_reason' => $status === 'rejected' ? 'Numéro de téléphone invalide' : null,
                'transaction_reference' => $status === 'completed' ? 'WD_' . strtoupper(Str::random(12)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * Create reports
     */
    private function createReports(array $users): void
    {
        $reasons = ['spam', 'harassment', 'hate_speech', 'inappropriate_content', 'impersonation', 'other'];
        $statuses = ['pending', 'pending', 'pending', 'resolved', 'dismissed'];

        $descriptions = [
            'Ce contenu est offensant',
            'Cette personne m\'a harcelé',
            'Messages de spam répétés',
            'Faux profil',
            'Contenu inapproprié pour les mineurs',
            null,
        ];

        // Get admin users for review
        $admins = User::whereIn('role', ['admin', 'superadmin', 'moderator'])->get();

        // Reports on users
        for ($i = 0; $i < 20; $i++) {
            $reporter = $users[array_rand($users)];
            $reportedUser = $users[array_rand($users)];

            while ($reporter->id === $reportedUser->id) {
                $reportedUser = $users[array_rand($users)];
            }

            $createdAt = Carbon::now()->subDays(rand(0, 30));
            $status = $statuses[array_rand($statuses)];
            $reviewer = $admins->isNotEmpty() && $status !== 'pending' ? $admins->random() : null;

            Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => User::class,
                'reportable_id' => $reportedUser->id,
                'reason' => $reasons[array_rand($reasons)],
                'description' => $descriptions[array_rand($descriptions)],
                'status' => $status,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $status !== 'pending' ? $createdAt->copy()->addHours(rand(1, 72)) : null,
                'action_taken' => $status === 'resolved' ? 'Utilisateur averti' : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Reports on messages
        $messages = AnonymousMessage::inRandomOrder()->limit(10)->get();
        foreach ($messages as $message) {
            $reporter = $users[array_rand($users)];
            $createdAt = Carbon::now()->subDays(rand(0, 20));
            $status = $statuses[array_rand($statuses)];
            $reviewer = $admins->isNotEmpty() && $status !== 'pending' ? $admins->random() : null;

            Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => AnonymousMessage::class,
                'reportable_id' => $message->id,
                'reason' => $reasons[array_rand($reasons)],
                'description' => $descriptions[array_rand($descriptions)],
                'status' => $status,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $status !== 'pending' ? $createdAt->copy()->addHours(rand(1, 48)) : null,
                'action_taken' => $status === 'resolved' ? 'Message supprimé' : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Reports on confessions
        $confessions = Confession::inRandomOrder()->limit(10)->get();
        foreach ($confessions as $confession) {
            $reporter = $users[array_rand($users)];
            $createdAt = Carbon::now()->subDays(rand(0, 20));
            $status = $statuses[array_rand($statuses)];
            $reviewer = $admins->isNotEmpty() && $status !== 'pending' ? $admins->random() : null;

            Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => Confession::class,
                'reportable_id' => $confession->id,
                'reason' => $reasons[array_rand($reasons)],
                'description' => $descriptions[array_rand($descriptions)],
                'status' => $status,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $status !== 'pending' ? $createdAt->copy()->addHours(rand(1, 48)) : null,
                'action_taken' => $status === 'resolved' ? 'Confession rejetée' : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * Create notifications
     */
    private function createNotifications(array $users): void
    {
        $notificationTypes = [
            ['type' => 'new_message', 'title' => 'Nouveau message', 'body' => 'Vous avez reçu un nouveau message anonyme !'],
            ['type' => 'message_read', 'title' => 'Message lu', 'body' => 'Votre message a été lu !'],
            ['type' => 'new_confession', 'title' => 'Nouvelle confession', 'body' => 'Quelqu\'un a une confession pour vous...'],
            ['type' => 'gift_received', 'title' => 'Cadeau reçu', 'body' => 'Vous avez reçu un cadeau !'],
            ['type' => 'identity_revealed', 'title' => 'Identité révélée', 'body' => 'Quelqu\'un a révélé son identité !'],
            ['type' => 'withdrawal_completed', 'title' => 'Retrait effectué', 'body' => 'Votre retrait a été traité avec succès.'],
            ['type' => 'new_chat', 'title' => 'Nouveau message', 'body' => 'Vous avez un nouveau message dans le chat.'],
            ['type' => 'streak_warning', 'title' => 'Streak en danger', 'body' => 'Votre streak va expirer ! Envoyez un message.'],
        ];

        foreach ($users as $user) {
            $numNotifications = rand(0, 10);

            for ($i = 0; $i < $numNotifications; $i++) {
                $notification = $notificationTypes[array_rand($notificationTypes)];
                $createdAt = Carbon::now()->subDays(rand(0, 14))->subHours(rand(0, 23));
                $isRead = rand(0, 10) > 4;

                Notification::create([
                    'id' => Str::uuid(),
                    'user_id' => $user->id,
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'data' => json_encode(['user_id' => $users[array_rand($users)]->id]),
                    'read_at' => $isRead ? $createdAt->copy()->addMinutes(rand(5, 1440)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
