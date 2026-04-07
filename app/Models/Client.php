<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'document_type', 'document_number', 'phone', 'mobile',
        'whatsapp', 'birth_date', 'company_name', 'company_position', 'address', 'address_number',
        'address_complement', 'neighborhood', 'city', 'state', 'postcode', 'country', 'ibge_code',
        'avatar', 'language', 'currency', 'status', 'email_verified', 'email_verified_at',
        'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'marketing_consent', 'terms_accepted', 'terms_accepted_at', 'credit_balance', 'notes',
        'last_login_at', 'last_login_ip', 'is_protected', 'meta',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'two_factor_confirmed_at'   => 'datetime',
            'terms_accepted_at'         => 'datetime',
            'last_login_at'             => 'datetime',
            'birth_date'                => 'date',
            'email_verified'            => 'boolean',
            'two_factor_enabled'        => 'boolean',
            'marketing_consent'         => 'boolean',
            'terms_accepted'            => 'boolean',
            'is_protected'              => 'boolean',
            'credit_balance'            => 'decimal:2',
            'meta'                      => 'array',
        ];
    }

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function loginLogs()
    {
        return $this->morphMany(LoginLog::class, 'authenticatable');
    }

    public function impersonationLogs()
    {
        return $this->hasMany(ImpersonationLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(InAppNotification::class);
    }

    public function activeServices()
    {
        return $this->hasMany(Service::class)->where('status', 'active');
    }

    public function pendingInvoices()
    {
        return $this->hasMany(Invoice::class)->whereIn('status', ['pending', 'overdue']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=ffffff&background=2563eb';
    }

    public function getFormattedDocumentAttribute(): string
    {
        $doc = $this->document_number ?? '';
        if ($this->document_type === 'cpf' && strlen($doc) === 11) {
            return substr($doc, 0, 3) . '.' . substr($doc, 3, 3) . '.' . substr($doc, 6, 3) . '-' . substr($doc, 9, 2);
        }
        if ($this->document_type === 'cnpj' && strlen($doc) === 14) {
            return substr($doc, 0, 2) . '.' . substr($doc, 2, 3) . '.' . substr($doc, 5, 3) . '/' . substr($doc, 8, 4) . '-' . substr($doc, 12, 2);
        }
        return $doc;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->address_number,
            $this->address_complement,
            $this->neighborhood,
            $this->city . ($this->state ? '/' . $this->state : ''),
            $this->postcode,
        ]);
        return implode(', ', $parts);
    }
}
