<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'active', 'sort_order'];
    protected function casts(): array { return ['active' => 'boolean']; }
}
