<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_date',
        'status',
        'opened_by',
        'closed_by',
        'start_time',
        'end_time',
    ];

    public function openedByUser()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function counterSessions()
    {
        return $this->hasMany(CounterSession::class);
    }
}
