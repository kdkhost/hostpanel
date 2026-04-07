<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AutoLoginToken extends Model
{
    protected $fillable = [
        'token', 'service_id', 'invoice_id', 'client_id', 'admin_id',
        'type', 'generated_by', 'panel_url', 'remote_ip', 'used_ip',
        'expires_at', 'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = (string) Str::uuid();
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function service() { return $this->belongsTo(Service::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function client()  { return $this->belongsTo(Client::class); }
    public function admin()   { return $this->belongsTo(Admin::class, 'admin_id'); }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function publicUrl(): string
    {
        return route('autologin.access', ['token' => $this->token]);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'invoice'  => 'Fatura',
            'ondemand' => 'Avulso',
            'admin'    => 'Administrativo',
            default    => $this->type,
        };
    }

    public function markUsed(string $ip): void
    {
        $this->update(['used_at' => now(), 'used_ip' => $ip]);
    }
}

