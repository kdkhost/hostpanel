<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $guard_name = 'admin';

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'status', 'two_factor_enabled',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'locale', 'timezone', 'last_login_at', 'last_login_ip', 'is_super_admin', 'preferences',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'two_factor_confirmed_at'   => 'datetime',
            'last_login_at'             => 'datetime',
            'is_super_admin'            => 'boolean',
            'two_factor_enabled'        => 'boolean',
            'preferences'               => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function impersonationLogs()
    {
        return $this->hasMany(ImpersonationLog::class);
    }

    public function loginLogs()
    {
        return $this->morphMany(LoginLog::class, 'authenticatable');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function kanbanTasks()
    {
        return $this->hasMany(KanbanTask::class, 'assigned_to');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=ffffff&background=1e40af';
    }
}
