<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPricing extends Model
{
    protected $table = 'product_pricing';

    protected $fillable = ['product_id', 'currency', 'billing_cycle', 'price', 'setup_fee', 'active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'setup_fee' => 'decimal:2', 'active' => 'boolean'];
    }

    public function product() { return $this->belongsTo(Product::class); }

    public static function cycleLabel(string $cycle): string
    {
        return match($cycle) {
            'one_time'      => 'Pagamento Único',
            'monthly'       => 'Mensal',
            'quarterly'     => 'Trimestral',
            'semiannually'  => 'Semestral',
            'annually'      => 'Anual',
            'biennially'    => 'Bienal',
            'triennially'   => 'Trienal',
            default         => $cycle,
        };
    }

    public static function cycleMonths(string $cycle): int
    {
        return match($cycle) {
            'monthly'       => 1,
            'quarterly'     => 3,
            'semiannually'  => 6,
            'annually'      => 12,
            'biennially'    => 24,
            'triennially'   => 36,
            default         => 0,
        };
    }

    public function getCycleLabelAttribute(): string
    {
        return self::cycleLabel($this->billing_cycle);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}
