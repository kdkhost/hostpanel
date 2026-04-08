<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'name',
        'image',
        'image_mobile',
        'title',
        'subtitle',
        'cta_label',
        'cta_url',
        'target',
        'position',
        'active',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected function casts(): array { return ['active' => 'boolean']; }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return '';
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }
}
