<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $otherParticipant = $this->other_participant ?? $this->getOtherParticipant($user);

        // Si l'autre participant est supprimé, retourner des données minimales
        if (!$otherParticipant) {
            return [
                'id' => $this->id,
                'other_participant' => null,
                'last_message' => null,
                'streak' => [
                    'count' => 0,
                    'flame_level' => 'none',
                    'streak_updated_at' => null,
                ],
                'identity_revealed' => false,
                'has_premium' => false,
                'unread_count' => 0,
                'last_message_at' => $this->last_message_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
            ];
        }

        // Vérifier si l'identité peut être révélée (premium ou payé pour révéler)
        $canViewIdentity = $user->is_premium || $this->isIdentityRevealedFor($user);

        return [
            'id' => $this->id,

            // L'autre participant
            'other_participant' => [
                'id' => $otherParticipant->id,
                'first_name' => $canViewIdentity ? $otherParticipant->first_name : null,
                'last_name' => $canViewIdentity ? $otherParticipant->last_name : null,
                'full_name' => $canViewIdentity ? $otherParticipant->full_name : null,
                'username' => $canViewIdentity ? $otherParticipant->username : null,
                'initial' => $otherParticipant->initial,
                'avatar_url' => $canViewIdentity ? $otherParticipant->avatar_url : null,
                'is_online' => $otherParticipant->is_online,
                'is_premium' => $otherParticipant->is_premium,
                'last_seen_at' => $otherParticipant->last_seen_at?->toIso8601String(),
            ],

            // Dernier message
            'last_message' => $this->when($this->relationLoaded('lastMessage'), function () {
                return [
                    'id' => $this->lastMessage?->id,
                    'content' => $this->lastMessage?->content_preview,
                    'type' => $this->lastMessage?->type,
                    'is_mine' => $this->lastMessage?->sender_id === request()->user()?->id,
                    'created_at' => $this->lastMessage?->created_at?->toIso8601String(),
                ];
            }),

            // Système Flame
            'streak' => [
                'count' => $this->streak_count,
                'flame_level' => $this->flame_level,
                'streak_updated_at' => $this->streak_updated_at?->toIso8601String(),
            ],

            // Status
            'identity_revealed' => $canViewIdentity,
            'has_premium' => $this->has_premium ?? false,
            'unread_count' => $this->unread_count ?? 0,

            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
