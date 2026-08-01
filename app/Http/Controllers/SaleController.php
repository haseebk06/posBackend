<?php

namespace App\Http\Controllers;

use App\Models\HoldCart;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\SoldItems;
use App\Models\Retrun;
use App\Models\RetrunItem;
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
    
    public function getCurrentShiftSales($id, $userId)
    {
        $sales = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSales = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('finalTotal');

        $totalGrossSales = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('total');

        $totalDiscount = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('discount');

        $totalGst = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('gst');

        $totalServiceCharges = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('service_charges');

        return response()->json([
            'status' => true,
            'message' => 'Sales fetched successfully',
            'data' => $sales,
            'total_sales' => $totalSales,
            'gross_sales' => $totalGrossSales,
            'total_discount' => $totalDiscount,
            'total_gst' => $totalGst,
            'total_service_charges' => $totalServiceCharges,
        ], 200);
    }
    
    public function getCurrentShiftRetruns($id, $userId)
    {
        $returns = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRetruns = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('finalTotal');

        $totalGrossRetruns = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->sum('total');

        return response()->json([
            'status' => true,
            'message' => 'retruns fetched successfully',
            'data' => $returns,
            'total_retruns' => $totalRetruns,
            'gross_retruns' => $totalGrossRetruns,
        ], 200);
    }

    public function getPreviousShiftSales($counterId, $userId)
    {
        $previousShift = Shift::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousShift) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter today',
                'shift_id' => null,
                'data' => [],
                'total_sales' => 0,
            ], 200);
        }

        $sales = Sale::where('shift_id', $previousShift->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSales = Sale::where('shift_id', $previousShift->id)
            ->sum('finalTotal');

        $totalGrossSales = Sale::where('shift_id', $previousShift->id)
            ->sum('total');

        $totalDiscount = Sale::where('shift_id', $previousShift->id)
            ->sum('discount');

        return response()->json([
            'status' => true,
            'message' => 'Previous shift sales fetched successfully',
            'shift_id' => $previousShift->id,
            'data' => $sales,
            'total_sales' => $totalSales,
            'gross_sales' => $totalGrossSales,
            'total_discount' => $totalDiscount,
        ], 200);
    }

    public function getPreviousShiftRetruns($counterId, $userId)
    {
        $previousShift = Shift::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousShift) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter today',
                'shift_id' => null,
                'data' => [],
                'total_sales' => 0,
            ], 200);
        }

        $returns = Retrun::where('shift_id', $previousShift->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRetruns = Retrun::where('shift_id', $previousShift->id)
            ->sum('finalTotal');

        $totalGrossRetruns = Retrun::where('shift_id', $previousShift->id)
            ->sum('total');

        return response()->json([
            'status' => true,
            'message' => 'Previous shift retrun fetched successfully',
            'shift_id' => $previousShift->id,
            'data' => $returns,
            'total_retruns' => $totalRetruns,
            'gross_retruns' => $totalGrossRetruns,
        ], 200);
    }

    public function getCurrentShiftItemsSold($id, $userId)
    {
        $items = SoldItems::query()
            ->join('sales', 'sold_items.sale_id', '=', 'sales.id')
            ->where('sales.user_id', $userId)
            ->where('sales.shift_id', $id)
            ->where('sold_items.is_return', false)
            ->selectRaw('sold_items.name, sold_items.category, sold_items.unit, SUM(sold_items.quantity) as quantity, SUM(sold_items.subtotal) as total_amount')
            ->groupBy('sold_items.name', 'sold_items.category', 'sold_items.unit')
            ->orderByDesc('quantity')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Current shift items fetched successfully',
            'data' => $items,
        ], 200);
    }

    public function getPreviousShiftItemsSold($counterId, $userId)
    {
        $previousShift = Shift::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousShift) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter',
                'shift_id' => null,
                'data' => [],
            ], 200);
        }

        $items = SoldItems::query()
            ->join('sales', 'sold_items.sale_id', '=', 'sales.id')
            ->where('sales.shift_id', $previousShift->id)
            ->where('sold_items.is_return', false)
            ->selectRaw('sold_items.name, sold_items.category, sold_items.unit, SUM(sold_items.quantity) as quantity, SUM(sold_items.subtotal) as total_amount')
            ->groupBy('sold_items.name', 'sold_items.category', 'sold_items.unit')
            ->orderByDesc('quantity')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Previous shift items fetched successfully',
            'shift_id' => $previousShift->id,
            'data' => $items,
        ], 200);
    }

    public function getAllTransactions()
    {
        // Sales still eligible to be picked for a return: exclude ones that have
        // already been returned in full (nothing left to return), but keep
        // partially-returned sales selectable so remaining items can still be returned.
        $sales = Sale::with(['soldItems' => function ($query) {
            $query->where('is_return', false);
        }])
            ->where('status', '!=', 'returned')
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
        $returns = Retrun::with('retrunItems')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched successfully',
            'data' => $returns,
        ], 200);
    }
    
    public function addReturns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:sales,id',
            'shift_id' => 'required|integer|exists:shifts,id',
            'paymentMethod' => 'required|string|in:cash,card,mobile',
            'reason' => 'required|string|max:255',
            'total' => 'required|numeric|min:0',
            'finalTotal' => 'required|numeric|min:0',
            'amountReceived' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:sold_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create the return record
            $return = new Retrun();
            $return->user_id = $request->user()->id;
            $return->sale_id = $request->sale_id;
            $return->total = $request->total;
            $return->tax = $request->tax ?? 0;
            $return->gst = $request->gst ?? 0;
            $return->service_charges = $request->service_charges ?? 0;
            $return->shift_id = $request->shift_id;
            $return->discount = $request->discount ?? 0;
            $return->finalTotal = $request->finalTotal;
            $return->paymentMethod = $request->paymentMethod;
            $return->amountReceived = $request->amountReceived;
            $return->changeAmount = $request->changeAmount ?? 0;
            $return->reason = $request->reason;
            $return->save();

            // Save return items and update the original sold items, row-locked so two
            // concurrent return requests against the same sale can't both read the same
            // pre-return quantity and over-return it.
            $savedItems = [];
            foreach ($request->items as $item) {
                $soldItem = SoldItems::where('id', $item['id'])
                    ->where('sale_id', $request->sale_id)
                    ->where('is_return', false)
                    ->lockForUpdate()
                    ->first();

                if (! $soldItem) {
                    throw new \Exception("Item #{$item['id']} was not found on this sale or has already been fully returned.");
                }

                if ($item['quantity'] > $soldItem->quantity) {
                    throw new \Exception("Cannot return {$item['quantity']} of \"{$soldItem->name}\" - only {$soldItem->quantity} remain on this sale.");
                }

                $returnItem = new RetrunItem();
                $returnItem->return_id = $return->id;
                $returnItem->name = $soldItem->name;
                $returnItem->quantity = $item['quantity'];
                $returnItem->barcode = $soldItem->barcode;
                $returnItem->category = $soldItem->category;
                $returnItem->costPrice = $soldItem->costPrice;
                $returnItem->sellingPrice = $soldItem->sellingPrice;
                $returnItem->stock = $soldItem->stock;
                $returnItem->subtotal = $soldItem->sellingPrice * $item['quantity'];
                $returnItem->unit = $soldItem->unit;
                $returnItem->original_quantity = $soldItem->original_quantity ?? $soldItem->quantity;
                $returnItem->save();

                $savedItems[] = $returnItem;

                // Update the original sold item in place: fully returned rows are flagged
                // out of future item-sold/return queries, partially returned rows keep
                // their remaining quantity so they can be returned again later.
                $soldItem->original_quantity = $soldItem->original_quantity ?? $soldItem->quantity;
                $soldItem->return_reason = $request->reason;
                if ($item['quantity'] >= $soldItem->quantity) {
                    $soldItem->is_return = 1;
                } else {
                    $soldItem->quantity -= $item['quantity'];
                    $soldItem->subtotal = $soldItem->sellingPrice * $soldItem->quantity;
                }
                $soldItem->save();
            }

            // Update the original sale's status based on whether any non-returned items
            // remain. The sale's finalTotal/paymentMethod are intentionally left untouched
            // - it stays an immutable record of what was originally sold. The Retrun row
            // created above is the sole source of truth for the refunded amount/method, so
            // reports can net sales and returns per payment method without special-casing
            // full vs. partial returns.
            $originalSale = Sale::find($request->sale_id);
            if ($originalSale) {
                $hasRemainingItems = SoldItems::where('sale_id', $originalSale->id)
                    ->where('is_return', false)
                    ->exists();

                $originalSale->status = $hasRemainingItems ? 'partially_returned' : 'returned';
                $originalSale->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Return processed successfully',
                'data' => [
                    'return' => $return,
                    'items' => $savedItems,
                    'updated_sale' => $originalSale
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to process return: ' . $e->getMessage()
            ], 422);
        }
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
        // 'tax' (a manual ad-hoc percentage the cashier used to type in) is
        // retired - GST/SST and service charges are the only charges applied now.
        $sale->tax = 0;
        $sale->gst = $request["gst"] ?? 0;
        $sale->service_charges = $request["service_charges"] ?? 0;
        $sale->shift_id = $request["shift_id"];
        $sale->discount = $request["discount"];
        $sale->finalTotal = $request["finalTotal"];
        $sale->paymentMethod = $request["paymentMethod"];
        $sale->amountReceived = $request["amountReceived"];
        $sale->changeAmount = $request["changeAmount"];
        $sale->mode = $request["mode"];
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
            $soldItem->barcode = $item['barcode'] ?? null;
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
}
