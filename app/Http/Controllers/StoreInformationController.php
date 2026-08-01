<?php

namespace App\Http\Controllers;

use App\Models\StoreInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreInformationController extends Controller
{
    // There is only ever one store's settings, so this always returns the
    // single existing row (or null if it hasn't been set up yet) rather than
    // a list - the frontend renders it as a single settings form.
    public function getStoreInfo()
    {
        $storeInfo = StoreInformation::first();

        return response()->json([
            'status' => true,
            'message' => 'Store information fetched successfully',
            'data' => $storeInfo,
        ], 200);
    }

    // Create-or-update the single store settings row. Previously this always
    // inserted a new row, so saving Settings a second time hit the unique
    // constraint on storeName/email and failed - now it updates the existing
    // row (excluded from the uniqueness check against itself) if one exists.
    public function addStoreInfo(Request $request)
    {
        $existing = StoreInformation::first();

        $validator = Validator::make($request->all(), [
            'storeName' => [
                'required', 'max:255',
                Rule::unique('store_information', 'storeName')->ignore($existing?->id),
            ],
            'address' => 'required|max:255',
            'phone' => 'required|max:255',
            'email' => [
                'nullable', 'max:255',
                Rule::unique('store_information', 'email')->ignore($existing?->id),
            ],
            'currency' => 'required|max:255',
            'taxId' => 'nullable|max:255',
            'logo' => 'nullable|string',
            'gstPercentage' => 'nullable|numeric|min:0|max:100',
            'serviceChargePercentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $storeInfo = $existing ?? new StoreInformation();
        $storeInfo->fill($request->only([
            'storeName', 'address', 'phone', 'email', 'taxId', 'logo', 'currency',
            'gstPercentage', 'serviceChargePercentage',
        ]));
        $storeInfo->save();

        return response()->json([
            'status' => true,
            'message' => 'Store information saved successfully',
            'data' => $storeInfo,
        ], 200);
    }
}
