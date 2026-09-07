<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckExpense extends Model {
    use SoftDeletes;

    protected $table = 'truck_expenses';

    protected $fillable = [
        'truck_number',
        'date',
        'advance',
        'diesel_liters',
        'diesel_rate_per_liter',
        'diesel_amount',
        'diesel_location',
        'spare_parts',
        'spare_parts_cost',
        'maintenance_details',
        'maintenance_cost',
        'total_amount',
        'balance',
    ];

    protected $casts = [
        'date' => 'date',
        'advance' => 'decimal:2',
        'diesel_liters' => 'decimal:2',
        'diesel_rate_per_liter' => 'decimal:2',
        'diesel_amount' => 'decimal:2',
        'spare_parts' => 'array',
        'spare_parts_cost' => 'decimal:2',
        'maintenance_details' => 'array',
        'maintenance_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    // Automatically calculate totals before saving
    protected static function boot() {
        parent::boot();

        static::saving(function ($model) {
            // Calculate diesel amount
            $model->diesel_amount = bcmul(
                $model->diesel_liters ?? 0,
                $model->diesel_rate_per_liter ?? 0,
                2
            );

            // Calculate spare parts cost
            $spareParts = $model->spare_parts ?? [];
            $model->spare_parts_cost = array_reduce(
                $spareParts,
                fn($sum, $part) => bcadd($sum, $part['amount'] ?? 0, 2),
                0
            );

            // Calculate maintenance cost
            $maintenance = $model->maintenance_details ?? [];
            $model->maintenance_cost = array_reduce(
                $maintenance,
                fn($sum, $detail) => bcadd($sum, $detail['cost'] ?? 0, 2),
                0
            );

            // Calculate total amount
            $model->total_amount = bcadd(
                bcadd($model->diesel_amount, $model->spare_parts_cost, 2),
                $model->maintenance_cost,
                2
            );

            // Calculate balance
            $model->balance = bcsub($model->total_amount, $model->advance ?? 0, 2);
        });
    }
}
