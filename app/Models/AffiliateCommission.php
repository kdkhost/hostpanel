<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_id', 'referral_id', 'invoice_id', 'invoice_amount',
        'commission_amount', 'rate_applied', 'type', 'status', 'description',
    ];

    protected function casts(): array
    {
        return [
            'invoice_amount'    => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'rate_applied'      => 'decimal:2',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(AffiliateReferral::class, 'referral_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
