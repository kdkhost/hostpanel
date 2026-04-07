<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Gateway extends Model
{
    protected $fillable = [
        'name', 'slug', 'driver', 'settings', 'active', 'test_mode',
        'fee_fixed', 'fee_percentage', 'sort_order', 'allowed_currencies',
        'supports_recurring', 'supports_refund',
    ];

    protected $hidden = ['settings'];

    protected function casts(): array
    {
        return [
            'active'               => 'boolean',
            'test_mode'            => 'boolean',
            'supports_recurring'   => 'boolean',
            'supports_refund'      => 'boolean',
            'fee_fixed'            => 'decimal:2',
            'fee_percentage'       => 'decimal:4',
            'allowed_currencies'   => 'array',
        ];
    }

    public function logs()
    {
        return $this->hasMany(GatewayLog::class);
    }

    public function getSettingsDecryptedAttribute(): array
    {
        if (!$this->settings) return [];
        try {
            return json_decode(Crypt::decryptString($this->settings), true) ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    public function setSettingsEncryptedAttribute(array $settings): void
    {
        $this->settings = Crypt::encryptString(json_encode($settings));
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->getSettingsDecryptedAttribute()[$key] ?? $default;
    }

    public function calculateFee(float $amount): float
    {
        return round($this->fee_fixed + ($amount * $this->fee_percentage), 2);
    }
}
