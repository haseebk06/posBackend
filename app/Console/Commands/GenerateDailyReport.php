<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily counter-wise sales and cash reports';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $yesterday = Carbon::yesterday()->toDateString();

        // Check if already generated
        if (DailyReport::where('report_date', $yesterday)->exists()) {
            $this->info("Report already exists for {$yesterday}");
            return;
        }

        $totals = Shift::whereDate('start_time', $yesterday)
            ->selectRaw('counter_id, SUM(closing_cash) as total_closing_cash, SUM(total_sales) as total_sales')
            ->groupBy('counter_id')
            ->get();

        foreach ($totals as $total) {
            DailyReport::create([
                'report_date'        => $yesterday,
                'counter_id'         => $total->counter_id,
                'total_sales'        => $total->total_sales,
                'total_closing_cash' => $total->total_closing_cash,
            ]);
        }

        $this->info("Daily report generated for {$yesterday}");
    }
}
