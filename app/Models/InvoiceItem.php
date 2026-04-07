<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'service_id', 'description', 'amount', 'quantity',
        'unit_price', 'discount', 'period_from', 'period_to', 'type', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to'   => 'date',
            'amount'      => 'decimal:2',
            'unit_price'  => 'decimal:2',
            'discount'    => 'decimal:2',
        ];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
