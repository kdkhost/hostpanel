<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'client_id', 'service_id', 'name', 'tld', 'registrar', 'registrar_id', 'type',
        'status', 'epp_code', 'nameserver1', 'nameserver2', 'nameserver3', 'nameserver4',
        'auto_renew', 'locked', 'id_protection', 'registration_date', 'expiry_date',
        'next_due_date', 'price', 'currency', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'auto_renew'        => 'boolean',
            'locked'            => 'boolean',
            'id_protection'     => 'boolean',
            'registration_date' => 'date',
            'expiry_date'       => 'date',
            'next_due_date'     => 'date',
            'price'             => 'decimal:2',
            'meta'              => 'array',
        ];
    }

    public function client()  { return $this->belongsTo(Client::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function tldModel(){ return $this->belongsTo(DomainTld::class, 'tld', 'tld'); }

    public function getFullDomainAttribute(): string
    {
        return $this->name . '.' . $this->tld;
    }

    public function isExpiring(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= $days;
    }
}
