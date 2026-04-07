<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDepartment extends Model
{
    protected $fillable = ['name', 'email', 'description', 'public', 'sla_hours', 'auto_assign', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['public' => 'boolean', 'auto_assign' => 'boolean', 'active' => 'boolean'];
    }

    public function tickets() { return $this->hasMany(Ticket::class); }
    public function quickReplies() { return $this->hasMany(QuickReply::class); }
}
