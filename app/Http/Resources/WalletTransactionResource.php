<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type === 'credit' ? 'Crédit' : 'Débit',
            'amount' => $this->amount,
            'formatted_amount' => ($this->type === 'credit' ? '+' : '-') . number_format($this->amount, 0, ',', ' ') . ' FCFA',
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            
            // Source de la transaction (polymorphique)
            'source_type' => $this->transactionable_type ? class_basename($this->transactionable_type) : null,
            'source_id' => $this->transactionable_id,
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
