<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KanbanTask extends Model
{
    protected $fillable = [
        'kanban_column_id', 'title', 'description', 'priority', 'assigned_to',
        'created_by', 'due_date', 'tags', 'related_type', 'related_id', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'tags' => 'array'];
    }

    public function column()     { return $this->belongsTo(KanbanColumn::class, 'kanban_column_id'); }
    public function assignee()   { return $this->belongsTo(Admin::class, 'assigned_to'); }
    public function creator()    { return $this->belongsTo(Admin::class, 'created_by'); }
    public function related()    { return $this->morphTo(); }
}
