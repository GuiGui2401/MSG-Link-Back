<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Utilisateur (admin seulement pour la liste)
            'user' => $this->when(
                $request->user()?->is_admin && $this->relationLoaded('user'),
                new UserPublicResource($this->user)
            ),
            
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'fee' => $this->fee,
            'net_amount' => $this->net_amount,
            'formatted_net_amount' => $this->formatted_net_amount,
            
            'phone_number' => $this->phone_number,
            'provider' => $this->provider,
            'provider_label' => $this->provider_label,
            
            'status' => $this->status,
            'status_label' => $this->status_label,
            'is_pending' => $this->is_pending,
            'is_completed' => $this->is_completed,
            
            'transaction_reference' => $this->transaction_reference,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->when($request->user()?->is_admin, $this->notes),
            
            // Traitement (admin)
            'processed_by' => $this->when(
                $request->user()?->is_admin && $this->relationLoaded('processedBy'),
                fn() => $this->processedBy?->username
            ),
            'processed_at' => $this->processed_at?->toIso8601String(),
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
