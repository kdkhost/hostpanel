<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'type', 'active', 'published_at', 'expires_at'];
    protected function casts(): array { return ['active' => 'boolean', 'published_at' => 'datetime', 'expires_at' => 'datetime']; }
}
