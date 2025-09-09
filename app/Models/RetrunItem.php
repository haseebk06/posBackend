<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetrunItem extends Model
{
    protected $fillable = [
        'return_id',
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

    public function retruns()
    {
        return $this->belongsTo(Retrun::class, 'return_id');
    }
}
