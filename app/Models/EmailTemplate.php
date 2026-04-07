<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'subject', 'body_html', 'body_text', 'variables', 'active'];
    protected function casts(): array { return ['active' => 'boolean', 'variables' => 'array']; }
}
