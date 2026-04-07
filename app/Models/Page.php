<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'template', 'meta_title', 'meta_description', 'meta_keywords', 'published', 'show_in_menu', 'sort_order'];
    protected function casts(): array { return ['published' => 'boolean', 'show_in_menu' => 'boolean']; }
}
