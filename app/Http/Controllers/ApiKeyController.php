<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index()
    {
        $keys = Auth::user()->apiKeys;
        
        return response()->json([
            'status' => 'success',
            'data' => $keys->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key' => $key->key,
                    'last_used_at' => $key->last_used_at ? $key->last_used_at->format('Y-m-d H:i:s') : null,
                    'expires_at' => $key->expires_at ? $key->expires_at->format('Y-m-d H:i:s') : null,
                    'is_active' => $key->is_active,
                    'created_at' => $key->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }
    
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'expires_at' => 'nullable|date|after:today',
    ]);
    
    $key = ApiKey::generateFor(
        Auth::id(), 
        $validated['name'],
        $validated['expires_at'] ?? null
    );
    
    // Return JSON response with the API key
    return response()->json([
        'status' => 'success',
        'message' => 'API key created successfully.',
        'data' => [
            'key' => $key->key,
            'name' => $key->name,
            'expires_at' => $key->expires_at ? $key->expires_at->format('Y-m-d') : null,
            'created_at' => $key->created_at->format('Y-m-d H:i:s')
        ]
    ], 201);
}
    
public function destroy(ApiKey $apiKey)
{
    // Security check
    if ($apiKey->user_id !== Auth::id()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action'
        ], 403);
    }
    
    $apiKey->delete();
    
    return response()->json([
        'status' => 'success',
        'message' => 'API key deleted successfully'
    ]);
}

public function toggle(ApiKey $apiKey)
{
    // Security check
    if ($apiKey->user_id !== Auth::id()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized action'
        ], 403);
    }
    
    $apiKey->update(['is_active' => !$apiKey->is_active]);
    
    return response()->json([
        'status' => 'success',
        'message' => 'API key status updated successfully',
        'data' => [
            'is_active' => $apiKey->is_active
        ]
    ]);
}
}