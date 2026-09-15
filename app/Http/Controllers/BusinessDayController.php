<?php

namespace App\Http\Controllers;

use App\Models\BusinessDay;
use App\Models\Counter;
use App\Models\CounterCashierAssignment;
use App\Models\CounterSession;
use App\Models\Order;
use App\Models\PettyCashTransaction;
use App\Models\Retrun;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\ShiftType;
use App\Models\Table;
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
            $counter->current_session = $this->currentSessionForCounter($counter->id, $businessDay);
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

    /**
     * The "already open" check in startCounterSession() is intentionally
     * global (a counter can't have two open sessions at once even if a
     * previous business day was never closed), so this lookup mirrors that:
     * an open session on the counter -- from any day -- always takes
     * priority so a stray leftover-open session is visible and closeable
     * here, instead of only blocking startCounterSession() invisibly.
     * Falls back to today's latest session for historical badge display
     * when nothing is currently open.
     */
    private function currentSessionForCounter(int $counterId, ?BusinessDay $businessDay): ?CounterSession
    {
        $openSession = CounterSession::where('counter_id', $counterId)
            ->where('status', 'open')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($openSession) {
            return $openSession;
        }

        return $businessDay
            ? CounterSession::where('counter_id', $counterId)
                ->where('business_day_id', $businessDay->id)
                ->orderBy('created_at', 'desc')
                ->first()
            : null;
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
            $counter->current_session = $this->currentSessionForCounter($counter->id, $businessDay);
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

    /**
     * Compute the financial summary for a counter session's sales. Shared by
     * closeCounterSession (at the moment of closing) and sessionSummary (for
     * re-viewing an already-closed session's report later).
     */
    private function buildSessionSummary(CounterSession $session, float $closingCash): array
    {
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

        // Cash that left the till outside of a sale: refunds paid back in
        // cash, and manual petty cash withdrawals -- both need to reduce
        // expected cash the same way a manual deposit increases it, or this
        // reconciliation drifts from the live Petty Cash ledger the moment
        // either is used (see PettyCashController::currentBalance, which
        // this mirrors).
        $cashRefunds = (float) Retrun::where('counter_session_id', $session->id)
            ->where('paymentMethod', 'cash')
            ->sum('finalTotal');

        $pettyCashDeposits = (float) PettyCashTransaction::where('counter_session_id', $session->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $pettyCashWithdrawals = (float) PettyCashTransaction::where('counter_session_id', $session->id)
            ->where('type', 'withdrawal')
            ->sum('amount');

        $openingCash = (float) ($session->opening_cash ?? 0);
        $expectedCash = $openingCash + $cashSales - $cashRefunds + $pettyCashDeposits - $pettyCashWithdrawals;

        return [
            'total_sales' => round($netSale, 2),
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
                    'cash_refunds' => round($cashRefunds, 2),
                    'petty_cash_deposits' => round($pettyCashDeposits, 2),
                    'petty_cash_withdrawals' => round($pettyCashWithdrawals, 2),
                    'expected_cash' => round($expectedCash, 2),
                    'closing_cash' => round($closingCash, 2),
                    'short_excess' => round($closingCash - $expectedCash, 2),
                ],
            ],
        ];
    }

    public function closeCounterSession(Request $request, $id)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $session = CounterSession::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'No open counter session found.'], 404);
        }

        // A pending order means an occupied table hasn't been paid/cleared
        // yet -- closing the counter out from under it would strand the
        // table in a state nothing can recover.
        $sessionOrderIds = Order::where('counter_session_id', $session->id)->pluck('id');
        $pendingTables = Table::where('payment_status', 'pending')
            ->whereIn('order_id', $sessionOrderIds)
            ->pluck('name');

        if ($pendingTables->isNotEmpty()) {
            return response()->json([
                'message' => 'Please clear or cancel pending orders before closing this counter: '
                    . $pendingTables->implode(', '),
            ], 422);
        }

        $closingCash = (float) ($request->closing_cash ?? 0);
        $result = $this->buildSessionSummary($session, $closingCash);

        $session->update([
            'status' => 'closed',
            'closing_cash' => $closingCash,
            'total_sales' => $result['total_sales'],
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
            'total_sales' => $result['total_sales'],
            'summary' => $result['summary'],
        ]);
    }

    /**
     * Re-view the financial summary for any counter session (typically an
     * already-closed one) without re-running the close mutation.
     */
    public function sessionSummary(Request $request, $id)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $session = CounterSession::with(['counter', 'businessDay', 'openedByUser', 'closedByUser'])
            ->find($id);

        if (! $session) {
            return response()->json(['message' => 'Counter session not found.'], 404);
        }

        $result = $this->buildSessionSummary($session, (float) ($session->closing_cash ?? 0));

        return response()->json([
            'data' => $session,
            'total_sales' => $result['total_sales'],
            'summary' => $result['summary'],
        ]);
    }
}
