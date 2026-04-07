<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_reply_id', 'filename', 'original_name', 'mime_type', 'size'];

    public function reply() { return $this->belongsTo(TicketReply::class, 'ticket_reply_id'); }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->filename);
    }
}
