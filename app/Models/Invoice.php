<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number', 'client_id', 'order_id', 'coupon_id', 'status', 'subtotal', 'discount',
        'tax', 'late_fee', 'interest', 'total', 'amount_paid', 'amount_due', 'credit_applied',
        'currency', 'payment_method', 'gateway', 'date_issued', 'date_due', 'date_paid',
        'notes', 'admin_notes', 'email_sent', 'email_sent_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'date_issued'    => 'date',
            'date_due'       => 'date',
            'date_paid'      => 'datetime',
            'email_sent_at'  => 'datetime',
            'email_sent'     => 'boolean',
            'subtotal'       => 'decimal:2',
            'discount'       => 'decimal:2',
            'tax'            => 'decimal:2',
            'late_fee'       => 'decimal:2',
            'interest'       => 'decimal:2',
            'total'          => 'decimal:2',
            'amount_paid'    => 'decimal:2',
            'amount_due'     => 'decimal:2',
            'credit_applied' => 'decimal:2',
            'meta'           => 'array',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function gatewayLogs()
    {
        return $this->hasMany(GatewayLog::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['pending', 'overdue'])
            && $this->date_due->isPast();
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'draft']);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    public function getFormattedAmountDueAttribute(): string
    {
        return 'R$ ' . number_format($this->amount_due, 2, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'           => 'Rascunho',
            'pending'         => 'Pendente',
            'paid'            => 'Pago',
            'partially_paid'  => 'Parcialmente Pago',
            'cancelled'       => 'Cancelado',
            'refunded'        => 'Estornado',
            'overdue'         => 'Vencido',
            default           => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'paid'            => 'success',
            'pending'         => 'warning',
            'overdue'         => 'danger',
            'partially_paid'  => 'info',
            'cancelled'       => 'secondary',
            'refunded'        => 'dark',
            default           => 'light',
        };
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $invoice) {
            if (empty($invoice->number)) {
                $prefix = config('hostpanel.invoice.prefix', 'FAT');
                $invoice->number = $prefix . str_pad(
                    (static::withTrashed()->max('id') + 1) ?? 1,
                    6, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
