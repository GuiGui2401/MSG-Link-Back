<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    private static array $premiumCache = [];

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isMine = $this->sender_id === $user?->id;

        // Cache premium check per conversation to avoid N+1 queries
        $conversationId = $this->conversation_id;
        if (!isset(self::$premiumCache[$conversationId]) && $this->conversation && $user) {
            self::$premiumCache[$conversationId] = $this->conversation->hasPremiumSubscription($user);
        }
        $hasPremium = self::$premiumCache[$conversationId] ?? false;

        // Si le sender est supprimé, retourner des données par défaut
        $senderData = null;
        if ($this->sender) {
            $senderData = [
                'id' => $this->sender->id,
                'initial' => $this->sender->initial,
                'first_name' => ($isMine || $hasPremium) ? $this->sender->first_name : null,
                // Toujours exposer l'avatar si disponible, meme en mode anonyme.
                'avatar_url' => $this->sender->avatar_url,
            ];
        } else {
            // Sender supprimé - retourner des données anonymes
            $senderData = [
                'id' => null,
                'initial' => '?',
                'first_name' => null,
                'avatar_url' => null,
            ];
        }

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'content' => $this->content,
            'type' => $this->type,
            'media_url' => $this->media_url,
            'media_full_url' => $this->media_full_url,
            'reply_to_id' => $this->reply_to_message_id,
            'voice_effect' => $this->voice_effect,
            'is_mine' => $isMine,

            // Expéditeur
            'sender' => $senderData,

            // Si c'est un message cadeau
            'gift_data' => $this->when($this->type === 'gift' && $this->relationLoaded('giftTransaction'), function () {
                $gift = $this->giftTransaction?->gift;
                return [
                    'id' => $gift?->id,
                    'name' => $gift?->name,
                    'icon' => $gift?->icon,
                    'animation' => $gift?->animation,
                    'price' => $gift?->price,
                    'formatted_price' => $gift?->formatted_price,
                    'tier' => $gift?->tier,
                    'tier_color' => $gift?->tier_color,
                    'background_color' => $gift?->background_color,
                    'description' => $gift?->description,
                    'amount' => $this->giftTransaction?->amount,
                    'is_anonymous' => $this->giftTransaction?->is_anonymous ?? false,
                ];
            }),

            // Si c'est une réponse à un message anonyme
            'anonymous_message' => $this->when($this->relationLoaded('anonymousMessage') && $this->anonymousMessage, function () {
                return [
                    'id' => $this->anonymousMessage->id,
                    'content' => $this->anonymousMessage->content,
                    'created_at' => $this->anonymousMessage->created_at->toIso8601String(),
                ];
            }),

            'reply_to' => $this->when($this->relationLoaded('replyToMessage') && $this->replyToMessage, function () {
                return [
                    'id' => $this->replyToMessage->id,
                    'content' => $this->replyToMessage->content,
                    'type' => $this->replyToMessage->type,
                    'media_url' => $this->replyToMessage->media_url,
                    'media_full_url' => $this->replyToMessage->media_full_url,
                    'created_at' => $this->replyToMessage->created_at->toIso8601String(),
                ];
            }),

            // Si c'est une réponse à une story
            'story_reply' => $this->when($this->relationLoaded('storyReply') && $this->storyReply, function () {
                $storyReply = $this->storyReply;
                $story = $storyReply->story;
                return [
                    'id' => $storyReply->id,
                    'type' => $storyReply->type,
                    'content' => $storyReply->content,
                    'story' => $story ? [
                        'id' => $story->id,
                        'type' => $story->type,
                        'media_url' => $story->media_full_url ?? $story->media_url,
                        'text_content' => $story->text_content,
                        'background_color' => $story->background_color,
                    ] : null,
                ];
            }),

            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
