<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['title', 'subtitle', 'image', 'link', 'button_text', 'active', 'sort_order'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function getImageUrlAttribute(): string { return asset('storage/' . $this->image); }
}
