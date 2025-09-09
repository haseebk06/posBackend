<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name',
        'seats',
        'payment_status',
        'status',
        'order_id',
        'server_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}