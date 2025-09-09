<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
     public function index()
    {
        $servers = Server::with('table')->get();
        return response()->json($servers);
    }
    
    public function store(Request $request)
    {
        // validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $server = Server::create($request->all());

        return response()->json([
            'message' => 'Server created successfully',
            'data' => $server
        ]);
    }
    
    public function destroy($id)
    {
        $server = Server::findOrFail($id);
        $server->delete();

        return response()->json([
            'message' => 'Server deleted successfully'
        ]);
    }
}
