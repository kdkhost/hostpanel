<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'transaction_id', 'invoice_id', 'client_id',
        'requested_by_type', 'requested_by_id',
        'gateway', 'gateway_refund_id',
        'type', 'status', 'amount', 'reason', 'meta', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'meta'         => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function invoice()     { return $this->belongsTo(Invoice::class); }
    public function client()      { return $this->belongsTo(Client::class); }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'Pendente',
            'processing' => 'Processando',
            'completed'  => 'Concluído',
            'failed'     => 'Falhou',
            default      => $this->status,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'full' ? 'Total' : 'Parcial';
    }
}
