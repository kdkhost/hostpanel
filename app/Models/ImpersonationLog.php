<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    protected $fillable = [
        'admin_id', 'client_id', 'ip_address', 'user_agent', 'reason',
        'started_at', 'ended_at', 'duration_seconds', 'actions_log',
    ];

    protected function casts(): array
    {
        return [
            'started_at'       => 'datetime',
            'ended_at'         => 'datetime',
            'actions_log'      => 'array',
        ];
    }

    public function admin()  { return $this->belongsTo(Admin::class); }
    public function client() { return $this->belongsTo(Client::class); }

    public function getDurationAttribute(): string
    {
        if (!$this->duration_seconds) return '-';
        $mins = floor($this->duration_seconds / 60);
        $secs = $this->duration_seconds % 60;
        return "{$mins}m {$secs}s";
    }
}
