<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'category', 'tags', 'active', 'views', 'helpful', 'not_helpful'];
    protected function casts(): array { return ['active' => 'boolean', 'tags' => 'array']; }
}
