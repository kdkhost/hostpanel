<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientContact extends Model
{
    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 'mobile', 'document_number',
        'can_login', 'password', 'is_primary', 'permissions', 'status',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'can_login'   => 'boolean',
            'is_primary'  => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
}
