<?php

namespace App\Http\Controllers;

use App\Models\BusinessDay;
use App\Models\Counter;
use App\Models\CounterCashierAssignment;
use App\Models\CounterSession;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\ShiftType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessDayController extends Controller
{
    private function todaysBusinessDay()
    {
        return BusinessDay::whereDate('business_date', now()->toDateString())->first();
    }

    public function currentState(Request $request)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $businessDay = $this->todaysBusinessDay();
        $shiftTypes = ShiftType::orderBy('name')->get();

        $shifts = $businessDay
            ? Shift::with(['shiftType', 'openedByUser', 'closedByUser'])
                ->where('business_day_id', $businessDay->id)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $counterSessions = $businessDay
            ? CounterSession::with(['counter', 'user', 'shift.shiftType', 'openedByUser', 'closedByUser'])
                ->where('business_day_id', $businessDay->id)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $assignedCounters = Counter::whereHas('assignedCashiers', function ($q) use ($request) {
            $q->where('users.id', $request->user()->id);
        })->get()->map(function ($counter) use ($businessDay) {
            $session = $businessDay
                ? CounterSession::where('counter_id', $counter->id)
                    ->where('business_day_id', $businessDay->id)
                    ->orderBy('created_at', 'desc')
                    ->first()
                : null;

            $counter->current_session = $session;
            return $counter;
        });

        return response()->json([
            'businessDay' => $businessDay,
            'shiftTypes' => $shiftTypes,
            'shifts' => $shifts,
            'counterSessions' => $counterSessions,
            'assignedCounters' => $assignedCounters,
        ]);
    }

    public function list()
    {
        $days = BusinessDay::with(['openedByUser', 'closedByUser'])
            ->orderBy('business_date', 'desc')
            ->get();

        return response()->json($days);
    }

    public function startDay(Request $request)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        if ($this->todaysBusinessDay()) {
            return response()->json(['message' => 'A business day already exists for today.'], 422);
        }

        $businessDay = BusinessDay::create([
            'business_date' => now()->toDateString(),
            'status' => 'open',
            'opened_by' => $request->user()->id,
            'start_time' => now(),
        ]);

        return response()->json(['status' => true, 'data' => $businessDay], 201);
    }

    public function closeDay(Request $request, $id)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $businessDay = BusinessDay::where('id', $id)->where('status', 'open')->first();

        if (! $businessDay) {
            return response()->json(['message' => 'No open business day found.'], 404);
        }

        if (Shift::where('business_day_id', $businessDay->id)->where('status', 'open')->exists()) {
            return response()->json(['message' => 'Please close all open shifts before closing the business day.'], 422);
        }

        $shiftIds = Shift::where('business_day_id', $businessDay->id)->pluck('id');
        $totalSales = Sale::whereIn('shift_id', $shiftIds)->sum('finalTotal');

        $businessDay->update([
            'status' => 'closed',
            'closed_by' => $request->user()->id,
            'end_time' => now(),
        ]);

        return response()->json([
            'data' => $businessDay,
            'total_sales' => $totalSales,
        ]);
    }

    public function startShift(Request $request)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $validator = Validator::make($request->all(), [
            'shift_type_id' => 'required|exists:shift_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $businessDay = $this->todaysBusinessDay();
        if (! $businessDay || $businessDay->status !== 'open') {
            return response()->json(['message' => 'Please start the business day first.'], 422);
        }

        $shift = Shift::where('business_day_id', $businessDay->id)
            ->where('shift_type_id', $request->shift_type_id)
            ->first();

        if ($shift) {
            if ($shift->status === 'closed') {
                return response()->json(['message' => 'This shift has already been closed for today.'], 422);
            }

            return response()->json(['status' => true, 'data' => $shift->load('shiftType'), 'created' => false]);
        }

        $shift = Shift::create([
            'business_day_id' => $businessDay->id,
            'shift_type_id' => $request->shift_type_id,
            'opened_by' => $request->user()->id,
            'start_time' => now(),
            'status' => 'open',
        ]);

        return response()->json(['status' => true, 'data' => $shift->load('shiftType'), 'created' => true], 201);
    }

    public function closeShift(Request $request, $id)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $shift = Shift::where('id', $id)->where('status', 'open')->first();

        if (! $shift) {
            return response()->json(['message' => 'No open shift found.'], 404);
        }

        if (CounterSession::where('shift_id', $shift->id)->where('status', 'open')->exists()) {
            return response()->json(['message' => 'Please close all open counters before closing the shift.'], 422);
        }

        $totalSales = Sale::where('shift_id', $shift->id)->sum('finalTotal');

        $shift->update([
            'status' => 'closed',
            'closed_by' => $request->user()->id,
            'end_time' => now(),
            'total_sales' => $totalSales,
        ]);

        return response()->json([
            'data' => $shift->load(['shiftType', 'openedByUser', 'closedByUser']),
            'total_sales' => $totalSales,
        ]);
    }

    public function assignedCounters(Request $request)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $businessDay = $this->todaysBusinessDay();

        $counters = Counter::whereHas('assignedCashiers', function ($q) use ($request) {
            $q->where('users.id', $request->user()->id);
        })->get()->map(function ($counter) use ($businessDay) {
            $session = $businessDay
                ? CounterSession::where('counter_id', $counter->id)
                    ->where('business_day_id', $businessDay->id)
                    ->orderBy('created_at', 'desc')
                    ->first()
                : null;

            $counter->current_session = $session;
            return $counter;
        });

        return response()->json($counters);
    }

    public function startCounterSession(Request $request)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $validator = Validator::make($request->all(), [
            'counter_id' => 'required|exists:counters,id',
            'shift_id' => 'required|exists:shifts,id',
            'opening_cash' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $assigned = CounterCashierAssignment::where('counter_id', $request->counter_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $assigned) {
            return response()->json(['message' => 'This counter is not assigned to you.'], 403);
        }

        $shift = Shift::where('id', $request->shift_id)->where('status', 'open')->first();
        if (! $shift) {
            return response()->json(['message' => 'No open shift found.'], 422);
        }

        if (CounterSession::where('counter_id', $request->counter_id)->where('status', 'open')->exists()) {
            return response()->json(['message' => 'This counter is already open.'], 422);
        }

        $session = CounterSession::create([
            'business_day_id' => $shift->business_day_id,
            'shift_id' => $shift->id,
            'counter_id' => $request->counter_id,
            'user_id' => $request->user()->id,
            'status' => 'open',
            'opening_cash' => $request->opening_cash ?? 0,
            'opened_by' => $request->user()->id,
            'start_time' => now(),
        ]);

        Counter::where('id', $request->counter_id)->update([
            'status' => 'open',
            'start_time' => now(),
            'opened_by' => $request->user()->name,
            'closed_by' => null,
        ]);

        return response()->json([
            'status' => true,
            'data' => $session->load(['counter', 'shift.shiftType']),
        ], 201);
    }

    public function closeCounterSession(Request $request, $id)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $session = CounterSession::with(['counter', 'businessDay', 'openedByUser'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'No open counter session found.'], 404);
        }

        $sales = Sale::where('counter_session_id', $session->id)
            ->where('is_return', false)
            ->get();

        $gross = (float) $sales->sum('total');
        $gst = (float) $sales->sum('gst');
        $discount = (float) $sales->sum('discount');
        $serviceCharges = (float) $sales->sum('service_charges');
        $netSale = (float) $sales->sum('finalTotal');

        $amountBreakdown = $sales->groupBy(fn ($sale) => $sale->paymentMethod ?: 'unspecified')
            ->map(fn ($group, $method) => [
                'method' => $method,
                'orders' => $group->count(),
                'total' => round((float) $group->sum('finalTotal'), 2),
            ])->values();

        $orderTypeBreakdown = $sales->groupBy(fn ($sale) => $sale->mode ?: 'Unspecified')
            ->map(fn ($group, $mode) => [
                'mode' => $mode,
                'orders' => $group->count(),
                'total' => round((float) $group->sum('finalTotal'), 2),
            ])->values();

        $cashSales = (float) $sales->where('paymentMethod', 'cash')->sum('finalTotal');
        $openingCash = (float) ($session->opening_cash ?? 0);
        $expectedCash = $openingCash + $cashSales;
        $closingCash = (float) ($request->closing_cash ?? 0);

        $session->update([
            'status' => 'closed',
            'closing_cash' => $closingCash,
            'total_sales' => $netSale,
            'closed_by' => $request->user()->id,
            'end_time' => now(),
        ]);

        Counter::where('id', $session->counter_id)->update([
            'status' => 'closed',
            'end_time' => now(),
            'closed_by' => $request->user()->name,
        ]);

        return response()->json([
            'data' => $session->fresh(['counter', 'businessDay', 'openedByUser', 'closedByUser']),
            'total_sales' => $netSale,
            'summary' => [
                'financial' => [
                    'gross' => round($gross, 2),
                    'gst' => round($gst, 2),
                    'discount' => round($discount, 2),
                    'service_charges' => round($serviceCharges, 2),
                    'net_sale' => round($netSale, 2),
                    'total_orders' => $sales->count(),
                ],
                'amount_breakdown' => $amountBreakdown,
                'order_type_breakdown' => $orderTypeBreakdown,
                'reconciliation' => [
                    'opening_cash' => round($openingCash, 2),
                    'cash_sales' => round($cashSales, 2),
                    'expected_cash' => round($expectedCash, 2),
                    'closing_cash' => round($closingCash, 2),
                    'short_excess' => round($closingCash - $expectedCash, 2),
                ],
            ],
        ]);
    }
}
