<?php

namespace App\Http\Controllers;

use App\Models\TruckTyre;
use Illuminate\Http\Request;

class TruckTyreController extends Controller {

    public function get() {
        try {
            $tyres = TruckTyre::query()
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['data' => $tyres]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function add(Request $request) {
        try {
            $validated = $request->validate([
                'truck_number' => 'required|string|max:255',
                'date' => 'required|date',
                'tyre_type' => 'nullable|string|max:255',
                'tyre_quantity' => 'nullable|integer|min:0',
                'tyre_amount' => 'nullable|numeric|min:0',
                'tyre_details' => 'nullable|array',
                'tyre_details.*.detail' => 'string|max:255',
                'tyre_details.*.cost' => 'numeric|min:0',
            ]);

            $tyre = TruckTyre::create($validated);

            return response()->json([
                'message' => 'Truck tyre record created successfully',
                'data' => $tyre,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $tyre = TruckTyre::findOrFail($id);

            $validated = $request->validate([
                'truck_number' => 'required|string|max:255',
                'date' => 'required|date',
                'tyre_type' => 'nullable|string|max:255',
                'tyre_quantity' => 'nullable|integer|min:0',
                'tyre_amount' => 'nullable|numeric|min:0',
                'tyre_details' => 'nullable|array',
                'tyre_details.*.detail' => 'string|max:255',
                'tyre_details.*.cost' => 'numeric|min:0',
            ]);

            $tyre->update($validated);

            return response()->json([
                'message' => 'Truck tyre record updated successfully',
                'data' => $tyre,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        try {
            $tyre = TruckTyre::findOrFail($id);
            $tyre->delete();

            return response()->json(['message' => 'Truck tyre record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function restore($id) {
        try {
            $tyre = TruckTyre::onlyTrashed()->findOrFail($id);
            $tyre->restore();

            return response()->json(['message' => 'Truck tyre record restored successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deletionLogs() {
        try {
            $deletedTyres = TruckTyre::onlyTrashed()
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json(['data' => $deletedTyres]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
