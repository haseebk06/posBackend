<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Retrun;
use App\Models\Sale;
use App\Models\Server;
use App\Models\SoldItems;
use App\Models\CounterSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ServerController extends Controller
{
     public function index()
    {
        $servers = Server::with('table')->get();
        return response()->json($servers);
    }

    public function store(Request $request)
    {
        // validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'type' => 'nullable|in:Head Waiter,Senior,Junior',
        ]);

        $server = Server::create($validated);

        return response()->json([
            'message' => 'Server created successfully',
            'data' => $server
        ]);
    }

    public function update(Request $request, $id)
    {
        $server = Server::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'type' => 'nullable|in:Head Waiter,Senior,Junior',
        ]);

        $server->update($validated);

        return response()->json([
            'message' => 'Server updated successfully',
            'data' => $server,
        ]);
    }

    public function destroy($id)
    {
        $server = Server::findOrFail($id);
        $server->delete();

        return response()->json([
            'message' => 'Server deleted successfully'
        ]);
    }

    /**
     * Per-waiter performance for a period: orders served, items sold, gross
     * revenue, returns, net revenue, average order value. Scoped by
     * business_day_id (matches its actual counter sessions, same precise
     * approach used on the frontend Reports page) when given, otherwise by a
     * from/to calendar range.
     */
    public function performance(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'manager']), 403);

        $ordersQuery = Order::whereNotNull('server_id');

        $from = null;
        $to = null;

        if ($request->filled('business_day_id')) {
            $sessionIds = CounterSession::where('business_day_id', $request->business_day_id)->pluck('id');
            $ordersQuery->whereIn('counter_session_id', $sessionIds);
        } else {
            $from = $request->query('from')
                ? Carbon::parse($request->query('from'))->startOfDay()
                : now()->startOfMonth();
            $to = $request->query('to')
                ? Carbon::parse($request->query('to'))->endOfDay()
                : now()->endOfDay();
            $ordersQuery->whereBetween('created_at', [$from, $to]);
        }

        $orders = $ordersQuery->get(['id', 'server_id']);
        $orderIdsByServer = $orders->groupBy('server_id')->map(fn ($g) => $g->pluck('id'));
        $orderIds = $orders->pluck('id');

        $sales = Sale::whereIn('order_id', $orderIds)
            ->where('is_return', false)
            ->get(['id', 'order_id', 'finalTotal']);
        $salesByOrderId = $sales->groupBy('order_id');
        $saleIds = $sales->pluck('id');

        $returnsBySaleId = Retrun::whereIn('sale_id', $saleIds)
            ->get(['sale_id', 'finalTotal'])
            ->groupBy('sale_id');

        $itemQtyBySaleId = SoldItems::whereIn('sale_id', $saleIds)
            ->where('is_return', false)
            ->selectRaw('sale_id, SUM(quantity) as qty')
            ->groupBy('sale_id')
            ->pluck('qty', 'sale_id');

        $data = Server::all()->map(function ($server) use ($orderIdsByServer, $salesByOrderId, $returnsBySaleId, $itemQtyBySaleId) {
            $orderIds = $orderIdsByServer[$server->id] ?? collect();
            $ordersServed = $orderIds->count();

            $grossRevenue = 0.0;
            $returnsTotal = 0.0;
            $itemsSold = 0.0;

            foreach ($orderIds as $orderId) {
                foreach ($salesByOrderId[$orderId] ?? [] as $sale) {
                    $grossRevenue += (float) $sale->finalTotal;
                    $itemsSold += (float) ($itemQtyBySaleId[$sale->id] ?? 0);

                    foreach ($returnsBySaleId[$sale->id] ?? [] as $return) {
                        $returnsTotal += (float) $return->finalTotal;
                    }
                }
            }

            $netRevenue = $grossRevenue - $returnsTotal;

            return [
                'id' => $server->id,
                'name' => $server->name,
                'type' => $server->type,
                'orders_served' => $ordersServed,
                'items_sold' => $itemsSold,
                'gross_revenue' => round($grossRevenue, 2),
                'returns' => round($returnsTotal, 2),
                'net_revenue' => round($netRevenue, 2),
                'avg_order_value' => $ordersServed > 0 ? round($netRevenue / $ordersServed, 2) : 0,
            ];
        })->sortByDesc('net_revenue')->values();

        return response()->json([
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'data' => $data,
        ]);
    }
}
