<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoldCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'holdId',
        'name',
        'quantity',
        'barcode',
        'category',
        'costPrice',
        'sellingPrice',
        'stock',
        'subtotal',
        'unit',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
