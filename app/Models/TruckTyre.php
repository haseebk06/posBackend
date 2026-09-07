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
        'tyre_details_cost',
        'total_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'tyre_quantity' => 'integer',
        'tyre_amount' => 'decimal:2',
        'tyre_details' => 'array',
        'tyre_details_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot() {
        parent::boot();

        static::saving(function ($model) {
            // Calculate tyre amount (ensure it's numeric)
            $model->tyre_amount = $model->tyre_amount ?? 0;

            // Calculate tyre details cost
            $tyreDetails = $model->tyre_details ?? [];
            $model->tyre_details_cost = array_reduce(
                $tyreDetails,
                fn($sum, $detail) => bcadd($sum, $detail['cost'] ?? 0, 2),
                0
            );

            // Calculate total amount
            $model->total_amount = bcadd($model->tyre_amount, $model->tyre_details_cost, 2);
        });
    }
}
