<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'client_id', 'referral_code', 'commission_rate', 'commission_type',
        'balance', 'total_earned', 'total_withdrawn', 'total_referrals',
        'total_conversions', 'status', 'payment_info',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate'   => 'decimal:2',
            'balance'           => 'decimal:2',
            'total_earned'      => 'decimal:2',
            'total_withdrawn'   => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function getReferralUrlAttribute(): string
    {
        return url('/?ref=' . $this->referral_code);
    }
}
