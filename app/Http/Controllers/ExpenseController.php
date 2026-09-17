<?php

namespace App\Http\Controllers;

use App\Models\CounterSession;
use App\Models\DeletionLog;
use App\Models\ExpenseCategory;
use App\Models\LedgerEntry;
use App\Models\LedgerPayment;
use App\Models\Party;
use App\Models\Retrun;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    /**
     * Resolve the reporting window. Defaults to the current month when the
     * frontend doesn't pass an explicit range.
     */
    private function range(Request $request): array
    {
        $from = $request->query('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    /**
     * Net POS revenue for the period: completed sales minus returns. This is
     * computed live from Sale/Retrun (never stored) so it can't drift or be
     * double-counted -- same approach as PettyCashController.
     */
    private function posNetSales(Carbon $from, Carbon $to): float
    {
        $sales = (float) Sale::where('is_return', false)
            ->whereBetween('created_at', [$from, $to])
            ->sum('finalTotal');

        $returns = (float) Retrun::whereBetween('created_at', [$from, $to])
            ->sum('finalTotal');

        return round($sales - $returns, 2);
    }

    /**
     * One synthetic income row per counter session that had sales in the
     * period, so POS takings appear in the ledger list as read-only entries.
     */
    private function posIncomeRows(Carbon $from, Carbon $to): array
    {
        $salesBySession = Sale::where('is_return', false)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('counter_session_id, SUM(finalTotal) as total, MAX(created_at) as last_at, COUNT(*) as cnt')
            ->groupBy('counter_session_id')
            ->get();

        $returnsBySession = Retrun::whereBetween('created_at', [$from, $to])
            ->selectRaw('counter_session_id, SUM(finalTotal) as total')
            ->groupBy('counter_session_id')
            ->get()
            ->keyBy('counter_session_id');

        $sessionIds = $salesBySession->pluck('counter_session_id')->filter()->all();
        $sessions = CounterSession::with(['counter', 'shift.shiftType'])
            ->whereIn('id', $sessionIds)
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($salesBySession as $group) {
            $sid = $group->counter_session_id;
            $refunds = (float) ($returnsBySession[$sid]->total ?? 0);
            $net = round((float) $group->total - $refunds, 2);

            if ($net == 0.0) {
                continue;
            }

            $session = $sid ? ($sessions[$sid] ?? null) : null;
            $label = $session && $session->counter
                ? $session->counter->name . ($session->shift?->shiftType?->name ? ' - ' . $session->shift->shiftType->name : '')
                : 'POS Sales';

            $rows[] = [
                'id' => 'pos-' . ($sid ?? 'na'),
                'raw_id' => null,
                'source' => 'pos',
                'type' => 'income',
                'category' => 'POS Sales',
                'party' => null,
                'description' => 'POS Sales - ' . $label . ' (' . $group->cnt . ' orders)',
                'amount' => $net,
                'amount_paid' => $net,
                'due' => 0,
                'status' => 'settled',
                'is_credit' => false,
                'payment_method' => null,
                'entry_date' => Carbon::parse($group->last_at)->toDateString(),
                'can_edit' => false,
            ];
        }

        return $rows;
    }

    private function manualRow(LedgerEntry $e): array
    {
        return [
            'id' => 'entry-' . $e->id,
            'raw_id' => $e->id,
            'source' => 'manual',
            'type' => $e->type,
            'category' => $e->category?->name,
            'party' => $e->party?->name,
            'description' => $e->description,
            'amount' => (float) $e->amount,
            'amount_paid' => (float) $e->amount_paid,
            'due' => $e->due,
            'status' => $e->status,
            'is_credit' => $e->is_credit,
            'payment_method' => $e->payment_method,
            'entry_date' => optional($e->entry_date)->toDateString(),
            'can_edit' => true,
        ];
    }

    public function summary(Request $request)
    {
        $this->guard($request);
        [$from, $to] = $this->range($request);

        $posNet = $this->posNetSales($from, $to);

        $manualIncome = (float) LedgerEntry::where('type', 'income')
            ->whereBetween('entry_date', [$from, $to])
            ->sum('amount');

        $expenses = (float) LedgerEntry::where('type', 'expense')
            ->whereBetween('entry_date', [$from, $to])
            ->sum('amount');

        $revenue = round($posNet + $manualIncome, 2);
        $netProfit = round($revenue - $expenses, 2);

        // Outstanding dues are point-in-time balances (not period-bound): show
        // everything still owed regardless of when it was entered.
        $payables = (float) LedgerEntry::where('type', 'expense')
            ->where('is_credit', true)
            ->get()
            ->sum(fn ($e) => $e->due);

        $receivables = (float) LedgerEntry::where('type', 'income')
            ->where('is_credit', true)
            ->get()
            ->sum(fn ($e) => $e->due);

        $cashInHand = (float) CounterSession::whereNotNull('end_time')
            ->whereBetween('end_time', [$from, $to])
            ->sum('closing_cash');

        $expenseByCategory = LedgerEntry::where('type', 'expense')
            ->whereBetween('entry_date', [$from, $to])
            ->with('category')
            ->get()
            ->groupBy(fn ($e) => $e->category?->name ?: 'Uncategorised')
            ->map(fn ($group, $name) => [
                'category' => $name,
                'total' => round((float) $group->sum('amount'), 2),
            ])
            ->values();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'revenue' => $revenue,
            'pos_net_sales' => $posNet,
            'manual_income' => round($manualIncome, 2),
            'expenses' => round($expenses, 2),
            'net_profit' => $netProfit,
            'accounts_payable' => round($payables, 2),
            'accounts_receivable' => round($receivables, 2),
            'cash_in_hand' => round($cashInHand, 2),
            'expense_by_category' => $expenseByCategory,
        ]);
    }

    public function transactions(Request $request)
    {
        $this->guard($request);
        [$from, $to] = $this->range($request);

        $query = LedgerEntry::with(['category', 'party'])
            ->whereBetween('entry_date', [$from, $to]);

        if ($request->query('type')) {
            $query->where('type', $request->query('type'));
        }

        $manual = $query->get()->map(fn ($e) => $this->manualRow($e))->all();

        // POS rows only make sense in an income view.
        $pos = $request->query('type') === 'expense'
            ? []
            : $this->posIncomeRows($from, $to);

        $rows = collect(array_merge($manual, $pos));

        if ($status = $request->query('status')) {
            $rows = $rows->where('status', $status);
        }

        $rows = $rows->sortByDesc('entry_date')->values();

        return response()->json(['data' => $rows]);
    }

    public function payables(Request $request)
    {
        $this->guard($request);

        $rows = LedgerEntry::with(['category', 'party'])
            ->where('type', 'expense')
            ->where('is_credit', true)
            ->get()
            ->filter(fn ($e) => $e->due > 0)
            ->map(fn ($e) => $this->manualRow($e))
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function receivables(Request $request)
    {
        $this->guard($request);

        $rows = LedgerEntry::with(['category', 'party'])
            ->where('type', 'income')
            ->where('is_credit', true)
            ->get()
            ->filter(fn ($e) => $e->due > 0)
            ->map(fn ($e) => $this->manualRow($e))
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $this->guard($request);

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:income,expense',
            'category_id' => 'nullable|exists:expense_categories,id',
            'party_id' => 'nullable|exists:parties,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'is_credit' => 'boolean',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank,other',
            'entry_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $isCredit = $data['is_credit'] ?? false;
        $amount = (float) $data['amount'];

        // Immediate (non-credit) entries are fully settled on creation. Credit
        // entries default to unpaid unless an upfront amount is supplied.
        if (! $isCredit) {
            $amountPaid = $amount;
        } else {
            $amountPaid = min((float) ($data['amount_paid'] ?? 0), $amount);
        }

        $entry = LedgerEntry::create([
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'party_id' => $data['party_id'] ?? null,
            'description' => $data['description'],
            'amount' => $amount,
            'amount_paid' => $amountPaid,
            'is_credit' => $isCredit,
            'payment_method' => $data['payment_method'] ?? null,
            'entry_date' => $data['entry_date'],
            'user_id' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
        ]);

        // Record any upfront payment as a payment row so the due history is
        // complete from day one.
        if ($amountPaid > 0) {
            LedgerPayment::create([
                'ledger_entry_id' => $entry->id,
                'amount' => $amountPaid,
                'payment_date' => $data['entry_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $isCredit ? 'Upfront payment' : 'Paid in full',
                'user_id' => $request->user()->id,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Entry recorded successfully',
            'data' => $this->manualRow($entry->fresh(['category', 'party'])),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->guard($request);

        $entry = LedgerEntry::find($id);
        if (! $entry) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:expense_categories,id',
            'party_id' => 'nullable|exists:parties,id',
            'description' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'payment_method' => 'nullable|in:cash,bank,other',
            'entry_date' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Guard against editing the amount below what's already been paid.
        if (isset($data['amount']) && (float) $data['amount'] < (float) $entry->amount_paid) {
            return response()->json([
                'message' => 'Amount cannot be less than what has already been paid (Rs '
                    . number_format((float) $entry->amount_paid, 2) . ').',
            ], 422);
        }

        $entry->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Entry updated successfully',
            'data' => $this->manualRow($entry->fresh(['category', 'party'])),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->guard($request);

        $entry = LedgerEntry::with(['category', 'party'])->find($id);
        if (! $entry) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        DeletionLog::create([
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'user_email' => $request->user()->email,
            'action' => 'delete_ledger_entry',
            'entity_type' => 'ledger_entry',
            'entity_id' => $entry->id,
            'reason' => $validated['reason'],
            'entity_snapshot' => $entry->toArray(),
            'created_at' => now(),
        ]);

        $entry->delete();

        return response()->json([
            'status' => true,
            'message' => 'Entry deleted successfully',
        ]);
    }

    public function recordPayment(Request $request, $id)
    {
        $this->guard($request);

        $entry = LedgerEntry::find($id);
        if (! $entry) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        if (! $entry->is_credit) {
            return response()->json(['message' => 'This entry is already settled.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ((float) $data['amount'] > $entry->due + 0.001) {
            return response()->json([
                'message' => 'Payment exceeds the outstanding due (Rs '
                    . number_format($entry->due, 2) . ').',
            ], 422);
        }

        DB::transaction(function () use ($entry, $data, $request) {
            LedgerPayment::create([
                'ledger_entry_id' => $entry->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            // Recompute the cache from the source-of-truth payment rows.
            $entry->amount_paid = (float) $entry->payments()->sum('amount');
            $entry->save();
        });

        return response()->json([
            'status' => true,
            'message' => 'Payment recorded successfully',
            'data' => $this->manualRow($entry->fresh(['category', 'party'])),
        ]);
    }

    public function entryPayments(Request $request, $id)
    {
        $this->guard($request);

        $entry = LedgerEntry::with(['payments.user:id,name'])->find($id);
        if (! $entry) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        return response()->json(['data' => $entry->payments()->with('user:id,name')->orderBy('payment_date')->get()]);
    }

    // ---- Parties ----

    public function parties(Request $request)
    {
        $this->guard($request);

        return response()->json(['data' => Party::orderBy('name')->get()]);
    }

    public function storeParty(Request $request)
    {
        $this->guard($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:supplier,customer,staff,other',
            'phone' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $party = Party::create(array_merge(
            ['type' => 'supplier'],
            $validator->validated()
        ));

        return response()->json(['status' => true, 'data' => $party], 201);
    }

    public function destroyParty(Request $request, $id)
    {
        $this->guard($request);

        $party = Party::find($id);
        if (! $party) {
            return response()->json(['message' => 'Party not found'], 404);
        }

        $party->delete();

        return response()->json(['status' => true, 'message' => 'Party deleted successfully']);
    }

    // ---- Categories ----

    public function categories(Request $request)
    {
        $this->guard($request);

        return response()->json(['data' => ExpenseCategory::orderBy('type')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $this->guard($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:expense,income',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = ExpenseCategory::create(array_merge($validator->validated(), ['is_default' => false]));

        return response()->json(['status' => true, 'data' => $category], 201);
    }
}
