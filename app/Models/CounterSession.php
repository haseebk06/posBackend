<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_day_id',
        'shift_id',
        'counter_id',
        'user_id',
        'status',
        'opening_cash',
        'closing_cash',
        'total_sales',
        'opened_by',
        'closed_by',
        'start_time',
        'end_time',
    ];

    public function businessDay()
    {
        return $this->belongsTo(BusinessDay::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function openedByUser()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
