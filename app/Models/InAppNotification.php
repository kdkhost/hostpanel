<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InAppNotification extends Model
{
    protected $fillable = [
        'client_id', 'admin_id', 'title', 'message', 'icon', 'color',
        'action_url', 'action_label', 'read', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read' => 'boolean', 'read_at' => 'datetime'];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function admin()  { return $this->belongsTo(Admin::class); }

    public function markAsRead(): void
    {
        $this->update(['read' => true, 'read_at' => now()]);
    }
}
