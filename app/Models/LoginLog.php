<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = [
        'authenticatable_type', 'authenticatable_id', 'ip_address', 'user_agent',
        'device', 'browser', 'platform', 'success', 'fail_reason', 'country', 'city',
    ];

    protected function casts(): array
    {
        return ['success' => 'boolean'];
    }

    public function authenticatable()
    {
        return $this->morphTo();
    }
}
