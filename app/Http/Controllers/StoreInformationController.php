<?php

namespace App\Http\Controllers;

use App\Models\StoreInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreInformationController extends Controller
{
    public function getStoreInfo()
    {
        $sales = StoreInformation::with('soldItems')->get();

        return response()->json([
            'status' => true,
            'message' => 'Stocks fetched successfully',
            'data' => $sales,
        ], 201);
    }

    public function addStoreInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'storeName' => 'required|unique:store_information,storeName|max:255',
            'address' => 'required|max:255',
            'phone' => 'required|max:255',
            'email' => 'unique:store_information,email|max:255',
            'currency' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $storeInfo = StoreInformation::create($request->all());
        $storeInfo->save();

        return response()->json([
            'status' => true,
            'message' => 'Store Information added successfully',
            'data' => $storeInfo,
        ], 200);
    }

    public function updateStoreInfo(Request $request, $id)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'items' => 'sometimes|max:255',
            'total' => 'sometimes|max:255',
            'tax' => 'sometimes|max:255',
            'discount' => 'sometimes|max:255',
            'finalTotal' => 'sometimes|max:255',
            'paymentMethod' => 'sometimes|max:255',
            'amountReceived' => 'sometimes|max:255',
            'changeAmount' => 'sometimes|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $sale = StoreInformation::where('id', $id)
            ->where('id', $request->id)
            ->first();

        if (!$sale) {
            return response()->json([
                'status' => false,
                'message' => 'Stock item not found or you don\'t have permission to update it',
            ], 404);
        }

        // Update only the fields that were provided in the request
        $sale->fill($request->only([
            'items',
            'total',
            'tax',
            'discount',
            'finalTotal',
            'paymentMethod',
            'amountReceived',
            'changeAmount'
        ]));

        $sale->user_id = $request->user()->id;
        $sale->save();

        return response()->json([
            'status' => true,
            'message' => 'Stock updated successfully',
            'data' => $sale,
        ], 200);
    }
}
