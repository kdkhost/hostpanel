<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'order_id', 'product_id', 'server_id', 'product_name', 'domain',
        'username', 'password_encrypted', 'server_hostname', 'server_ip', 'nameserver1',
        'nameserver2', 'billing_cycle', 'price', 'setup_fee', 'currency', 'status',
        'provision_status', 'provision_log', 'provisioned_at', 'registration_date',
        'next_due_date', 'termination_date', 'auto_renew', 'configurable_options',
        'custom_fields', 'notes', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'provisioned_at'        => 'datetime',
            'registration_date'     => 'date',
            'next_due_date'         => 'date',
            'termination_date'      => 'date',
            'auto_renew'            => 'boolean',
            'price'                 => 'decimal:2',
            'setup_fee'             => 'decimal:2',
            'configurable_options'  => 'array',
            'custom_fields'         => 'array',
            'meta'                  => 'array',
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function addonSubscriptions()
    {
        return $this->hasMany(ServiceAddonSubscription::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getPasswordAttribute(): ?string
    {
        if ($this->password_encrypted) {
            try {
                return Crypt::decryptString($this->password_encrypted);
            } catch (\Exception) {
                return null;
            }
        }
        return null;
    }

    public function setPlainPasswordAttribute(string $value): void
    {
        $this->password_encrypted = Crypt::encryptString($value);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isDueOrOverdue(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'Pendente',
            'active'     => 'Ativo',
            'suspended'  => 'Suspenso',
            'terminated' => 'Encerrado',
            'cancelled'  => 'Cancelado',
            'fraud'      => 'Fraude',
            default      => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active'     => 'success',
            'pending'    => 'warning',
            'suspended'  => 'danger',
            'terminated' => 'secondary',
            'cancelled'  => 'dark',
            'fraud'      => 'danger',
            default      => 'light',
        };
    }
}
