<?php

namespace App\Http\Controllers;

use App\Models\HoldCart;
use App\Models\Sale;
use App\Models\SoldItems;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    public function getSales()
    {
        $sales = Sale::with(['soldItems' => function ($query) {
            $query->where('is_return', false);
        }])->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Sales fetched successfully',
            'data' => $sales,
        ], 200);
    }

    public function getAllTransactions()
    {
        $sales = Sale::with(['soldItems' => function ($query) {
            $query->where('is_return', false);
        }])
            ->where('is_return', false)
            ->whereNull('return_reason')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Sales fetched successfully',
            'data' => $sales,
        ], 200);
    }

    public function getReturns()
    {
        $returns = Sale::with('soldItems')
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

    public function addSales(Request $request)
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

        $sale = new Sale();
        $sale->user_id = $request->user()->id;
        $sale->total = $request["total"];
        $sale->tax = $request["tax"];
        $sale->discount = $request["discount"];
        $sale->finalTotal = $request["finalTotal"];
        $sale->paymentMethod = $request["paymentMethod"];
        $sale->amountReceived = $request["amountReceived"];
        $sale->changeAmount = $request["changeAmount"];
        $sale->save();

        return response()->json([
            'status' => true,
            'message' => 'Stock added successfully',
            'data' => $sale,
        ], 200);
    }

    public function addSoldItems(Request $request)
    {
        $user = $request->user();
        $sale = $user->Sales()->latest()->first();

        if (!$sale) {
            return response()->json([
                'status' => false,
                'message' => 'No sale record found for the user.'
            ], 404);
        }

        $saleId = $sale->id;

        $savedItems = [];

        foreach ($request->items as $item) {
            $soldItem = new SoldItems();
            $soldItem->name = $item['name'];
            $soldItem->quantity = $item['quantity'];
            $soldItem->barcode = $item['barcode'];
            $soldItem->category = $item['category'] ?? null;
            $soldItem->costPrice = $item['costPrice'];
            $soldItem->sellingPrice = $item['sellingPrice'];
            $soldItem->stock = $item['stock'];
            $soldItem->subtotal = $item['subtotal'];
            $soldItem->unit = $item['unit'] ?? null;
            $soldItem->sale_id = $saleId;
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
            $holdItem->barcode = $item['barcode'];
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
            'saleId' => 'required|exists:sales,id',
            'itemId' => 'required|exists:sold_items,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $originalSale = Sale::with('soldItems')->findOrFail($validated['saleId']);
            $item = SoldItems::findOrFail($validated['itemId']);

            // Validate quantity
            if ($validated['quantity'] > $item->quantity) {
                throw new \Exception('Return quantity cannot exceed original sale quantity');
            }

            // Update stock
            $product = Stock::where('barcode', $item->barcode)->first();
            if (!$product) {
                throw new \Exception('Product not found in inventory');
            }
            $product->stock += $validated['quantity'];
            $product->save();

            // Create a return record for the item
            $returnItem = new SoldItems();
            $returnItem->sale_id = $originalSale->id;
            $returnItem->name = $item->name;
            $returnItem->quantity = -$validated['quantity'];
            $returnItem->barcode = $item->barcode;
            $returnItem->category = $item->category;
            $returnItem->costPrice = $item->costPrice;
            $returnItem->sellingPrice = $item->sellingPrice;
            $returnItem->stock = $product->stock;
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

            // Recalculate sale totals
            $this->recalculateSaleTotals($originalSale);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Item returned successfully',
                'data' => $originalSale->fresh(['soldItems'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function recalculateSaleTotals(Sale $sale)
    {
        $sale->load('soldItems');

        // Calculate new totals from non-returned items only
        $sale->total = $sale->soldItems->where('is_return', false)->sum('subtotal');
        $sale->finalTotal = $sale->total + $sale->tax - $sale->discount;
        $sale->save();
    }

    public function returnEntireSale(Request $request)
    {
        $validated = $request->validate([
            'saleId' => 'required|exists:sales,id',
            'reason' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $originalSale = Sale::with('soldItems')->findOrFail($validated['saleId']);

            // Validate sale can be returned
            if ($originalSale->is_return) {
                throw new \Exception('Cannot return an already returned sale');
            }

            if ($originalSale->status === 'refunded') {
                throw new \Exception('This sale has already been fully refunded');
            }

            // 1. Update original sale status
            $originalSale->update([
                'status' => 'returned',
                'return_reason' => $validated['reason'],
            ]);

            // 2. Create the return sale (with negative amounts)
            $returnSale = Sale::create([
                'total' => -$originalSale->total,
                'tax' => -$originalSale->tax,
                'discount' => -$originalSale->discount,
                'finalTotal' => -$originalSale->finalTotal,
                'paymentMethod' => 'return',
                'amountReceived' => 0,
                'changeAmount' => 0,
                'original_sale_id' => $originalSale->id,
                'return_reason' => $validated['reason'],
                'user_id' => $request->user()->id,
                'is_return' => true,
                'status' => 'refunded'
            ]);

            // 3. Process each item
            foreach ($originalSale->soldItems as $item) {
                // Update stock
                $product = Stock::where('barcode', $item->barcode)->first();
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }

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
                    'sale_id' => $returnSale->id
                ]);

                // Mark original item as returned
                $item->update(['is_return' => true]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Entire sale returned successfully',
                'data' => [
                    'original_sale' => $originalSale,
                    'return_sale' => $returnSale
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
