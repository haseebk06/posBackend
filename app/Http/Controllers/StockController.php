<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    public function getStock()
    {
        $stocks = Stock::all();

        return response()->json([
            'status' => true,
            'message' => 'Stocks fetched successfully',
            'data' => $stocks,
        ], 201);
    }

    public function addStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'barcode' => 'required|max:255|unique:stocks,barcode',
            'category' => 'max:255',
            'costPrice' => 'required|max:255',
            'sellingPrice' => 'required|max:255',
            'stock' => 'required|max:255',
            'unit' => 'max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = new Stock();
        $stock->user_id = $request->user()->id;
        $stock->name = $request["name"];
        $stock->barcode = $request["barcode"];
        $stock->category = $request["category"];
        $stock->costPrice = $request["costPrice"];
        $stock->sellingPrice = $request["sellingPrice"];
        $stock->stock = $request["stock"];
        $stock->unit = $request["unit"];
        $stock->save();

        return response()->json([
            'status' => true,
            'message' => 'Stock added successfully',
            'data' => $stock,
        ], 200);
    }

    public function updateStock(Request $request, $id)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|max:255',
            'barcode' => 'sometimes|max:255|unique:stocks,barcode,' . $id,
            'category' => 'sometimes|max:255',
            'costPrice' => 'sometimes|numeric|min:0',
            'sellingPrice' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'unit' => 'sometimes|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'status' => false,
                'message' => 'Stock item not found',
            ], 404);
        }

        // Update only the fields that were provided in the request
        $stock->fill($request->only([
            'name',
            'barcode',
            'category',
            'costPrice',
            'sellingPrice',
            'stock',
            'unit'
        ]));

        $stock->user_id = $request->user()->id;
        $stock->save();

        return response()->json([
            'status' => true,
            'message' => 'Stock updated successfully',
            'data' => $stock,
        ], 200);
    }

    public function updateMultipleStocks(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.barcode' => 'required|exists:stocks,barcode',
            'updates.*.quantityChange' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['updates'] as $update) {
                $stock = Stock::where('barcode', $update['barcode'])->first();

                if ($stock) {
                    $newStock = (int)$stock->stock + $update['quantityChange'];

                    if ($newStock < 0) {
                        throw new \Exception("Insufficient stock for product {$stock->name}");
                    }

                    $stock->update(['stock' => (string)$newStock]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Stock quantities updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($stockId)
    {
        $stock = Stock::find($stockId);

        if (!$stock) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $stock->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }
}
