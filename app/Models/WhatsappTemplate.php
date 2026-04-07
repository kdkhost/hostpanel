<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'message', 'variables', 'active'];
    protected function casts(): array { return ['active' => 'boolean', 'variables' => 'array']; }
}
