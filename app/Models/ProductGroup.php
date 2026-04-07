<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'color', 'show_on_order', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['show_on_order' => 'boolean', 'active' => 'boolean'];
    }

    public function products()
    {
        return $this->hasMany(Product::class)->where('active', true)->orderBy('sort_order');
    }
}
