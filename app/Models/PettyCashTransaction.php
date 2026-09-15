<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashTransaction extends Model
{
    protected $fillable = [
        'counter_session_id',
        'user_id',
        'type',
        'amount',
        'description',
    ];

    public function counterSession()
    {
        return $this->belongsTo(CounterSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
