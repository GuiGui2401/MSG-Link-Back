<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationIdentityReveal extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'revealed_user_id',
        'wallet_transaction_id',
        'revealed_at',
    ];

    protected $casts = [
        'revealed_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

    /**
     * La conversation concernée
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * L'utilisateur qui paie pour révéler
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * L'utilisateur dont l'identité est révélée
     */
    public function revealedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revealed_user_id');
    }

    /**
     * La transaction wallet associée
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
