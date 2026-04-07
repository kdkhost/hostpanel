<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerHealthLog extends Model
{
    protected $fillable = [
        'server_id', 'cpu_usage', 'ram_usage', 'swap_usage', 'disk_usage',
        'load_avg_1', 'load_avg_5', 'load_avg_15', 'uptime_seconds', 'account_count',
        'status', 'latency_ms', 'packet_loss_pct', 'network_in_mbps', 'network_out_mbps',
        'network_status', 'disk_partitions', 'services_status', 'raw_data', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at'       => 'datetime',
            'disk_partitions'  => 'array',
            'services_status'  => 'array',
            'raw_data'         => 'array',
            'cpu_usage'        => 'decimal:2',
            'ram_usage'        => 'decimal:2',
            'swap_usage'       => 'decimal:2',
            'disk_usage'       => 'decimal:2',
            'packet_loss_pct'  => 'decimal:2',
            'network_in_mbps'  => 'decimal:3',
            'network_out_mbps' => 'decimal:3',
        ];
    }

    public function server() { return $this->belongsTo(Server::class); }

    public function getUptimeHumanAttribute(): string
    {
        if (!$this->uptime_seconds) return '-';
        $days  = floor($this->uptime_seconds / 86400);
        $hours = floor(($this->uptime_seconds % 86400) / 3600);
        $mins  = floor(($this->uptime_seconds % 3600) / 60);
        return "{$days}d {$hours}h {$mins}m";
    }

    public function isHealthy(): bool
    {
        return $this->status === 'online'
            && ($this->cpu_usage ?? 0) < 90
            && ($this->ram_usage ?? 0) < 90
            && ($this->disk_usage ?? 0) < 90;
    }
}
