<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'domain', 'billing_cycle',
        'price', 'setup_fee', 'discount', 'configurable_options', 'custom_fields', 'addons',
    ];

    protected function casts(): array
    {
        return [
            'price'                 => 'decimal:2',
            'setup_fee'             => 'decimal:2',
            'discount'              => 'decimal:2',
            'configurable_options'  => 'array',
            'custom_fields'         => 'array',
            'addons'                => 'array',
        ];
    }

    public function order()   { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
