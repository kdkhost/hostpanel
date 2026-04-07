<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    protected $fillable = ['product_id', 'name', 'description', 'billing_cycle_type', 'price', 'setup_fee', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'price' => 'decimal:2', 'setup_fee' => 'decimal:2'];
    }

    public function product()       { return $this->belongsTo(Product::class); }
    public function subscriptions() { return $this->hasMany(ServiceAddonSubscription::class); }
}
