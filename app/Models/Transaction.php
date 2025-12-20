<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'amount', 'description', 'status', 'meta', 'completed_at'
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
        'completed_at' => 'datetime'
    ];

    protected $appends = ['is_credit'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsCreditAttribute()
    {
        return $this->amount > 0;
    }
}
