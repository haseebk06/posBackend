<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Shift;
use App\Models\DailyReport;
use App\Models\Sale;
use App\Models\Retrun;
use App\Models\SoldItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class ShiftController extends Controller
{
    public function openShift(Request $request)
    {
        // 1. Check if user already has an open shift
        $existingShift = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            return response()->json(['message' => 'You already have an open shift.'], 400);
        }

        // 2. Check if this is the first shift of the day for this counter
        $today = now()->toDateString();
        $firstShiftToday = !Shift::where('counter_id', $request->counter_id)
            ->whereDate('start_time', $today)
            ->exists();

        // 3. Create shift
        $shift = Shift::create([
            'user_id'      => $request->user()->id,
            'counter_id'   => $request->counter_id,
            'name'         => $request->user()->name ?? 'Shift ' . now()->format('d-m-Y H:i'),
            'start_time'   => now(),
            'opening_cash' => $request->opening_cash ?? 0,
            'status'       => 'open',
        ]);

        // 4. Add flag in response
        return response()->json([
            'status' => true,
            'shift' => $shift,
            'is_first_shift' => $firstShiftToday,
        ], 201);
    }

    // Close a shift
    public function closeShift(Request $request, $id)
    {
        $shift = Shift::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (! $shift) {
            return response()->json(['message' => 'No open shift found.'], 404);
        }

        $shift->update([
            'end_time'       => now(),
            'closing_cash'   => $request->closing_cash ?? 0,
            'total_sales'    => $request->total_sales ?? 0,
            'total_expenses' => $request->total_expenses ?? 0,
            'status'         => 'closed',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Shift closed successfully.',
            'data' => $shift,
        ]);
    }

    public function currentShift($id)
    {
        $shift = Shift::where('user_id', $id)
            ->where('status', 'open')
            ->first();

        return response()->json($shift ?? ['message' => 'No open shift']);
    }
    
    public function prevShift($id)
    {
        $shift = Shift::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->where('status', 'closed')
            ->first();

        return response()->json($shift ?? ['message' => 'No closed shift']);
    }

    // List all shifts for user
    public function allShiftsById($id)
    {
        $shifts = Shift::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($shifts);
    }

    public function allShifts()
    {
        $totals = Shift::with('counter')
            ->selectRaw('counter_id, SUM(closing_cash) as total_closing_cash, SUM(total_sales) as total_sales')
            ->groupBy('counter_id')
            ->get();

        $shifts = Shift::with('counter')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'totals' => $totals,
            'shifts' => $shifts
        ]);
    }
    
    public function dailyReports()
    {
        $reports = DailyReport::with('counter')
            ->orderBy('report_date', 'desc')
            ->get();

        return response()->json($reports);
    }
    
    public function addCounter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'branch' => 'required|string|max:255',
            'system_id' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $counter = Counter::create([
            'name' => $request->name,
            'branch' => $request->branch,
            'status' => 'closed',
            'system_id' => $request->system_id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Counter created successfully.',
            'data' => $counter
        ], 201);
    }

    public function allCounters()
    {
        $counters = Counter::orderBy('created_at', 'desc')->get();

        return response()->json($counters);
    }
    
    public function openCounter(Request $request, $id)
    {

        $counter = Counter::where('id', $id)
            ->where('status', 'closed')
            ->first();

        if (! $counter) {
            return response()->json(['message' => 'No closed counter found.'], 404);
        }

        $counter->update([
            'start_time'      => now(),
            'end_time'        => null,
            'opened_by'       => $request->user()->name,
            'closed_by'       => null,
            'status'          => 'open',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Counter opened successfully.',
            'data' => $counter,
        ]);
    }

    public function closeCounter(Request $request, $id)
    {
        $counter = Counter::where('id', $id)
            ->where('status', 'open')
            ->first();

        if (! $counter) {
            return response()->json(['message' => 'No open counter found.'], 404);
        }

        $endTime = now();
        $startTime = $counter->start_time;

        $counter->update([
            'end_time'       => $endTime,
            'closed_by'      => $request->user()->name,
            'status'         => 'closed',
        ]);
        $counter = $counter->fresh();

        $report = null;

        if ($startTime) {
            // Every shift that ran on this counter during this open->close session.
            // Scoped purely by shift membership in the session window, never by
            // calendar day, so a session that runs past midnight or stays open
            // for 12/24+ hours still reports its real totals instead of zero.
            $shiftIds = Shift::where('counter_id', $counter->id)
                ->where('start_time', '>=', $startTime)
                ->where(function ($query) use ($endTime) {
                    $query->whereNull('end_time')
                        ->orWhere('end_time', '<=', $endTime);
                })
                ->pluck('id');

            $firstShift = Shift::whereIn('id', $shiftIds)->orderBy('start_time')->first();
            $lastShift = Shift::whereIn('id', $shiftIds)->orderByDesc('start_time')->first();

            $sales = Sale::whereIn('shift_id', $shiftIds)->get();
            $returns = Retrun::whereIn('shift_id', $shiftIds)->get();

            $paymentTotal = fn ($method) => (float) $sales->where('paymentMethod', $method)->sum('finalTotal');
            $returnPaymentTotal = fn ($method) => (float) $returns->where('paymentMethod', $method)->sum('finalTotal');

            $itemsSold = SoldItems::query()
                ->join('sales', 'sold_items.sale_id', '=', 'sales.id')
                ->whereIn('sales.shift_id', $shiftIds)
                ->where('sold_items.is_return', false)
                ->selectRaw('sold_items.name, sold_items.category, sold_items.unit, SUM(sold_items.quantity) as quantity, SUM(sold_items.subtotal) as total_amount')
                ->groupBy('sold_items.name', 'sold_items.category', 'sold_items.unit')
                ->orderByDesc('quantity')
                ->get();

            $grossSales = (float) $sales->sum('total');
            $rawFinalSales = (float) $sales->sum('finalTotal');
            $totalDiscount = (float) $sales->sum('discount');
            $totalGst = (float) $sales->sum('gst');
            $totalServiceCharges = (float) $sales->sum('service_charges');
            $totalExpenses = (float) Shift::whereIn('id', $shiftIds)->sum('total_expenses');
            $totalReturns = (float) $returns->sum('finalTotal');

            // Sale rows are never mutated by a return (see SaleController::addReturns),
            // so $rawFinalSales still includes the original amount of fully and partially
            // returned sales. Retrun rows are the sole source of truth for refunded
            // amounts, so every sales figure below is netted against them explicitly
            // instead of relying on the sale row itself having been adjusted.
            $cashReturns = (float) $returnPaymentTotal('cash');
            $cardReturns = (float) $returnPaymentTotal('card');
            $mobileReturns = (float) $returnPaymentTotal('mobile');

            $round = fn ($num) => round((float) $num);

            $report = [
                'counterName'         => $counter->name,
                'openedBy'            => $counter->opened_by,
                'openedAt'            => $counter->start_time,
                'closedBy'            => $counter->closed_by,
                'closedAt'            => $counter->end_time,
                'openingAmount'       => $round($firstShift->opening_cash ?? 0),
                'closingAmount'       => $round($lastShift->closing_cash ?? 0),
                'totalSales'          => $round($rawFinalSales - $totalReturns),
                'grossSales'          => $round($grossSales),
                // finalTotal already has the discount baked in (see SaleController::addSales),
                // so netSales must not subtract it again - it only nets out returns.
                'netSales'            => $round($rawFinalSales - $totalReturns),
                'totalGst'            => $round($totalGst),
                'totalServiceCharges' => $round($totalServiceCharges),
                'totalDiscount'       => $round($totalDiscount),
                'cashSales'           => $round($paymentTotal('cash') - $cashReturns),
                'cardSales'           => $round($paymentTotal('card') - $cardReturns),
                'mobileSales'         => $round($paymentTotal('mobile') - $mobileReturns),
                'cashReturns'         => $round($cashReturns),
                'cardReturns'         => $round($cardReturns),
                'mobileReturns'       => $round($mobileReturns),
                'totalOrders'         => $sales->count(),
                'dineInOrders'        => $sales->filter(fn ($s) => strtolower((string) $s->mode) === 'dine-in')->count(),
                'takeAwayOrders'      => $sales->filter(fn ($s) => strtolower((string) $s->mode) === 'takeaway')->count(),
                'returns'             => $returns->count(),
                'totalReturns'        => $round($totalReturns),
                'expenses'            => $round($totalExpenses),
                'itemsSold'           => $itemsSold,
            ];

            // Each counter-close is its own report row (a counter can be opened
            // and closed more than once in a calendar day), so this always
            // inserts rather than merging into an existing report_date row.
            DailyReport::create([
                'report_date'        => $endTime->toDateString(),
                'counter_id'         => $counter->id,
                'total_sales'        => $report['netSales'],
                'total_closing_cash' => $lastShift->closing_cash ?? 0,
                'report_data'        => $report,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Counter closed successfully.',
            'data'    => $counter,
            'report'  => $report,
        ]);
    }

    // Latest counter-closing report for a counter (for reprints after the fact).
    public function lastCounterReport($counterId)
    {
        $report = DailyReport::where('counter_id', $counterId)
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->first();

        if (! $report || ! $report->report_data) {
            return response()->json([
                'status'  => false,
                'message' => 'No counter closing report found yet.',
            ], 404);
        }

        return response()->json([
            'status'      => true,
            'report_date' => $report->report_date,
            'report'      => $report->report_data,
        ]);
    }
    
    public function generateReportManually()
    {
        Artisan::call('report:daily');

        return response()->json([
            'message' => 'Report generation triggered.',
            'output' => Artisan::output(),
        ]);
    }
}
