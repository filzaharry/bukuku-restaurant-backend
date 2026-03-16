<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Get API welcome message
     */
    public function index()
    {
        return response()->json(['message' => 'Hello World']);
    }

    /**
     * Test endpoint
     */
    public function test()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => 'API is working!',
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Test POST endpoint
     */
    public function testPost(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'received_name' => $validated['name'],
                'received_email' => $validated['email'] ?? null
            ]
        ]);
    }
}
