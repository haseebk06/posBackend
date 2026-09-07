<?php

namespace App\Http\Controllers;

use App\Models\TruckExpense;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckExpenseController extends Controller {

    public function get() {
        try {
            $expenses = TruckExpense::query()
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['data' => $expenses]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function add(Request $request) {
        try {
            $validated = $request->validate([
                'truck_number' => 'required|string|max:255',
                'date' => 'required|date',
                'advance' => 'nullable|numeric|min:0',
                'diesel_liters' => 'nullable|numeric|min:0',
                'diesel_rate_per_liter' => 'nullable|numeric|min:0',
                'diesel_location' => 'nullable|string|max:255',
                'spare_parts' => 'nullable|array',
                'spare_parts.*.name' => 'string|max:255',
                'spare_parts.*.amount' => 'numeric|min:0',
                'maintenance_details' => 'nullable|array',
                'maintenance_details.*.detail' => 'string|max:255',
                'maintenance_details.*.cost' => 'numeric|min:0',
            ]);

            $expense = TruckExpense::create($validated);

            return response()->json([
                'message' => 'Truck expense record created successfully',
                'data' => $expense,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $expense = TruckExpense::findOrFail($id);

            $validated = $request->validate([
                'truck_number' => 'required|string|max:255',
                'date' => 'required|date',
                'advance' => 'nullable|numeric|min:0',
                'diesel_liters' => 'nullable|numeric|min:0',
                'diesel_rate_per_liter' => 'nullable|numeric|min:0',
                'diesel_location' => 'nullable|string|max:255',
                'spare_parts' => 'nullable|array',
                'spare_parts.*.name' => 'string|max:255',
                'spare_parts.*.amount' => 'numeric|min:0',
                'maintenance_details' => 'nullable|array',
                'maintenance_details.*.detail' => 'string|max:255',
                'maintenance_details.*.cost' => 'numeric|min:0',
            ]);

            $expense->update($validated);

            return response()->json([
                'message' => 'Truck expense record updated successfully',
                'data' => $expense,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        try {
            $expense = TruckExpense::findOrFail($id);
            $expense->delete();

            return response()->json(['message' => 'Truck expense record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function restore($id) {
        try {
            $expense = TruckExpense::onlyTrashed()->findOrFail($id);
            $expense->restore();

            return response()->json(['message' => 'Truck expense record restored successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deletionLogs() {
        try {
            $deletedExpenses = TruckExpense::onlyTrashed()
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json(['data' => $deletedExpenses]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
