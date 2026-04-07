<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceAddonSubscription extends Model
{
    protected $fillable = [
        'service_id', 'product_addon_id', 'billing_cycle', 'price', 'status',
        'next_due_date', 'auto_renew',
    ];

    protected function casts(): array
    {
        return ['auto_renew' => 'boolean', 'next_due_date' => 'date', 'price' => 'decimal:2'];
    }

    public function service() { return $this->belongsTo(Service::class); }
    public function addon()   { return $this->belongsTo(ProductAddon::class, 'product_addon_id'); }
}
