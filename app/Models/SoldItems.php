<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldItems extends Model
{

    use HasFactory;

    protected $fillable = [
        'sale_id',
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

    public function Sales()
    {
        return $this->belongsTo(Sale::class);
    }
}
