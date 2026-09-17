<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerPayment extends Model
{
    protected $fillable = [
        'ledger_entry_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
