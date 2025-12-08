<?php

namespace App\Services;

use App\Models\User;
use App\Models\AnonymousMessage;
use App\Models\Confession;
use App\Models\ChatMessage;
use App\Models\GiftTransaction;
use App\Models\Withdrawal;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FCMNotification;

class NotificationService
{
    private $messaging = null;

    public function __construct()
    {
        // Initialiser Firebase si configuré
        if (config('services.firebase.credentials')) {
            try {
                $factory = (new Factory)->withServiceAccount(config('services.firebase.credentials'));
                $this->messaging = $factory->createMessaging();
            } catch (\Exception $e) {
                Log::warning('Firebase not configured: ' . $e->getMessage());
            }
        }
    }

    /**
     * Créer une notification en base de données
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): Notification {
        return Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Envoyer une notification push via FCM
     */
    public function sendPushNotification(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging || !$user->fcm_token) {
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(FCMNotification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);

            return true;
        } catch (\Exception $e) {
            Log::error('FCM notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Si le token est invalide, le supprimer
            if (str_contains($e->getMessage(), 'not a valid FCM registration token')) {
                $user->update(['fcm_token' => null]);
            }

            return false;
        }
    }

    /**
     * Notification de nouveau message anonyme
     */
    public function sendNewMessageNotification(AnonymousMessage $message): void
    {
        $recipient = $message->recipient;
        $senderInitial = $message->sender_initial;

        // Notification en base
        $this->createNotification(
            $recipient,
            'new_message',
            'Nouveau message anonyme',
            "Quelqu'un ({$senderInitial}.) vous a envoyé un message anonyme.",
            [
                'message_id' => $message->id,
                'action' => 'view_message',
            ]
        );

        // Notification push
        $this->sendPushNotification(
            $recipient,
            '📩 Nouveau message anonyme',
            "Quelqu'un ({$senderInitial}.) vous a envoyé un message.",
            [
                'type' => 'new_message',
                'message_id' => (string) $message->id,
            ]
        );
    }

    /**
     * Notification de nouvelle confession
     */
    public function sendNewConfessionNotification(Confession $confession): void
    {
        if (!$confession->recipient) {
            return;
        }

        $recipient = $confession->recipient;
        $authorInitial = $confession->author_initial;

        $this->createNotification(
            $recipient,
            'new_confession',
            'Nouvelle confession',
            "Quelqu'un ({$authorInitial}.) vous a fait une confession.",
            [
                'confession_id' => $confession->id,
                'action' => 'view_confession',
            ]
        );

        $this->sendPushNotification(
            $recipient,
            '💬 Nouvelle confession',
            "Quelqu'un vous a fait une confession anonyme.",
            [
                'type' => 'new_confession',
                'confession_id' => (string) $confession->id,
            ]
        );
    }

    /**
     * Notification de message de chat
     */
    public function sendChatMessageNotification(ChatMessage $message): void
    {
        $conversation = $message->conversation;
        $recipient = $conversation->getOtherParticipant($message->sender);

        // Vérifier si le destinataire a un premium actif pour voir l'identité
        $hasPremium = $conversation->hasPremiumSubscription($recipient);
        
        $senderName = $hasPremium 
            ? $message->sender->first_name 
            : "Anonyme ({$message->sender->initial}.)";

        $this->createNotification(
            $recipient,
            'new_chat_message',
            'Nouveau message',
            "{$senderName} vous a envoyé un message.",
            [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'action' => 'open_chat',
            ]
        );

        $this->sendPushNotification(
            $recipient,
            '💬 Nouveau message',
            "{$senderName} vous a envoyé un message.",
            [
                'type' => 'new_chat_message',
                'conversation_id' => (string) $conversation->id,
            ]
        );
    }

    /**
     * Notification de cadeau reçu
     */
    public function sendGiftNotification(GiftTransaction $transaction): void
    {
        $recipient = $transaction->recipient;
        $gift = $transaction->gift;

        $this->createNotification(
            $recipient,
            'gift_received',
            'Cadeau reçu ! 🎁',
            "Vous avez reçu un cadeau : {$gift->name} ({$transaction->formatted_amount})",
            [
                'transaction_id' => $transaction->id,
                'gift_id' => $gift->id,
                'amount' => $transaction->net_amount,
                'action' => 'view_gift',
            ]
        );

        $this->sendPushNotification(
            $recipient,
            '🎁 Cadeau reçu !',
            "Vous avez reçu un {$gift->name} !", 
            [
                'type' => 'gift_received',
                'transaction_id' => (string) $transaction->id,
            ]
        );
    }

    /**
     * Notification de retrait traité
     */
    public function sendWithdrawalProcessedNotification(Withdrawal $withdrawal): void
    {
        $user = $withdrawal->user;

        $this->createNotification(
            $user,
            'withdrawal_processed',
            'Retrait effectué ✅',
            "Votre retrait de {$withdrawal->formatted_net_amount} a été effectué avec succès.",
            [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->net_amount,
                'action' => 'view_wallet',
            ]
        );

        $this->sendPushNotification(
            $user,
            '✅ Retrait effectué',
            "Votre retrait de {$withdrawal->formatted_net_amount} est en cours.",
            [
                'type' => 'withdrawal_processed',
                'withdrawal_id' => (string) $withdrawal->id,
            ]
        );
    }

    /**
     * Notification de retrait rejeté
     */
    public function sendWithdrawalRejectedNotification(Withdrawal $withdrawal): void
    {
        $user = $withdrawal->user;

        $this->createNotification(
            $user,
            'withdrawal_rejected',
            'Retrait refusé ❌',
            "Votre demande de retrait de {$withdrawal->formatted_amount} a été refusée. Raison: {$withdrawal->rejection_reason}",
            [
                'withdrawal_id' => $withdrawal->id,
                'reason' => $withdrawal->rejection_reason,
                'action' => 'view_wallet',
            ]
        );

        $this->sendPushNotification(
            $user,
            '❌ Retrait refusé',
            "Votre demande de retrait a été refusée.",
            [
                'type' => 'withdrawal_rejected',
                'withdrawal_id' => (string) $withdrawal->id,
            ]
        );
    }

    /**
     * Notification d'abonnement expirant bientôt
     */
    public function sendSubscriptionExpiringNotification(User $user, int $daysRemaining): void
    {
        $this->createNotification(
            $user,
            'subscription_expiring',
            'Abonnement expirant ⏰',
            "Votre abonnement premium expire dans {$daysRemaining} jour(s).",
            [
                'days_remaining' => $daysRemaining,
                'action' => 'manage_subscription',
            ]
        );

        $this->sendPushNotification(
            $user,
            '⏰ Abonnement expirant',
            "Votre abonnement premium expire dans {$daysRemaining} jour(s).",
            [
                'type' => 'subscription_expiring',
            ]
        );
    }
}
