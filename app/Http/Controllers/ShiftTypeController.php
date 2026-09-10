<?php

namespace App\Http\Controllers;

use App\Models\ShiftType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftTypeController extends Controller
{
    public function index()
    {
        return response()->json(ShiftType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shiftType = ShiftType::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Shift type created successfully.',
            'data' => $shiftType,
        ], 201);
    }

    public function destroy($id)
    {
        $shiftType = ShiftType::findOrFail($id);
        $shiftType->delete();

        return response()->json(['message' => 'Shift type deleted successfully.']);
    }
}
