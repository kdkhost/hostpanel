<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'server_group_id', 'name', 'hostname', 'ip_address', 'ip_address_secondary', 'port',
        'datacenter', 'location',
        'type', 'module', 'username', 'api_key', 'api_hash', 'password',
        'max_accounts', 'current_accounts', 'secure', 'active', 'status', 'last_check_at',
        'os_name', 'os_version', 'kernel', 'cpanel_version', 'php_version_default',
        'php_versions_available', 'nameserver1', 'nameserver2', 'nameserver3', 'notes', 'meta',
    ];

    protected $hidden = ['api_key', 'api_hash', 'password'];

    protected function casts(): array
    {
        return [
            'api_key'                 => 'encrypted',
            'api_hash'                => 'encrypted',
            'password'                => 'encrypted',
            'secure'                  => 'boolean',
            'active'                  => 'boolean',
            'last_check_at'           => 'datetime',
            'php_versions_available'  => 'array',
            'notes'                   => 'array',
            'meta'                    => 'array',
        ];
    }

    public function group()
    {
        return $this->belongsTo(ServerGroup::class, 'server_group_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function healthLogs()
    {
        return $this->hasMany(ServerHealthLog::class);
    }

    public function latestHealthLog()
    {
        return $this->hasOne(ServerHealthLog::class)->latestOfMany('checked_at');
    }

    public function getApiUrlAttribute(): string
    {
        $scheme = $this->secure ? 'https' : 'http';
        return "{$scheme}://{$this->hostname}:{$this->port}";
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function getAvailabilityPercentAttribute(): float
    {
        if ($this->max_accounts === 0) {
            return 100.0;
        }
        return round((($this->max_accounts - $this->current_accounts) / $this->max_accounts) * 100, 1);
    }
}
