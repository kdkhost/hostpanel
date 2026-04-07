<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'content', 'type', 'published', 'published_at'];
    protected function casts(): array { return ['published' => 'boolean', 'published_at' => 'datetime']; }
}
