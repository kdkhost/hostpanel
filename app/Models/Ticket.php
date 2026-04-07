<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'client_id', 'ticket_department_id', 'service_id', 'assigned_to',
        'subject', 'priority', 'status', 'rating', 'rating_comment', 'closed_at',
        'last_reply_at', 'sla_due_at', 'sla_breached', 'tags', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'closed_at'    => 'datetime',
            'last_reply_at'=> 'datetime',
            'sla_due_at'   => 'datetime',
            'sla_breached' => 'boolean',
            'tags'         => 'array',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function department()
    {
        return $this->belongsTo(TicketDepartment::class, 'ticket_department_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function publicReplies()
    {
        return $this->hasMany(TicketReply::class)->where('is_note', false)->orderBy('created_at');
    }

    public function notes()
    {
        return $this->hasMany(TicketReply::class)->where('is_note', true)->orderBy('created_at');
    }

    public function lastReply()
    {
        return $this->hasOne(TicketReply::class)->latestOfMany();
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open'            => 'Aberto',
            'answered'        => 'Respondido',
            'customer_reply'  => 'Resposta do Cliente',
            'on_hold'         => 'Em Espera',
            'in_progress'     => 'Em Andamento',
            'closed'          => 'Fechado',
            default           => $this->status,
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low'    => 'Baixa',
            'medium' => 'Média',
            'high'   => 'Alta',
            'urgent' => 'Urgente',
            default  => $this->priority,
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low'    => 'secondary',
            'medium' => 'info',
            'high'   => 'warning',
            'urgent' => 'danger',
            default  => 'secondary',
        };
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $ticket) {
            if (empty($ticket->number)) {
                $ticket->number = 'TKT' . strtoupper(substr(uniqid(), -6));
            }
        });
    }
}
