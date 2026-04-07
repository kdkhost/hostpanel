<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KanbanBoard extends Model
{
    protected $fillable = ['name', 'type', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function columns() { return $this->hasMany(KanbanColumn::class)->orderBy('sort_order'); }
}
