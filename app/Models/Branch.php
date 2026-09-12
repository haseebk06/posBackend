<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'last_order_sequence',
    ];

    public function counters()
    {
        return $this->hasMany(Counter::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
