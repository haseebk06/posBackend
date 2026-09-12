<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch',
        'branch_id',
        'status',
        'system_id',
        'start_time',
        'end_time',
        'user_id',
        'opened_by',
        'closed_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branchModel()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function counterSessions()
    {
        return $this->hasMany(CounterSession::class);
    }

    public function assignedCashiers()
    {
        return $this->belongsToMany(User::class, 'counter_cashier_assignments', 'counter_id', 'user_id');
    }
}
