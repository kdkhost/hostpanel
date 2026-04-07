<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'client_id', 'invoice_id', 'gateway', 'gateway_transaction_id',
        'gateway_reference', 'type', 'amount', 'fee', 'fee_amount', 'refunded_amount',
        'currency', 'status', 'description', 'gateway_response', 'meta', 'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'fee'             => 'decimal:2',
            'fee_amount'      => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'gateway_response'=> 'array',
            'meta'            => 'array',
        ];
    }

    public function client()      { return $this->belongsTo(Client::class); }
    public function invoice()     { return $this->belongsTo(Invoice::class); }
    public function admin()       { return $this->belongsTo(Admin::class); }

    public function refunds()  { return $this->hasMany(\App\Models\Refund::class); }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'            => 'Pendente',
            'completed'          => 'Concluída',
            'failed'             => 'Falhou',
            'cancelled'          => 'Cancelada',
            'refunded'           => 'Estornada',
            'partially_refunded' => 'Parcialmente Estornada',
            default              => $this->status,
        };
    }

    public function canRefund(): bool
    {
        return $this->status === 'completed'
            && ($this->refunded_amount ?? 0) < $this->amount;
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $t) {
            if (empty($t->reference)) {
                $t->reference = 'TXN' . strtoupper(uniqid());
            }
        });
    }
}
