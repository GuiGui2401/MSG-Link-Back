<?php

namespace Database\Seeders;

use App\Models\AnonymousMessage;
use App\Models\ChatMessage;
use App\Models\Confession;
use App\Models\Conversation;
use App\Models\Gift;
use App\Models\GiftCategory;
use App\Models\GiftTransaction;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PremiumSubscription;
use App\Models\Report;
use App\Models\Story;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // ===== USERS (10) =====
        $users = collect();
        $usersData = [
            [
                'email' => 'infodjstar7@gmail.com',
                'first_name' => 'Info',
                'last_name' => 'Djstar',
                'username' => 'infodjstar7',
                'phone' => '6500000001',
                'password' => '1470',
                'original_pin' => '1470',
                'role' => 'user',
                'bio' => 'Disponible pour collabs, DM ouvert.',
            ],
            [
                'email' => 'nadia.kossi@example.com',
                'first_name' => 'Nadia',
                'last_name' => 'Kossi',
                'username' => 'nadia.kossi',
                'phone' => '6500000002',
                'password' => 'Password@123',
                'original_pin' => '2233',
                'role' => 'admin',
                'bio' => 'Team support, réponses rapides.',
            ],
            [
                'email' => 'marc.yao@example.com',
                'first_name' => 'Marc',
                'last_name' => 'Yao',
                'username' => 'marc.yao',
                'phone' => '6500000003',
                'password' => 'Password@123',
                'original_pin' => '3344',
                'role' => 'moderator',
                'bio' => 'Modération et qualité.',
            ],
            [
                'email' => 'aicha.adjoa@example.com',
                'first_name' => 'Aïcha',
                'last_name' => 'Adjoa',
                'username' => 'aicha.adjoa',
                'phone' => '6500000004',
                'password' => 'Password@123',
                'original_pin' => '4455',
                'role' => 'user',
                'bio' => 'Toujours partante pour discuter.',
            ],
            [
                'email' => 'samuel.kpodo@example.com',
                'first_name' => 'Samuel',
                'last_name' => 'Kpodo',
                'username' => 'samuel.kpodo',
                'phone' => '6500000005',
                'password' => 'Password@123',
                'original_pin' => '5566',
                'role' => 'user',
                'bio' => 'Business & tech.',
            ],
            [
                'email' => 'fatou.hounnou@example.com',
                'first_name' => 'Fatou',
                'last_name' => 'Hounnou',
                'username' => 'fatou.hounnou',
                'phone' => '6500000006',
                'password' => 'Password@123',
                'original_pin' => '6677',
                'role' => 'user',
                'bio' => 'Créa visuelle et musique.',
            ],
            [
                'email' => 'lionel.assogba@example.com',
                'first_name' => 'Lionel',
                'last_name' => 'Assogba',
                'username' => 'lionel.assogba',
                'phone' => '6500000007',
                'password' => 'Password@123',
                'original_pin' => '7788',
                'role' => 'user',
                'bio' => 'Toujours connecté.',
            ],
            [
                'email' => 'prisca.zannou@example.com',
                'first_name' => 'Prisca',
                'last_name' => 'Zannou',
                'username' => 'prisca.zannou',
                'phone' => '6500000008',
                'password' => 'Password@123',
                'original_pin' => '8899',
                'role' => 'user',
                'bio' => 'Mode, lifestyle, vibes.',
            ],
            [
                'email' => 'luc.houenou@example.com',
                'first_name' => 'Luc',
                'last_name' => 'Houénou',
                'username' => 'luc.houenou',
                'phone' => '6500000009',
                'password' => 'Password@123',
                'original_pin' => '9900',
                'role' => 'user',
                'bio' => 'Sport et motivation.',
            ],
            [
                'email' => 'emilie.soglo@example.com',
                'first_name' => 'Émilie',
                'last_name' => 'Soglo',
                'username' => 'emilie.soglo',
                'phone' => '6500000010',
                'password' => 'Password@123',
                'original_pin' => '1122',
                'role' => 'user',
                'bio' => 'Toujours de bonne humeur.',
            ],
        ];

        foreach ($usersData as $index => $userData) {
            $users->push(User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'username' => $userData['username'],
                    'phone' => $userData['phone'],
                    'password' => Hash::make($userData['password']),
                    'original_pin' => $userData['original_pin'],
                    'is_verified' => true,
                    'email_verified_at' => $now,
                    'phone_verified_at' => $now,
                    'role' => $userData['role'],
                    'bio' => $userData['bio'],
                    'wallet_balance' => 10000 + ($index * 1500),
                    'last_seen_at' => $now->copy()->subMinutes(5 + ($index * 7)),
                ]
            ));
        }

        $adminUser = $users->firstWhere('role', 'admin') ?? $users->first();

        // ===== GIFT CATEGORIES (5) =====
        $giftCategoriesData = [
            ['name' => 'Romance', 'is_active' => true],
            ['name' => 'Amitié', 'is_active' => true],
            ['name' => 'Appréciation', 'is_active' => true],
            ['name' => 'Célébration', 'is_active' => true],
            ['name' => 'Premium', 'is_active' => true],
        ];

        foreach ($giftCategoriesData as $category) {
            GiftCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        // ===== STORIES (10) =====
        $stories = collect();
        $storySamples = [
            ['type' => 'text', 'content' => 'Bonne journée la team ☀️', 'background_color' => '#FFE066'],
            ['type' => 'text', 'content' => 'On se capte ce soir ?', 'background_color' => '#B8F2E6'],
            ['type' => 'text', 'content' => 'En réunion, je rappelle après', 'background_color' => '#E4C1F9'],
            ['type' => 'image', 'media_url' => 'https://picsum.photos/seed/fada-story-1/720/1280'],
            ['type' => 'image', 'media_url' => 'https://picsum.photos/seed/fada-story-2/720/1280'],
            ['type' => 'text', 'content' => 'Merci pour le soutien 🙏', 'background_color' => '#FFD6A5'],
            ['type' => 'video', 'media_url' => 'https://example.com/videos/fada-story-1.mp4', 'thumbnail_url' => 'https://picsum.photos/seed/fada-thumb-1/320/480'],
            ['type' => 'text', 'content' => 'Réponse en DM svp', 'background_color' => '#CDE7F0'],
            ['type' => 'image', 'media_url' => 'https://picsum.photos/seed/fada-story-3/720/1280'],
            ['type' => 'text', 'content' => 'Mood du jour : focus 🔥', 'background_color' => '#FDE2E4'],
        ];

        foreach ($storySamples as $index => $sample) {
            $user = $users[$index];
            $payload = [
                'user_id' => $user->id,
                'type' => $sample['type'],
                'duration' => 8,
                'views_count' => 15 + ($index * 7),
                'status' => 'active',
                'expires_at' => $now->copy()->addHours(24),
            ];

            if ($sample['type'] === 'text') {
                $payload['content'] = $sample['content'];
                $payload['background_color'] = $sample['background_color'];
            } elseif ($sample['type'] === 'image') {
                $payload['media_url'] = $sample['media_url'];
            } else {
                $payload['media_url'] = $sample['media_url'];
                $payload['thumbnail_url'] = $sample['thumbnail_url'];
            }

            $stories->push(Story::create($payload));
        }

        // ===== ANONYMOUS MESSAGES (10) =====
        $anonymousMessages = collect();
        $anonymousSamples = [
            'Salut, j’aime beaucoup ton énergie, continue comme ça.',
            'C’est pour remettre aujourd’hui hein, donc cette nuit.',
            'Tu peux me dire si tu seras dispo demain matin ?',
            'Merci pour ton aide hier, ça m’a vraiment sauvé.',
            'Tu as une voix grave, c’est captivant.',
            'J’ai un souci sur mon compte, tu peux regarder ?',
            'On se voit au point habituel vers 18h.',
            'Ton message m’a fait sourire, merci.',
            'Je passe déposer le colis après 19h.',
            'Hé, j’ai besoin d’un conseil rapide.',
        ];

        foreach ($anonymousSamples as $index => $content) {
            $recipient = $users[$index];
            $sender = $this->pickDifferentUser($users, $recipient);
            $isRead = $index % 2 === 0;

            $anonymousMessages->push(AnonymousMessage::create([
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'content' => $content,
                'is_read' => $isRead,
                'read_at' => $isRead ? $now->copy()->subHours(3 + $index) : null,
                'is_identity_revealed' => false,
            ]));
        }

        // ===== CONFESSIONS (10) =====
        $confessions = collect();
        $confessionSamples = [
            ['type' => 'public', 'status' => 'approved', 'content' => 'J’ai commencé un nouveau job et je suis stressé, mais ça va aller.'],
            ['type' => 'public', 'status' => 'approved', 'content' => 'J’ai peur d’avouer mes sentiments à quelqu’un.'],
            ['type' => 'public', 'status' => 'pending', 'content' => 'Je me sens perdu en ce moment.'],
            ['type' => 'public', 'status' => 'rejected', 'content' => 'Je veux faire payer quelqu’un, c’est trop.'],
            ['type' => 'private', 'status' => 'pending', 'content' => 'Je pense souvent à toi, même quand je ne dis rien.'],
            ['type' => 'private', 'status' => 'pending', 'content' => 'Désolé pour mon silence, c’était compliqué.'],
            ['type' => 'public', 'status' => 'approved', 'content' => 'Je me suis remis au sport et ça me fait du bien.'],
            ['type' => 'public', 'status' => 'approved', 'content' => 'J’aimerais que mes parents soient fiers de moi.'],
            ['type' => 'public', 'status' => 'pending', 'content' => 'Aujourd’hui j’ai décidé de repartir à zéro.'],
            ['type' => 'private', 'status' => 'pending', 'content' => 'Je te dois des excuses.'],
        ];

        foreach ($confessionSamples as $index => $sample) {
            $author = $users[$index];
            $recipient = $sample['type'] === 'private'
                ? $this->pickDifferentUser($users, $author)
                : null;

            $confession = Confession::create([
                'author_id' => $author->id,
                'recipient_id' => $recipient?->id,
                'content' => $sample['content'],
                'type' => $sample['type'],
                'status' => $sample['status'],
                'moderated_by' => $sample['status'] === 'pending' ? null : $adminUser->id,
                'moderated_at' => $sample['status'] === 'pending' ? null : $now->copy()->subDays(1),
                'rejection_reason' => $sample['status'] === 'rejected' ? 'Contenu non conforme.' : null,
                'likes_count' => 5 + ($index * 2),
                'views_count' => 50 + ($index * 15),
            ]);

            $confessions->push($confession);
        }

        // ===== GROUPS (10) + MEMBERS (10) =====
        $groups = collect();
        $groupMembers = collect();
        $groupSamples = [
            ['name' => 'Famille Weylo', 'description' => 'Discussions de famille et infos importantes.'],
            ['name' => 'Projet Weekend', 'description' => 'Organisation du projet du week-end.'],
            ['name' => 'Chill & Vibes', 'description' => 'On partage la bonne humeur.'],
            ['name' => 'Team Sport', 'description' => 'Entraînements, matchs et motivation.'],
            ['name' => 'Cuisine Maison', 'description' => 'Recettes rapides et astuces.'],
            ['name' => 'Business Local', 'description' => 'Réseau pro et opportunités.'],
            ['name' => 'Voyage 2025', 'description' => 'Planification du voyage.'],
            ['name' => 'Classe 2019', 'description' => 'Souvenirs et actus des anciens.'],
            ['name' => 'Musique Live', 'description' => 'Partages de sons et événements.'],
            ['name' => 'Support App', 'description' => 'Retour utilisateurs et suivi.'],
        ];

        for ($i = 0; $i < 10; $i++) {
            $creator = $users[$i];
            $group = Group::create([
                'name' => $groupSamples[$i]['name'],
                'description' => $groupSamples[$i]['description'],
                'creator_id' => $creator->id,
                'invite_code' => Group::generateInviteCode(),
                'is_public' => $i % 2 === 0,
                'max_members' => 50,
                'members_count' => 1,
                'messages_count' => 0,
                'last_message_at' => null,
                'avatar_url' => null,
            ]);

            $groups->push($group);
            $groupMembers->push(GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $creator->id,
                'role' => GroupMember::ROLE_ADMIN,
                'joined_at' => now(),
                'last_read_at' => now(),
                'is_muted' => false,
            ]));
        }

        // ===== GROUP MESSAGES (10) =====
        $groupMessages = collect();
        $groupMessageSamples = [
            'Bonsoir à tous, on fait le point demain.',
            'Je partage le doc ici, dites-moi si c’est ok.',
            'Je suis en retard de 10 minutes.',
            'Qui vient à l’entraînement ce soir ?',
            'Recette validée, merci pour vos retours.',
            'J’ai un nouveau contact pro à proposer.',
            'Les billets sont dispo, on bloque ?',
            'Vous avez les photos de la dernière fois ?',
            'On se retrouve au studio à 18h.',
            'Merci pour les retours, je corrige.',
        ];

        for ($i = 0; $i < 10; $i++) {
            $group = $groups[$i];
            $senderId = $groupMembers[$i]->user_id;

            $message = GroupMessage::create([
                'group_id' => $group->id,
                'sender_id' => $senderId,
                'content' => $groupMessageSamples[$i],
                'type' => GroupMessage::TYPE_TEXT,
            ]);

            $group->update([
                'messages_count' => 1,
                'last_message_at' => $message->created_at,
            ]);

            $groupMessages->push($message);
        }

        // ===== CONVERSATIONS (10) =====
        $conversations = collect();
        $pairs = [];
        while ($conversations->count() < 10) {
            $pair = $users->random(2)->values();
            $first = $pair[0];
            $second = $pair[1];

            $pairKey = $this->conversationKey($first->id, $second->id);
            if (isset($pairs[$pairKey])) {
                continue;
            }

            $pairs[$pairKey] = true;
            $conversations->push(Conversation::create([
                'participant_one_id' => $first->id,
                'participant_two_id' => $second->id,
                'last_message_at' => null,
                'streak_count' => 3,
                'streak_updated_at' => $now->copy()->subDays(1),
                'flame_level' => 'yellow',
                'message_count' => 0,
            ]));
        }

        // ===== GIFTS + GIFT TRANSACTIONS (10) =====
        $this->call(GiftSeeder::class);
        $gifts = Gift::all();
        $giftTransactions = collect();

        foreach ($conversations as $index => $conversation) {
            $senderId = $conversation->participant_one_id;
            $recipientId = $conversation->participant_two_id;
            $gift = $gifts->random();
            $amounts = GiftTransaction::calculateAmounts($gift->price);
            $giftMessages = [
                'Petit cadeau pour toi 🎁',
                'Merci pour ton aide aujourd’hui.',
                'Tu le mérites, force à toi.',
                'Un petit geste pour te remercier.',
                'Cadeau surprise, profite !',
                'Merci d’être là.',
                'Ça me fait plaisir de partager.',
                'Pour toi, sans raison.',
                'Petit boost du jour.',
                'Tu gères, tiens.',
            ];

            $giftTransactions->push(GiftTransaction::create([
                'gift_id' => $gift->id,
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'conversation_id' => $conversation->id,
                'amount' => $amounts['amount'],
                'platform_fee' => $amounts['platform_fee'],
                'net_amount' => $amounts['net_amount'],
                'status' => GiftTransaction::STATUS_COMPLETED,
                'payment_reference' => 'GIFT-' . strtoupper(Str::random(10)),
                'message' => $giftMessages[$index],
                'is_anonymous' => false,
            ]));
        }

        // ===== CHAT MESSAGES (10) =====
        $chatMessages = collect();
        $chatSamples = [
            'Salut, t’es où ?',
            'J’arrive dans 5 minutes.',
            'Ok c’est noté.',
            'Tu peux m’appeler quand tu es libre ?',
            'Je viens de finir, on se capte.',
            'Tu as vu mon dernier message ?',
            'Je t’envoie le lien tout de suite.',
            'Merci, reçu 👍',
            'On fait ça demain matin.',
            'Je suis en route.',
        ];

        foreach ($conversations as $index => $conversation) {
            $message = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $conversation->participant_one_id,
                'content' => $chatSamples[$index],
                'type' => ChatMessage::TYPE_TEXT,
                'is_read' => $index % 2 === 0,
                'read_at' => $index % 2 === 0 ? $now->copy()->subMinutes(10 + $index) : null,
            ]);

            $conversation->update([
                'last_message_at' => $message->created_at,
                'message_count' => 1,
            ]);

            $chatMessages->push($message);
        }

        // ===== PREMIUM SUBSCRIPTIONS (10) =====
        $premiumSubscriptions = collect();
        foreach ($conversations as $conversation) {
            $premiumSubscriptions->push(PremiumSubscription::create([
                'subscriber_id' => $conversation->participant_one_id,
                'target_user_id' => $conversation->participant_two_id,
                'type' => PremiumSubscription::TYPE_CONVERSATION,
                'conversation_id' => $conversation->id,
                'amount' => PremiumSubscription::MONTHLY_PRICE,
                'status' => PremiumSubscription::STATUS_ACTIVE,
                'payment_reference' => 'SUB-' . strtoupper(Str::random(10)),
                'starts_at' => $now->copy()->subDays(2),
                'expires_at' => $now->copy()->addDays(28),
                'auto_renew' => false,
            ]));
        }

        // ===== PAYMENTS (10) =====
        $payments = collect();
        for ($i = 0; $i < 10; $i++) {
            $type = $i < 4 ? Payment::TYPE_GIFT : ($i < 7 ? Payment::TYPE_SUBSCRIPTION : Payment::TYPE_WITHDRAWAL);
            $payments->push(Payment::create([
                'user_id' => $users->random()->id,
                'type' => $type,
                'provider' => [
                    Payment::PROVIDER_CINETPAY,
                    Payment::PROVIDER_INTOUCH,
                    Payment::PROVIDER_MANUAL,
                ][$i % 3],
                'amount' => 5000 + ($i * 1000),
                'currency' => 'XAF',
                'status' => Payment::STATUS_COMPLETED,
                'reference' => Payment::generateReference(),
                'provider_reference' => 'PRV-' . strtoupper(Str::random(10)),
                'metadata' => [
                    'channel' => $i % 2 === 0 ? 'mobile_money' : 'wallet',
                ],
                'completed_at' => $now->copy()->subDays(1),
            ]));
        }

        // ===== WITHDRAWALS (10) =====
        $withdrawals = collect();
        for ($i = 0; $i < 10; $i++) {
            $amount = 7000 + ($i * 1500);
            $status = [
                Withdrawal::STATUS_PENDING,
                Withdrawal::STATUS_PROCESSING,
                Withdrawal::STATUS_COMPLETED,
                Withdrawal::STATUS_REJECTED,
            ][$i % 4];

            $withdrawal = Withdrawal::create([
                'user_id' => $users->random()->id,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'phone_number' => '67' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT),
                'provider' => [
                    Withdrawal::PROVIDER_MTN_MOMO,
                    Withdrawal::PROVIDER_ORANGE_MONEY,
                    Withdrawal::PROVIDER_OTHER,
                ][$i % 3],
                'status' => $status,
                'processed_by' => in_array($status, [Withdrawal::STATUS_COMPLETED, Withdrawal::STATUS_REJECTED], true)
                    ? $adminUser->id
                    : null,
                'processed_at' => in_array($status, [Withdrawal::STATUS_COMPLETED, Withdrawal::STATUS_REJECTED], true)
                    ? $now->copy()->subDays(1)
                    : null,
                'notes' => $i % 3 === 0 ? 'Traitement en cours.' : null,
                'rejection_reason' => $status === Withdrawal::STATUS_REJECTED ? 'Informations incorrectes.' : null,
                'transaction_reference' => 'WDR-' . strtoupper(Str::random(10)),
            ]);

            $withdrawals->push($withdrawal);
        }

        // ===== WALLET TRANSACTIONS (10) =====
        $walletTransactions = collect();
        $walletSamples = [
            ['type' => WalletTransaction::TYPE_CREDIT, 'amount' => 2000, 'description' => 'Recharge via Mobile Money'],
            ['type' => WalletTransaction::TYPE_DEBIT, 'amount' => 1500, 'description' => 'Achat cadeau'],
            ['type' => WalletTransaction::TYPE_CREDIT, 'amount' => 3000, 'description' => 'Bonus fidélité'],
            ['type' => WalletTransaction::TYPE_DEBIT, 'amount' => 2500, 'description' => 'Abonnement premium'],
            ['type' => WalletTransaction::TYPE_CREDIT, 'amount' => 5000, 'description' => 'Recharge wallet'],
            ['type' => WalletTransaction::TYPE_DEBIT, 'amount' => 1000, 'description' => 'Frais de service'],
            ['type' => WalletTransaction::TYPE_CREDIT, 'amount' => 1200, 'description' => 'Cashback'],
            ['type' => WalletTransaction::TYPE_DEBIT, 'amount' => 1800, 'description' => 'Cadeau envoyé'],
            ['type' => WalletTransaction::TYPE_CREDIT, 'amount' => 4000, 'description' => 'Virement entrant'],
            ['type' => WalletTransaction::TYPE_DEBIT, 'amount' => 2200, 'description' => 'Retrait wallet'],
        ];

        for ($i = 0; $i < 10; $i++) {
            $type = $walletSamples[$i]['type'];
            $amount = $walletSamples[$i]['amount'];
            $balanceBefore = 20000 + ($i * 500);
            $balanceAfter = $type === WalletTransaction::TYPE_CREDIT
                ? $balanceBefore + $amount
                : max(0, $balanceBefore - $amount);

            $source = $i % 3 === 0
                ? $payments->random()
                : ($i % 3 === 1 ? $giftTransactions->random() : $withdrawals->random());

            $walletTransactions->push(WalletTransaction::create([
                'user_id' => $users->random()->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $walletSamples[$i]['description'],
                'reference' => 'WAL-' . strtoupper(Str::random(10)),
                'transactionable_type' => $source::class,
                'transactionable_id' => $source->id,
            ]));
        }

        // ===== REPORTS (10) =====
        $reports = collect();
        $reportSamples = [
            'Message suspect, possible spam.',
            'Contenu déplacé dans le groupe.',
            'Usurpation d’identité.',
            'Harcèlement dans les messages privés.',
            'Photos inappropriées.',
            'Message répétitif.',
            'Langage offensant.',
            'Faux profil détecté.',
            'Demande d’argent insistante.',
            'Publicité non autorisée.',
        ];

        for ($i = 0; $i < 10; $i++) {
            $reportable = match ($i % 4) {
                0 => $users[$i],
                1 => $anonymousMessages[$i],
                2 => $confessions[$i],
                default => $chatMessages[$i],
            };

            $status = [
                Report::STATUS_PENDING,
                Report::STATUS_REVIEWED,
                Report::STATUS_RESOLVED,
            ][$i % 3];

            $reports->push(Report::create([
                'reporter_id' => $users[($i + 1) % 10]->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->id,
                'reason' => array_keys(Report::getReasonLabels())[$i % 6],
                'description' => $reportSamples[$i],
                'status' => $status,
                'reviewed_by' => $status === Report::STATUS_PENDING ? null : $adminUser->id,
                'reviewed_at' => $status === Report::STATUS_PENDING ? null : $now->copy()->subDays(1),
                'action_taken' => $status === Report::STATUS_RESOLVED ? 'Signalement traité.' : null,
            ]));
        }

        // ===== NOTIFICATIONS (10) =====
        $notificationSamples = [
            ['title' => 'Nouveau message', 'body' => 'Tu as reçu un nouveau message.'],
            ['title' => 'Cadeau reçu', 'body' => 'Un ami t’a envoyé un cadeau.'],
            ['title' => 'Abonnement actif', 'body' => 'Ton abonnement premium est actif.'],
            ['title' => 'Signalement traité', 'body' => 'Ton signalement a été pris en charge.'],
            ['title' => 'Story vue', 'body' => 'Quelqu’un a vu ta story.'],
            ['title' => 'Nouveau like', 'body' => 'Ta confession a reçu un like.'],
            ['title' => 'Mise à jour', 'body' => 'Nouveaux paramètres disponibles.'],
            ['title' => 'Paiement confirmé', 'body' => 'Ton paiement a été confirmé.'],
            ['title' => 'Retrait en cours', 'body' => 'Ta demande de retrait est en traitement.'],
            ['title' => 'Rappel', 'body' => 'N’oublie pas de répondre à tes messages.'],
        ];

        for ($i = 0; $i < 10; $i++) {
            $user = $users[$i];
            $notifiable = $i % 3 === 0 ? $anonymousMessages[$i] : ($i % 3 === 1 ? $confessions[$i] : $chatMessages[$i]);
            $isRead = $i % 2 === 0;
            Notification::create([
                'user_id' => $user->id,
                'type' => $i % 3 === 0 ? 'message' : ($i % 3 === 1 ? 'system' : 'gift'),
                'title' => $notificationSamples[$i]['title'],
                'body' => $notificationSamples[$i]['body'],
                'data' => [
                    'notifiable_id' => $notifiable->id,
                ],
                'is_read' => $isRead,
                'read_at' => $isRead ? $now->copy()->subDays(1) : null,
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->id,
            ]);
        }
    }

    private function pickDifferentUser($users, User $exclude): User
    {
        return $users->where('id', '!=', $exclude->id)->random();
    }

    private function conversationKey(int $firstId, int $secondId): string
    {
        return $firstId < $secondId
            ? $firstId . '-' . $secondId
            : $secondId . '-' . $firstId;
    }
}
