<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
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
        'original_order_id',
        'return_reason',
        'user_id',
        'shift_id',
        'sale_id',
        'is_return',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
