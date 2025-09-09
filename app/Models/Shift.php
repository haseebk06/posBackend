<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'counter_id',
        'start_time',
        'end_time',
        'name',
        'opening_cash',
        'closing_cash',
        'total_sales',
        'total_expenses',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}
