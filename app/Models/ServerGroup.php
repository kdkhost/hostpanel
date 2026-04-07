<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerGroup extends Model
{
    protected $fillable = ['name', 'description', 'fill_type', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function servers()   { return $this->hasMany(Server::class); }
    public function products()  { return $this->hasMany(Product::class); }

    public function getNextServer(): ?Server
    {
        $query = $this->servers()->where('active', true)->where('status', 'online');

        return match($this->fill_type) {
            'sequential'  => $query->orderBy('id')->first(),
            'least_used'  => $query->orderBy('current_accounts')->first(),
            'random'      => $query->inRandomOrder()->first(),
            default       => $query->first(),
        };
    }
}
