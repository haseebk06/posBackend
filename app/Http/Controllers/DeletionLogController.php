<?php

namespace App\Http\Controllers;

use App\Models\DeletionLog;
use Illuminate\Http\Request;

class DeletionLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        return response()->json([
            'status' => true,
            'data' => DeletionLog::with('user')->latest('created_at')->get(),
        ]);
    }
}