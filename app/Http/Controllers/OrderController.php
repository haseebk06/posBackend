<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CounterSession;
use App\Models\DeletionLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function getOrders()
    {
        $orders = Order::with(['OrderItems' => function ($query) {
            $query->where('is_return', false);
        }])->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'orders fetched successfully',
            'data' => $orders,
        ], 200);
    }

    public function addOrders(Request $request)
    {

        $order = new Order();
        $order->user_id = $request->user()->id;
        $order->shift_id = $request["shift_id"] ?? null;
        $order->counter_session_id = $request["counter_session_id"] ?? null;
        $order->total = $request["total"] ?? null;
        $order->tax = $request["tax"] ?? null;
        $order->discount = $request["discount"] ?? null;
        $order->mode = $request["mode"] ?? null;
        $order->finalTotal = $request["finalTotal"] ?? null;
        $order->amountReceived = $request["amountReceived"] ?? null;
        $order->changeAmount = $request["changeAmount"] ?? null;

        $branchId = $this->resolveBranchId($order->counter_session_id, $order->shift_id);

        if ($branchId) {
            $order->branch_id = $branchId;
            $order->order_number = $this->generateOrderNumber($branchId);
        }

        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Order added successfully',
            'data' => $order,
        ], 200);
    }

    /**
     * Resolve the branch an order belongs to, via its counter session or shift's counter.
     */
    private function resolveBranchId(?int $counterSessionId, ?int $shiftId): ?int
    {
        if ($counterSessionId) {
            $branchId = CounterSession::find($counterSessionId)?->counter?->branch_id;
            if ($branchId) {
                return $branchId;
            }
        }

        if ($shiftId) {
            $branchId = Shift::find($shiftId)?->counter?->branch_id;
            if ($branchId) {
                return $branchId;
            }
        }

        return null;
    }

    /**
     * Generate the next order number for a branch: YYMMDD-HHmm-{branchId}-{00001}.
     * The per-branch sequence never resets and is incremented under a row
     * lock so concurrent orders on the same branch can't collide.
     */
    private function generateOrderNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::where('id', $branchId)->lockForUpdate()->first();

            if (!$branch) {
                throw new \RuntimeException("Branch {$branchId} not found for order numbering.");
            }

            $branch->increment('last_order_sequence');

            $sequence = str_pad((string) $branch->last_order_sequence, 5, '0', STR_PAD_LEFT);

            return now()->format('ymd-Hi') . '-' . $branchId . '-' . $sequence;
        });
    }
    
   public function getHoldOrders(Request $request)
{
    $validator = Validator::make($request->all(), [
        'order_id' => 'required|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $orderId = $request->input('order_id');

    $order = Order::with('orderItems')
        ->where('id', $orderId)
        ->has('orderItems')
        ->first();

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'No items found for this Order ID'
        ], 404);
    }

    // Get all order items for this order
    $orderItems = $order->orderItems;
    
    // Group items by product name and price
    $groupedItems = [];
    foreach ($orderItems as $item) {
        $key = $item->name . '-' . $item->sellingPrice;
        
        if (!isset($groupedItems[$key])) {
            $groupedItems[$key] = [];
        }
        $groupedItems[$key][] = $item;
    }
    
    // Process each group of duplicate items
    foreach ($groupedItems as $key => $items) {
        if (count($items) > 1) {
            // Keep the first item and delete the others
            $firstItem = array_shift($items);
            
            foreach ($items as $duplicateItem) {
                // Add quantity to the first item
                $firstItem->quantity += $duplicateItem->quantity;
                $firstItem->subtotal = floatval($firstItem->subtotal) + floatval($duplicateItem->subtotal);
                
                // Delete the duplicate item
                $duplicateItem->delete();
            }
            
            // Save the updated first item
            $firstItem->save();
        }
    }
    
    // Reload the order with updated items
    $order->load('orderItems');

    return response()->json([
        'status' => true,
        'message' => 'Table Order items loaded',
        'data' => $order,
    ], 200);
}

    public function addOrderItems(Request $request)
    {
        $user = $request->user();
        $order = $user->orders()->latest()->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'No order record found for the user.'
            ], 404);
        }

        $orderId = $order->id;

        $savedItems = [];

        foreach ($request->items as $item) {
            $orderItem = new OrderItem();
            $orderItem->name = $item['name'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->barcode = $item['barcode'];
            $orderItem->category = $item['category'] ?? null;
            $orderItem->costPrice = $item['costPrice'];
            $orderItem->sellingPrice = $item['sellingPrice'];
            $orderItem->stock = $item['stock'];
            $orderItem->subtotal = $item['subtotal'];
            $orderItem->unit = $item['unit'] ?? null;
            $orderItem->order_id = $orderId;
            $orderItem->save();

            $savedItems[] = $orderItem;
        }

        return response()->json([
            'status' => true,
            'message' => 'orderItems added successfully',
            'data' => $savedItems,
        ], 200);
    }
    
    /**
     * Cancel a pending order: mark it cancelled, free up its table, and log
     * an audit trail entry (same pattern as truck-expense/tyre deletions)
     * since this voids items that were already rung in.
     */
    public function cancelOrder(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::with('orderItems')->find($orderId);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'This order is already cancelled',
            ], 422);
        }

        if ($order->status === 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'A completed order cannot be cancelled',
            ], 422);
        }

        DB::transaction(function () use ($order, $request) {
            DeletionLog::create([
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->name,
                'user_email' => $request->user()->email,
                'action' => 'cancel',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'reason' => $request->reason,
                'entity_snapshot' => $order->toArray(),
                'created_at' => now(),
            ]);

            $order->update(['status' => 'cancelled']);

            Table::where('order_id', $order->id)->update([
                'status' => true,
                'payment_status' => 'completed',
                'order_id' => null,
                'server_id' => null,
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Order cancelled successfully',
        ], 200);
    }

    public function addOrderAddons(Request $request, $orderId)
    {

        if (!$orderId) {
            return response()->json([
                'status' => false,
                'message' => 'No orderId found.'
            ], 404);
        }

        $savedItems = [];

        foreach ($request->items as $item) {
            $orderItem = new OrderItem();
            $orderItem->name = $item['name'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->barcode = $item['barcode'];
            $orderItem->category = $item['category'] ?? null;
            $orderItem->costPrice = $item['costPrice'];
            $orderItem->sellingPrice = $item['sellingPrice'];
            $orderItem->stock = $item['stock'];
            $orderItem->subtotal = $item['subtotal'];
            $orderItem->unit = $item['unit'] ?? null;
            $orderItem->order_id = $orderId;
            $orderItem->save();

            $savedItems[] = $orderItem;
        }

        return response()->json([
            'status' => true,
            'message' => 'order addOns added successfully',
            'data' => $savedItems,
        ], 200);
    }
}
