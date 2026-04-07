<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'client_id', 'coupon_id', 'status', 'subtotal', 'discount',
        'setup_fee', 'total', 'currency', 'payment_method', 'ip_address', 'notes',
        'accepted_at', 'accepted_by',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'subtotal'    => 'decimal:2',
            'discount'    => 'decimal:2',
            'setup_fee'   => 'decimal:2',
            'total'       => 'decimal:2',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function acceptedBy()
    {
        return $this->belongsTo(Admin::class, 'accepted_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Pendente',
            'active'    => 'Ativo',
            'fraud'     => 'Fraude',
            'cancelled' => 'Cancelado',
            default     => $this->status,
        };
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $order) {
            if (empty($order->number)) {
                $order->number = 'ORD' . strtoupper(uniqid());
            }
        });
    }
}
