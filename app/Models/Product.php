<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_group_id', 'server_group_id', 'name', 'slug', 'description', 'welcome_email',
        'type', 'module', 'billing_cycle_type', 'require_domain', 'auto_setup', 'auto_setup_type',
        'configurable_options', 'custom_fields', 'cpanel_pkg', 'disk_space', 'bandwidth',
        'subdomains', 'email_accounts', 'databases', 'ftp_accounts', 'ssl_free', 'featured',
        'hidden', 'sort_order', 'stock_control_type', 'stock_quantity', 'image', 'features',
        'meta', 'active',
    ];

    protected function casts(): array
    {
        return [
            'require_domain'         => 'boolean',
            'auto_setup'             => 'boolean',
            'ssl_free'               => 'boolean',
            'featured'               => 'boolean',
            'hidden'                 => 'boolean',
            'active'                 => 'boolean',
            'configurable_options'   => 'array',
            'custom_fields'          => 'array',
            'features'               => 'array',
            'meta'                   => 'array',
        ];
    }

    public function group()
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id');
    }

    public function serverGroup()
    {
        return $this->belongsTo(ServerGroup::class);
    }

    public function pricing()
    {
        return $this->hasMany(ProductPricing::class)->where('active', true);
    }

    public function allPricing()
    {
        return $this->hasMany(ProductPricing::class);
    }

    public function addons()
    {
        return $this->belongsToMany(ProductAddon::class, 'product_addon_pricing', 'product_addon_id', 'product_addon_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function getPriceForCycle(string $cycle, string $currency = 'BRL'): ?ProductPricing
    {
        return $this->pricing
            ->where('billing_cycle', $cycle)
            ->where('currency', $currency)
            ->first();
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/product-default.svg');
    }
}
