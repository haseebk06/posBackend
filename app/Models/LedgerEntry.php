<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'category_id',
        'party_id',
        'description',
        'amount',
        'amount_paid',
        'is_credit',
        'payment_method',
        'entry_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'is_credit' => 'boolean',
        'entry_date' => 'date',
    ];

    protected $appends = ['due', 'status'];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(LedgerPayment::class);
    }

    /**
     * Money still outstanding on this entry: what's owed (expense) or
     * receivable (income) but not yet paid. Never negative.
     */
    public function getDueAttribute(): float
    {
        return round(max((float) $this->amount - (float) $this->amount_paid, 0), 2);
    }

    /**
     * settled  = fully paid/received
     * partial  = some paid, some still due
     * outstanding = nothing paid yet
     */
    public function getStatusAttribute(): string
    {
        $paid = (float) $this->amount_paid;
        $total = (float) $this->amount;

        if ($paid <= 0) {
            return 'outstanding';
        }

        return $paid >= $total ? 'settled' : 'partial';
    }
}
