<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'reach_min',
        'reach_max',
        'price',
        'duration_days',
        'is_active',
    ];

    protected $casts = [
        'reach_min' => 'integer',
        'reach_max' => 'integer',
        'price' => 'integer',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('price', 'asc')->orderBy('reach_min', 'asc');
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' FCFA';
    }

    public function getReachLabelAttribute(): string
    {
        if (!$this->reach_max || $this->reach_max === $this->reach_min) {
            return number_format($this->reach_min, 0, ',', ' ');
        }

        return number_format($this->reach_min, 0, ',', ' ') . ' - ' . number_format($this->reach_max, 0, ',', ' ');
    }
}
