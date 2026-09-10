<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\CounterCashierAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CounterAssignmentController extends Controller
{
    public function cashiers($id)
    {
        $counter = Counter::findOrFail($id);

        return response()->json($counter->assignedCashiers()->get());
    }

    public function assign(Request $request, $id)
    {
        $counter = Counter::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cashier_ids' => 'present|array',
            'cashier_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $counter->assignedCashiers()->sync($request->cashier_ids);

        return response()->json([
            'message' => 'Cashier assignments updated successfully.',
            'data' => $counter->assignedCashiers()->get(),
        ]);
    }
}
