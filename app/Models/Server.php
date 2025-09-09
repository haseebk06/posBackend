<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
     protected $fillable = [
        'name',
        'phone',
    ];
    
    public function table()
    {
        return $this->hasMany(Table::class);
    }
}