<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'name',
        'quantity',
        'barcode',
        'category',
        'costPrice',
        'sellingPrice',
        'stock',
        'subtotal',
        'unit',
        'is_return',
        'return_reason',
    ];

    public function orders()
    {
        return $this->belongsTo(Order::class);
    }
}
