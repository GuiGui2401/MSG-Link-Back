<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Vérifier si l'utilisateur connecté a un forfait Premium/Certification actif
        $currentUser = $request->user();
        $userHasPremium = $currentUser && $currentUser->has_active_premium;

        // Si l'utilisateur est le destinataire ET qu'il a Premium, il peut voir l'identité
        $canViewIdentity = $this->is_identity_revealed ||
                          ($userHasPremium && $currentUser->id === $this->recipient_id);

        // Préparer les infos du sender si l'utilisateur peut voir l'identité
        $senderData = null;
        if ($canViewIdentity && $this->sender) {
            $senderData = [
                'id' => $this->sender->id,
                'username' => $this->sender->username,
                'first_name' => $this->sender->first_name,
                'last_name' => $this->sender->last_name,
                'full_name' => $this->sender->full_name,
                'avatar_url' => $this->sender->avatar_url,
                'is_premium' => $this->sender->is_premium ?? false,
            ];
        }

        return [
            'id' => $this->id,
            'content' => $this->content,
            'media_type' => $this->media_type ?? 'none',
            'media_url' => $this->media_url ? url('storage/' . $this->media_url) : null,
            'voice_type' => $this->voice_type ?? 'normal',
            'sender_initial' => $this->sender_initial,
            // Montrer les infos du sender si identité révélée OU si l'utilisateur a Premium
            'sender' => $senderData,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'is_identity_revealed' => $this->is_identity_revealed,
            'revealed_at' => $this->revealed_at?->toIso8601String(),
            'reply_to_message_id' => $this->reply_to_message_id,
            'created_at' => $this->created_at->toIso8601String(),

            // Cadeaux associés à ce message
            'gift_transactions' => GiftTransactionResource::collection($this->whenLoaded('giftTransactions')),

            // Si l'utilisateur est l'expéditeur, montrer le destinataire
            'recipient' => $this->when(
                $request->user()?->id === $this->sender_id,
                new UserPublicResource($this->whenLoaded('recipient'))
            ),
        ];
    }
}
