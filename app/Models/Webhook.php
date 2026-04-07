<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = ['name', 'url', 'secret', 'events', 'active', 'api_token_id', 'last_triggered_at', 'failures'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'events' => 'array', 'last_triggered_at' => 'datetime'];
    }

    public function apiToken() { return $this->belongsTo(ApiToken::class); }

    public function dispatch(string $event, array $payload): void
    {
        if (!$this->active || !in_array($event, $this->events ?? ['*'])) return;

        try {
            $http = new \GuzzleHttp\Client(['timeout' => 10]);
            $body = json_encode(['event' => $event, 'payload' => $payload, 'timestamp' => now()->toIso8601String()]);
            $sig  = hash_hmac('sha256', $body, $this->secret ?? '');

            $http->post($this->url, [
                'body'    => $body,
                'headers' => ['Content-Type' => 'application/json', 'X-Signature' => $sig],
            ]);

            $this->update(['last_triggered_at' => now(), 'failures' => 0]);
        } catch (\Exception $e) {
            $this->increment('failures');
            if ($this->failures >= 10) $this->update(['active' => false]);
        }
    }
}
