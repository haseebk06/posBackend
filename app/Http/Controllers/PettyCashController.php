<?php

namespace App\Http\Controllers;

use App\Models\CounterSession;
use App\Models\PettyCashTransaction;
use App\Models\Retrun;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PettyCashController extends Controller
{
    /**
     * Net cash sitting in a counter's till: opening float, plus cash sales,
     * minus cash refunds, plus/minus manual petty cash movements. Shared by
     * summary() (live view) and store() (over-withdrawal guard) so both
     * agree with BusinessDayController::buildSessionSummary's reconciliation.
     */
    public static function currentBalance(CounterSession $session): float
    {
        $cashSales = (float) Sale::where('counter_session_id', $session->id)
            ->where('is_return', false)
            ->where('paymentMethod', 'cash')
            ->sum('finalTotal');

        $cashRefunds = (float) Retrun::where('counter_session_id', $session->id)
            ->where('paymentMethod', 'cash')
            ->sum('finalTotal');

        $deposits = (float) PettyCashTransaction::where('counter_session_id', $session->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $withdrawals = (float) PettyCashTransaction::where('counter_session_id', $session->id)
            ->where('type', 'withdrawal')
            ->sum('amount');

        return round((float) $session->opening_cash + $cashSales - $cashRefunds + $deposits - $withdrawals, 2);
    }

    public function summary(Request $request, $counterSessionId)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $session = CounterSession::with(['counter', 'shift.shiftType'])
            ->where('id', $counterSessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $session) {
            return response()->json(['message' => 'Counter session not found.'], 404);
        }

        $cashSales = Sale::where('counter_session_id', $session->id)
            ->where('is_return', false)
            ->where('paymentMethod', 'cash')
            ->get(['id', 'finalTotal', 'created_at', 'order_id'])
            ->load('order:id,order_number');

        $cashRefunds = Retrun::where('counter_session_id', $session->id)
            ->where('paymentMethod', 'cash')
            ->get(['id', 'finalTotal', 'created_at', 'reason', 'return_number']);

        $manual = PettyCashTransaction::with('user:id,name')
            ->where('counter_session_id', $session->id)
            ->get();

        $cashSalesTotal = (float) $cashSales->sum('finalTotal');
        $cashRefundsTotal = (float) $cashRefunds->sum('finalTotal');
        $deposits = (float) $manual->where('type', 'deposit')->sum('amount');
        $withdrawals = (float) $manual->where('type', 'withdrawal')->sum('amount');
        $openingCash = (float) $session->opening_cash;
        $currentBalance = round($openingCash + $cashSalesTotal - $cashRefundsTotal + $deposits - $withdrawals, 2);

        $entries = collect();

        foreach ($cashSales as $sale) {
            $entries->push([
                'id' => 'sale-' . $sale->id,
                'type' => 'sale',
                'amount' => (float) $sale->finalTotal,
                'description' => 'Cash sale' . ($sale->order?->order_number ? ' - Order #' . $sale->order->order_number : ''),
                'user' => null,
                'created_at' => $sale->created_at,
            ]);
        }

        foreach ($cashRefunds as $refund) {
            $entries->push([
                'id' => 'refund-' . $refund->id,
                'type' => 'refund',
                'amount' => (float) $refund->finalTotal,
                'description' => 'Cash refund' . ($refund->return_number ? ' - ' . $refund->return_number : '') . ($refund->reason ? ' (' . $refund->reason . ')' : ''),
                'user' => null,
                'created_at' => $refund->created_at,
            ]);
        }

        foreach ($manual as $txn) {
            $entries->push([
                'id' => 'txn-' . $txn->id,
                'type' => $txn->type,
                'amount' => (float) $txn->amount,
                'description' => $txn->description,
                'user' => $txn->user ? ['id' => $txn->user->id, 'name' => $txn->user->name] : null,
                'created_at' => $txn->created_at,
            ]);
        }

        $running = $openingCash;
        $transactions = $entries->sortBy('created_at')->values()->map(function ($entry) use (&$running) {
            $running += in_array($entry['type'], ['withdrawal', 'refund']) ? -$entry['amount'] : $entry['amount'];
            $entry['running_balance'] = round($running, 2);
            return $entry;
        })->reverse()->values();

        return response()->json([
            'counter_session_id' => $session->id,
            'session' => $session,
            'opening_cash' => round($openingCash, 2),
            'cash_sales' => round($cashSalesTotal, 2),
            'cash_refunds' => round($cashRefundsTotal, 2),
            'deposits' => round($deposits, 2),
            'withdrawals' => round($withdrawals, 2),
            'current_balance' => $currentBalance,
            'transactions' => $transactions,
        ]);
    }

    public function store(Request $request, $counterSessionId)
    {
        abort_unless($request->user()->role === 'cashier', 403);

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:deposit,withdrawal',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = CounterSession::where('id', $counterSessionId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (! $session) {
            return response()->json(['message' => 'No open counter session found.'], 404);
        }

        if ($request->type === 'withdrawal') {
            $balance = self::currentBalance($session);
            if ($request->amount > $balance) {
                return response()->json([
                    'message' => "Withdrawal exceeds available cash (Rs " . number_format($balance, 2) . ").",
                ], 422);
            }
        }

        $transaction = PettyCashTransaction::create([
            'counter_session_id' => $session->id,
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'data' => $transaction->load('user:id,name'),
            'balance_after' => self::currentBalance($session->fresh()),
        ], 201);
    }
}
