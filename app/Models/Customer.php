<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category_description',
        'add_cat',
        'address',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function partyLedgers()
    {
        return $this->hasMany(PartyLedger::class);
    }
}
