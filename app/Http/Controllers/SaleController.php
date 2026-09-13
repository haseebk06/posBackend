<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CounterSession;
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
        $sales = Sale::with([
            'soldItems' => function ($query) {
                $query->where('is_return', false);
            },
            'order:id,order_number',
        ])->orderBy('created_at', 'desc')
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
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();
    
        $totalSales = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('finalTotal');
    
        $totalGrossSales = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('total');
    
        $totalGst = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('gst');
    
        $totalServiceCharges = Sale::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('service_charges');
    
        return response()->json([
            'status' => true,
            'message' => 'Sales fetched successfully',
            'data' => $sales,
            'total_sales' => $totalSales,
            'gross_sales' => $totalGrossSales,
            'total_gst' => $totalGst,
            'total_service_charges' => $totalServiceCharges,
        ], 200);
    }
        
    public function getCurrentShiftRetruns($id, $userId)
    {
        $returns = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRetruns = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('finalTotal');

        $totalGrossRetruns = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('total');
    
        $totalGst = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('gst');
    
        $totalServiceCharges = Retrun::where('user_id', $userId)
            ->where('shift_id', $id)
            ->whereDate('created_at', today())
            ->sum('service_charges');

        return response()->json([
            'status' => true,
            'message' => 'retruns fetched successfully',
            'data' => $returns,
            'total_retruns' => $totalRetruns,
            'gross_retruns' => $totalGrossRetruns,
            'total_gst' => $totalGst,
            'total_service_charges' => $totalServiceCharges,
        ], 200);
    }

    public function getPreviousShiftSales($counterId, $userId)
    {
        // Counters are linked via CounterSession under the Business Day
        // workflow, not Shift.counter_id (which is no longer set), so the
        // "previous shift for this counter" has to be resolved through the
        // most recently closed session on this counter today.
        $previousSession = CounterSession::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->whereDate('start_time', today())
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousSession) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter today',
                'shift_id' => null,
                'data' => [],
                'total_sales' => 0,
                'gross_sales' => 0,
                'total_gst' => 0,
                'total_service_charges' => 0,
            ], 200);
        }

        $sales = Sale::where('counter_session_id', $previousSession->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSales = Sale::where('counter_session_id', $previousSession->id)
            ->whereDate('created_at', today())
            ->sum('finalTotal');

        $totalGrossSales = Sale::where('counter_session_id', $previousSession->id)
            ->whereDate('created_at', today())
            ->sum('total');

        $totalGst = Sale::where('counter_session_id', $previousSession->id)
            ->whereDate('created_at', today())
            ->sum('gst');

        $totalServiceCharges = Sale::where('counter_session_id', $previousSession->id)
            ->whereDate('created_at', today())
            ->sum('service_charges');

        return response()->json([
            'status' => true,
            'message' => 'Previous shift sales fetched successfully',
            'shift_id' => $previousSession->shift_id,
            'data' => $sales,
            'total_sales' => $totalSales,
            'gross_sales' => $totalGrossSales,
            'total_gst' => $totalGst,
            'total_service_charges' => $totalServiceCharges,
        ], 200);
    }
    
    public function getPreviousShiftRetruns($counterId, $userId)
    {
        $previousSession = CounterSession::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->whereDate('start_time', today())
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousSession) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter today',
                'shift_id' => null,
                'data' => [],
                'total_sales' => 0,
            ], 200);
        }

        // Retrun rows don't carry a counter_session_id, only shift_id.
        $previousShiftId = $previousSession->shift_id;

        $returns = Retrun::where('shift_id', $previousShiftId)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRetruns = Retrun::where('shift_id', $previousShiftId)
            ->whereDate('created_at', today())
            ->sum('finalTotal');

        $totalGrossRetruns = Retrun::where('shift_id', $previousShiftId)
            ->whereDate('created_at', today())
            ->sum('total');

        $totalGst = Retrun::where('shift_id', $previousShiftId)
            ->whereDate('created_at', today())
            ->sum('gst');

        $totalServiceCharges = Retrun::where('shift_id', $previousShiftId)
            ->whereDate('created_at', today())
            ->sum('service_charges');

        return response()->json([
            'status' => true,
            'message' => 'Previous shift retrun fetched successfully',
            'shift_id' => $previousShiftId,
            'data' => $returns,
            'total_retruns' => $totalRetruns,
            'gross_retruns' => $totalGrossRetruns,
            'total_gst' => $totalGst,
            'total_service_charges' => $totalServiceCharges,
        ], 200);
    }
    
    public function getCurrentShiftItemsSold($shiftId, $userId)
    {
        $sales = Sale::where('user_id', $userId)
            ->where('shift_id', $shiftId)
            ->whereDate('created_at', today())
            ->with(['soldItems' => function($query) {
                $query->where('is_return', false);
            }])
            ->get();
    
        $itemsSold = [];
        
        foreach ($sales as $sale) {
            foreach ($sale->soldItems as $item) {
                $itemName = $item->name;
                
                if (!isset($itemsSold[$itemName])) {
                    $itemsSold[$itemName] = [
                        'name' => $itemName,
                        'quantity' => 0,
                        'total_amount' => 0,
                        'category' => $item->category,
                        'unit' => $item->unit
                    ];
                }
                
                $itemsSold[$itemName]['quantity'] += $item->quantity;
                $itemsSold[$itemName]['total_amount'] += $item->subtotal;
            }
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Current shift items sold fetched successfully',
            'data' => array_values($itemsSold),
        ], 200);
    }

    public function getPreviousShiftItemsSold($counterId, $userId)
    {
        $previousSession = CounterSession::where('status', 'closed')
            ->where('counter_id', $counterId)
            ->whereDate('start_time', today())
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$previousSession) {
            return response()->json([
                'status' => false,
                'message' => 'No previous shift found for this counter today',
                'data' => [],
            ], 200);
        }

        $sales = Sale::where('counter_session_id', $previousSession->id)
            ->whereDate('created_at', today())
            ->with(['soldItems' => function($query) {
                $query->where('is_return', false);
            }])
            ->get();
    
        $itemsSold = [];
        
        foreach ($sales as $sale) {
            foreach ($sale->soldItems as $item) {
                $itemName = $item->name;
                
                if (!isset($itemsSold[$itemName])) {
                    $itemsSold[$itemName] = [
                        'name' => $itemName,
                        'quantity' => 0,
                        'total_amount' => 0,
                        'category' => $item->category,
                        'unit' => $item->unit
                    ];
                }
                
                $itemsSold[$itemName]['quantity'] += $item->quantity;
                $itemsSold[$itemName]['total_amount'] += $item->subtotal;
            }
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Previous shift items sold fetched successfully',
            'data' => array_values($itemsSold),
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
        $returns = Retrun::with(['retrunItems', 'sales.order:id,order_number'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Returns fetched successfully',
            'data' => $returns,
        ], 200);
    }

    /**
     * Generate the next return number for a branch: RTN-YYMMDD-HHmm-{branchId}-{00001}.
     * Separate per-branch sequence from order numbers, same lock-based pattern
     * as OrderController::generateOrderNumber so concurrent returns can't collide.
     */
    private function generateReturnNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::where('id', $branchId)->lockForUpdate()->first();

            if (!$branch) {
                throw new \RuntimeException("Branch {$branchId} not found for return numbering.");
            }

            $branch->increment('last_return_sequence');

            $sequence = str_pad((string) $branch->last_return_sequence, 5, '0', STR_PAD_LEFT);

            return 'RTN-' . now()->format('ymd-Hi') . '-' . $branchId . '-' . $sequence;
        });
    }
    
    public function addReturns(Request $request)
    {
        DB::beginTransaction();
        try {
            // Create the return record
            $return = new Retrun();
            $return->user_id = $request->user()->id;
            $return->sale_id = $request->sale_id;
            $return->total = $request->total;
            $return->tax = $request->tax;
            $return->gst = $request->gst;
            $return->service_charges = $request->service_charges;
            $return->shift_id = $request->shift_id;
            $return->discount = $request->discount;
            $return->finalTotal = $request->finalTotal;
            $return->paymentMethod = $request->paymentMethod;
            $return->amountReceived = $request->amountReceived;
            $return->changeAmount = $request->changeAmount;
            $return->reason = $request->reason;

            $branchId = Shift::find($request->shift_id)?->counter?->branch_id;
            if ($branchId) {
                $return->branch_id = $branchId;
                $return->return_number = $this->generateReturnNumber($branchId);
            }

            $return->save();
    
            // The frontend deliberately only sends {id, quantity} per item --
            // not trusting client-supplied prices/names for a financial
            // operation -- so authoritative name/price/etc always come from
            // the original sold item, matched by id (name alone can collide
            // across two lines on the same sale).
            $savedItems = [];
            foreach ($request->items as $item) {
                $soldItem = SoldItems::find($item['id']);

                if (!$soldItem) {
                    throw new \Exception("Sold item {$item['id']} not found for this sale.");
                }

                $returnQuantity = $item['quantity'];

                // Create return item record
                $returnItem = new RetrunItem();
                $returnItem->return_id = $return->id;
                $returnItem->name = $soldItem->name;
                $returnItem->quantity = $returnQuantity;
                $returnItem->barcode = $soldItem->barcode;
                $returnItem->category = $soldItem->category;
                $returnItem->costPrice = $soldItem->costPrice;
                $returnItem->sellingPrice = $soldItem->sellingPrice;
                $returnItem->stock = $soldItem->stock;
                $returnItem->subtotal = $soldItem->sellingPrice * $returnQuantity;
                $returnItem->unit = $soldItem->unit;
                $returnItem->save();

                $savedItems[] = $returnItem;

                // Update the original sold item's is_return status
                $this->updateSoldItemReturnStatus($soldItem, $returnQuantity);
            }

            // Update the original sale status based on return type
            $originalSale = Sale::find($request->sale_id);
            if ($originalSale) {
                // Check if this is a full return (all items returned)
                $isFullReturn = $this->isFullReturn($request->sale_id);

                if ($isFullReturn) {
                    $originalSale->status = 'returned';
                } else {
                    $originalSale->status = 'partially_returned';
                }
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
            ], 500);
        }
    }
    
    // Helper method to update sold item's return status
    private function updateSoldItemReturnStatus(SoldItems $soldItem, $returnedQuantity)
    {
        if ($returnedQuantity >= $soldItem->quantity) {
            // Mark as fully returned
            $soldItem->is_return = 1;
            $soldItem->return_reason = 'Fully returned';
        } else {
            // For partial returns, reduce the quantity
            $soldItem->quantity -= $returnedQuantity;
            $soldItem->subtotal = $soldItem->sellingPrice * $soldItem->quantity;
            $soldItem->is_return = 0; // Not fully returned
            $soldItem->return_reason = 'Partially returned: ' . $returnedQuantity . ' items returned';
        }
        $soldItem->save();
    }

    private function isFullReturn($saleId)
    {
        // Get all non-returned items from the original sale
        $originalItems = SoldItems::where('sale_id', $saleId)
                                 ->where('is_return', 0)
                                 ->get();
        
        // If there are no non-returned items left, it's a full return
        return $originalItems->isEmpty();
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
            'total' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'service_charges' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'finalTotal' => 'required|numeric',
            'paymentMethod' => 'required|max:255',
            'amountReceived' => 'required|numeric',
            'changeAmount' => 'nullable|numeric',
            'shift_id' => 'required|exists:shifts,id',
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
        $sale->order_id = $request["order_id"] ?? null;
        $sale->total = $request["total"];
        $sale->tax = $request["tax"];
        $sale->gst = $request["gst"];
        $sale->service_charges = $request["service_charges"];
        $sale->shift_id = $request["shift_id"];
        $sale->counter_session_id = $request["counter_session_id"] ?? null;
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
            'data' => $sale->load('order:id,order_number'),
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

            // Create a return record for the item
            $returnItem = new SoldItems();
            $returnItem->sale_id = $originalSale->id;
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

       public function returnEntireSale(Request $request, $id)
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
                'shift_id' => $id,
                'is_return' => true,
                'status' => 'refunded'
            ]);

            // 3. Process each item
            foreach ($originalSale->soldItems as $item) {
                
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
