<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KanbanColumn extends Model
{
    protected $fillable = ['kanban_board_id', 'name', 'color', 'mapped_status', 'sort_order', 'wip_limit'];
    public function board() { return $this->belongsTo(KanbanBoard::class, 'kanban_board_id'); }
    public function tasks() { return $this->hasMany(KanbanTask::class)->orderBy('sort_order'); }
}
