<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckTyre extends Model {
    use SoftDeletes;

    protected $table = 'truck_tyres';

    protected $fillable = [
        'truck_number',
        'date',
        'tyre_type',
        'tyre_quantity',
        'tyre_amount',
        'tyre_details',
        'total_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'tyre_quantity' => 'integer',
        'tyre_amount' => 'decimal:2',
        'tyre_details' => 'array',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot() {
        parent::boot();

        static::saving(function ($model) {
            // Ensure tyre amount is numeric
            $model->tyre_amount = $model->tyre_amount ?? 0;

            // Total amount is just the tyre amount
            $model->total_amount = $model->tyre_amount;
        });
    }
}
