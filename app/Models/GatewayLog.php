<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatewayLog extends Model
{
    protected $fillable = [
        'gateway_id', 'transaction_id', 'invoice_id', 'event_type', 'request_payload',
        'response_payload', 'response_code', 'success', 'error_message', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'request_payload'  => 'array',
            'response_payload' => 'array',
            'success'          => 'boolean',
        ];
    }

    public function gateway()     { return $this->belongsTo(Gateway::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function invoice()     { return $this->belongsTo(Invoice::class); }
}
