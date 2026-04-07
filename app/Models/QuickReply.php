<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickReply extends Model
{
    protected $fillable = ['name', 'message', 'ticket_department_id', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function department() { return $this->belongsTo(TicketDepartment::class, 'ticket_department_id'); }
}
