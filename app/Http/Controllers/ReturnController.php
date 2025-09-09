<?php

namespace App\Http\Controllers;
use App\Models\HoldCart;
use App\Models\Return;
use App\Models\Shift;
use App\Models\Stock;
use App\Models\ReturnItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function getReturns()
    {
        $returns = Return::with(['retunrItems' => function ($query) {
            $query->where('is_return', false);
        }])->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched successfully',
            'data' => $returns,
        ], 200);
    }
    
    public function getCurrentShiftReturns($id, $userId)
    {
        $returns = Return::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today()) // only today
            ->orderBy('created_at', 'desc')
            ->get();

        $totalReturns = Return::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today()) // only today
            ->sum('finalTotal');

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched successfully',
            'data' => $returns,
            'total_returns' => $totalReturns,
        ], 200);
    }

    public function getPreviousShiftReturns($counterId, $userId)
    {
        // get the most recent closed shift **today** for this counter
        $previousShift = Shift::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->whereDate('start_time', today()) // only today
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousShift) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter today',
                'shift_id' => null,
                'data' => [],
                'total_returns' => 0,
            ], 200);
        }

        $returns = Return::where('shift_id', $previousShift->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        $totalReturns = Return::where('shift_id', $previousShift->id)
            ->whereDate('created_at', today())
            ->sum('finalTotal');

        return response()->json([
            'status' => true,
            'message' => 'Previous shift Returns fetched successfully',
            'shift_id' => $previousShift->id,
            'data' => $returns,
            'total_returns' => $totalReturns,
        ], 200);
    }

    public function getAllTransactions()
    {
        $returns = Return::with(['soldItems' => function ($query) {
            $query->where('is_return', false);
        }])
            ->where('is_return', false)
            ->whereNull('return_reason')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched successfully',
            'data' => $returns,
        ], 200);
    }

    public function getReturns()
    {
        $returns = Return::with('soldItems')
            ->where('status', "returned")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched successfully',
            'data' => $returns,
        ], 200);
    }

    public function getHoldItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holdId' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $holdId = $request->input('holdId');
        $holdItems = HoldCart::where('holdId', $holdId)->get();

        if ($holdItems->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No items found for this Hold ID'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Hold items loaded',
            'data' => $holdItems,
        ], 200);
    }

    public function addReturns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total' => 'required|max:255',
            'tax' => 'required|max:255',
            'discount' => 'required|max:255',
            'finalTotal' => 'required|max:255',
            'paymentMethod' => 'required|max:255',
            'amountReceived' => 'required|max:255',
            'changeAmount' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $return = new Return();
        $return->user_id = $request->user()->id;
        $return->total = $request["total"];
        $return->tax = $request["tax"];
        $return->shift_id = $request["shift_id"];
        $return->discount = $request["discount"];
        $return->finalTotal = $request["finalTotal"];
        $return->paymentMethod = $request["paymentMethod"];
        $return->amountReceived = $request["amountReceived"];
        $return->changeAmount = $request["changeAmount"];
        $return->mode = $request["mode"];
        $return->save();

        return response()->json([
            'status' => true,
            'message' => 'Stock added successfully',
            'data' => $return,
        ], 200);
    }

    public function addSoldItems(Request $request)
    {
        $user = $request->user();
        $return = $user->Returns()->latest()->first();

        if (!$return) {
            return response()->json([
                'status' => false,
                'message' => 'No Return record found for the user.'
            ], 404);
        }

        $returnId = $return->id;

        $savedItems = [];

        foreach ($request->items as $item) {
            $soldItem = new SoldItems();
            $soldItem->name = $item['name'];
            $soldItem->quantity = $item['quantity'];
            $soldItem->barcode = $item['barcode'] ?? null;
            $soldItem->category = $item['category'] ?? null;
            $soldItem->costPrice = $item['costPrice'];
            $soldItem->sellingPrice = $item['sellingPrice'];
            $soldItem->stock = $item['stock'];
            $soldItem->subtotal = $item['subtotal'];
            $soldItem->unit = $item['unit'] ?? null;
            $soldItem->return_id = $returnId;
            $soldItem->save();

            $savedItems[] = $soldItem;
        }


        return response()->json([
            'status' => true,
            'message' => 'SoldItems added successfully',
            'data' => $savedItems,
        ], 200);
    }

    public function addHoldItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holdId' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $holdId = $request->holdId;

        $user = $request->user();

        $holdItems = [];

        foreach ($request->items as $item) {
            $holdItem = new HoldCart();
            $holdItem->holdId = $holdId;
            $holdItem->name = $item['name'];
            $holdItem->quantity = $item['quantity'];
            $holdItem->barcode = $item['barcode'] ?? null;
            $holdItem->category = $item['category'] ?? null;
            $holdItem->costPrice = $item['costPrice'];
            $holdItem->sellingPrice = $item['sellingPrice'];
            $holdItem->stock = $item['stock'];
            $holdItem->subtotal = $item['subtotal'];
            $holdItem->unit = $item['unit'] ?? null;
            $holdItem->user_id = $user->id;
            $holdItem->save();

            $holdItems[] = $holdItem;
        }


        return response()->json([
            'status' => true,
            'message' => 'Hold added successfully',
            'data' => $holdItems,
        ], 200);
    }

    public function destroy($id)
    {
        $holdCart = HoldCart::where('holdId', $id);

        if (!$holdCart) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $holdCart->delete();

        return response()->json([
            'status' => true,
            'message' => 'Hold Item deleted successfully'
        ], 200);
    }

    // Return
    public function returnItem(Request $request)
    {
        $validated = $request->validate([
            'ReturnId' => 'required|exists:Returns,id',
            'itemId' => 'required|exists:sold_items,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $originalReturn = Return::with('soldItems')->findOrFail($validated['ReturnId']);
            $item = SoldItems::findOrFail($validated['itemId']);

            // Validate quantity
            if ($validated['quantity'] > $item->quantity) {
                throw new \Exception('Return quantity cannot exceed original Return quantity');
            }

            // Create a return record for the item
            $returnItem = new SoldItems();
            $returnItem->return_id = $originalReturn->id;
            $returnItem->name = $item->name;
            $returnItem->quantity = -$validated['quantity'];
            $returnItem->barcode = $item->barcode;
            $returnItem->category = $item->category;
            $returnItem->costPrice = $item->costPrice;
            $returnItem->sellingPrice = $item->sellingPrice;
            $returnItem->stock = 999;
            $returnItem->subtotal = - ($item->sellingPrice * $validated['quantity']);
            $returnItem->unit = $item->unit;
            $returnItem->is_return = true;
            $returnItem->return_reason = $validated['reason'];
            $returnItem->save();

            // Update original item quantity if partial return
            if ($validated['quantity'] < $item->quantity) {
                $item->quantity -= $validated['quantity'];
                $item->subtotal = $item->sellingPrice * $item->quantity;
                $item->save();
            } else {
                $item->delete(); // Full return - remove original item
            }

            // Recalculate Return totals
            $this->recalculateReturnTotals($originalReturn);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Item returned successfully',
                'data' => $originalReturn->fresh(['soldItems'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function recalculateReturnTotals(Return $return)
    {
        $return->load('soldItems');

        // Calculate new totals from non-returned items only
        $return->total = $return->soldItems->where('is_return', false)->sum('subtotal');
        $return->finalTotal = $return->total + $return->tax - $return->discount;
        $return->save();
    }

       public function returnEntireReturn(Request $request, $id)
    {
        $validated = $request->validate([
            'ReturnId' => 'required|exists:Returns,id',
            'reason' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $originalReturn = Return::with('soldItems')->findOrFail($validated['ReturnId']);

            // Validate Return can be returned
            if ($originalReturn->is_return) {
                throw new \Exception('Cannot return an already returned Return');
            }

            if ($originalReturn->status === 'refunded') {
                throw new \Exception('This Return has already been fully refunded');
            }

            // 1. Update original Return status
            $originalReturn->update([
                'status' => 'returned',
                'return_reason' => $validated['reason'],
            ]);

            // 2. Create the return Return (with negative amounts)
            $returnReturn = Return::create([
                'total' => -$originalReturn->total,
                'tax' => -$originalReturn->tax,
                'discount' => -$originalReturn->discount,
                'finalTotal' => -$originalReturn->finalTotal,
                'paymentMethod' => 'return',
                'amountReceived' => 0,
                'changeAmount' => 0,
                'original_return_id' => $originalReturn->id,
                'return_reason' => $validated['reason'],
                'user_id' => $request->user()->id,
                'shift_id' => $id,
                'is_return' => true,
                'status' => 'refunded'
            ]);

            // 3. Process each item
            foreach ($originalReturn->soldItems as $item) {
                
                // Create return item record
                SoldItems::create([
                    'name' => $item->name,
                    'quantity' => -$item->quantity,
                    'original_quantity' => $item->quantity,
                    'barcode' => $item->barcode,
                    'category' => $item->category,
                    'costPrice' => $item->costPrice,
                    'sellingPrice' => $item->sellingPrice,
                    'stock' => $product ? $product->stock : 0,
                    'subtotal' => -$item->subtotal,
                    'unit' => $item->unit,
                    'return_reason' => $validated['reason'],
                    'return_id' => $returnReturn->id
                ]);

                // Mark original item as returned
                $item->update(['is_return' => true]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Entire Return returned successfully',
                'data' => [
                    'original_Return' => $originalReturn,
                    'return_Return' => $returnReturn
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
