<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    protected $fillable = [
        'tld', 'registrar', 'price_register', 'price_transfer', 'price_renew',
        'currency', 'epp_required', 'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'epp_required'   => 'boolean',
            'active'         => 'boolean',
            'price_register' => 'decimal:2',
            'price_transfer' => 'decimal:2',
            'price_renew'    => 'decimal:2',
        ];
    }
}
