<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'counter_id',
        'total_sales',
        'total_closing_cash',
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}
