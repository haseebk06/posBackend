<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'items',
        'total',
        'tax',
        'discount',
        'finalTotal',
        'paymentMethod',
        'amountReceived',
        'changeAmount',
        'original_sale_id',
        'return_reason',
        'user_id',
        'shift_id',
        'is_return',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function soldItems()
    {
        return $this->hasMany(SoldItems::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function returnedSales()
    {
        return $this->hasMany(Sale::class, 'original_sale_id');
    }

    // A return sale belongs to an original sale
    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }
}
