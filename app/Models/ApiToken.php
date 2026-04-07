<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'name', 'token', 'client_id', 'admin_id', 'abilities', 'active',
        'expires_at', 'last_used_at', 'uses_count', 'allowed_ips',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'active'      => 'boolean',
            'expires_at'  => 'datetime',
            'last_used_at'=> 'datetime',
            'abilities'   => 'array',
            'allowed_ips' => 'array',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function admin()  { return $this->belongsTo(Admin::class); }

    public static function generate(string $name, ?int $clientId = null, ?int $adminId = null, array $abilities = ['*']): array
    {
        $plain = Str::random(64);
        $token = static::create([
            'name'      => $name,
            'token'     => hash('sha256', $plain),
            'client_id' => $clientId,
            'admin_id'  => $adminId,
            'abilities' => $abilities,
            'active'    => true,
        ]);
        return ['token' => $token, 'plain_token' => $plain];
    }
}
