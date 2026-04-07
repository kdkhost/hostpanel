<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $fillable = [
        'client_id', 'invoice_id', 'transaction_id', 'type', 'amount',
        'description', 'balance_before', 'balance_after', 'admin_id',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'balance_before' => 'decimal:2', 'balance_after' => 'decimal:2'];
    }

    public function client()      { return $this->belongsTo(Client::class); }
    public function invoice()     { return $this->belongsTo(Invoice::class); }
    public function transaction()  { return $this->belongsTo(Transaction::class); }
    public function admin()       { return $this->belongsTo(Admin::class); }
}
