<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'notes',
    ];

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
