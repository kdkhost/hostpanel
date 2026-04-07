<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'applies_to_setup', 'recurring',
        'recurring_cycles', 'max_uses', 'uses_count', 'max_uses_per_client',
        'allowed_products', 'allowed_billing_cycles', 'starts_at', 'expires_at',
        'minimum_amount', 'active',
    ];

    protected function casts(): array
    {
        return [
            'applies_to_setup'       => 'boolean',
            'recurring'              => 'boolean',
            'active'                 => 'boolean',
            'value'                  => 'decimal:2',
            'minimum_amount'         => 'decimal:2',
            'starts_at'              => 'date',
            'expires_at'             => 'date',
            'allowed_products'       => 'array',
            'allowed_billing_cycles' => 'array',
        ];
    }

    public function isValid(): bool
    {
        if (!$this->active) return false;
        if ($this->max_uses > 0 && $this->uses_count >= $this->max_uses) return false;
        $now = now()->toDateString();
        if ($this->starts_at && $this->starts_at->toDateString() > $now) return false;
        if ($this->expires_at && $this->expires_at->toDateString() < $now) return false;
        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            return round($amount * ($this->value / 100), 2);
        }
        return min($this->value, $amount);
    }
}
