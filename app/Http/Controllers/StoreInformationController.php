<?php

namespace App\Http\Controllers;

use App\Models\StoreInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StoreInformationController extends Controller
{
    public function getStoreInfo()
    {
        $storeInformation = StoreInformation::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Store Information fetched successfully',
            'data' => $storeInformation,
        ]);
    }

    public function show($id)
    {
        $storeInfo = StoreInformation::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Store Information fetched successfully',
            'data' => $storeInfo,
        ]);
    }

    public function addStoreInfo(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $storeInfo = StoreInformation::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Store Information added successfully',
            'data' => $storeInfo,
        ], 201);
    }

    public function updateStoreInfo(Request $request, $id)
    {
        $storeInfo = StoreInformation::find($id);

        if (!$storeInfo) {
            return response()->json([
                'status' => false,
                'message' => 'Store Information not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), $this->rules(true, $storeInfo->id));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $storeInfo->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Store Information updated successfully',
            'data' => $storeInfo,
        ], 200);
    }

    public function destroy($id)
    {
        $storeInfo = StoreInformation::find($id);

        if (!$storeInfo) {
            return response()->json([
                'status' => false,
                'message' => 'Store Information not found',
            ], 404);
        }

        if ($storeInfo->invoices()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Company information is used by invoices and cannot be deleted',
            ], 409);
        }

        $storeInfo->delete();

        return response()->json([
            'status' => true,
            'message' => 'Store Information deleted successfully',
        ]);
    }

    private function rules(bool $isUpdate = false, ?int $id = null): array
    {
        $required = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return [
            'storeName' => [
                ...$required,
                'max:255',
                Rule::unique('store_information', 'storeName')->ignore($id),
            ],
            'address' => [...$required, 'max:255'],
            'phone' => [...$required, 'max:255'],
            'email' => [
                'nullable',
                'max:255',
                Rule::unique('store_information', 'email')->ignore($id),
            ],
            'taxId' => ['nullable', 'max:255'],
            'gst' => ['nullable', 'max:255'],
            'sst' => ['nullable', 'max:255'],
            'logo' => ['nullable', 'max:255'],
            'currency' => [...$required, 'max:255'],
            'wh_tax_percentage' => ['nullable', 'numeric', 'min:0'],
            'sst_withholding_tax_percentage' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
