<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterCashierAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'counter_id',
        'user_id',
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
