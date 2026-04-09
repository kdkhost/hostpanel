<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'session_id',
        'product_id',
        'billing_cycle',
        'domain',
        'custom_fields',
        'price',
        'setup_fee',
        'coupon_code',
        'discount',
        'expires_at',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->price + $this->setup_fee - $this->discount;
    }
}
