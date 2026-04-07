<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = ['ticket_id', 'client_id', 'admin_id', 'message', 'is_note', 'ip_address'];

    protected function casts(): array
    {
        return ['is_note' => 'boolean'];
    }

    public function ticket()      { return $this->belongsTo(Ticket::class); }
    public function client()      { return $this->belongsTo(Client::class); }
    public function admin()       { return $this->belongsTo(Admin::class); }
    public function attachments() { return $this->hasMany(TicketAttachment::class); }

    public function isFromClient(): bool { return !is_null($this->client_id); }
    public function isFromAdmin(): bool  { return !is_null($this->admin_id); }
}
