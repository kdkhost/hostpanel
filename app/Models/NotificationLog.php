<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'notifiable_type', 'notifiable_id', 'channel', 'subject', 'message',
        'recipient', 'status', 'template_slug', 'error', 'attempts', 'sent_at',
    ];
    protected function casts(): array { return ['sent_at' => 'datetime']; }
    public function notifiable() { return $this->morphTo(); }
}
