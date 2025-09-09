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

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}
