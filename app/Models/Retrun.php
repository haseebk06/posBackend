<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retrun extends Model
{

    protected $fillable = [
        'items',
        'total',
        'tax',
        'gst',
        'service_charges',
        'discount',
        'finalTotal',
        'paymentMethod',
        'amountReceived',
        'changeAmount',
        'sale_id',
        'reason',
        'user_id',
        'shift_id',
        'branch_id',
        'return_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function retrunItems()
    {
        return $this->hasMany(RetrunItem::class, 'return_id');
    }
    
    public function sales()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function returnedSales()
    {
        return $this->hasMany(Sale::class, 'original_sale_id');
    }

    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }
}
