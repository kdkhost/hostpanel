<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $fillable = ['title', 'slug', 'content', 'category', 'tags', 'published', 'views', 'sort_order'];
    protected function casts(): array { return ['published' => 'boolean', 'tags' => 'array']; }
}
