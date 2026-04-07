<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'category', 'active', 'sort_order'];
    protected function casts(): array { return ['active' => 'boolean']; }
}
