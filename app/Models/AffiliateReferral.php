<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateReferral extends Model
{
    protected $fillable = [
        'affiliate_id', 'referred_client_id', 'ip', 'landing_page', 'converted', 'converted_at',
    ];

    protected function casts(): array
    {
        return ['converted' => 'boolean', 'converted_at' => 'datetime'];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function referredClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'referred_client_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'referral_id');
    }
}
