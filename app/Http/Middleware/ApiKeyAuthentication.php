<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next)
    {
        // Check for API key in header
        $apiKey = $request->header('X-API-Key');
        
        // Alternative: Check for API key in query string (less secure)
        // $apiKey = $apiKey ?? $request->query('api_key');
        
        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key is missing'
            ], 401);
        }

        // Find and validate the API key
        $keyModel = ApiKey::where('key', $apiKey)->first();
        
        if (!$keyModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key'
            ], 401);
        }

        if (!$keyModel->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key has been deactivated'
            ], 401);
        }

        if ($keyModel->expires_at && $keyModel->expires_at->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key has expired'
            ], 401);
        }

        // Update last used timestamp
        $keyModel->update(['last_used_at' => now()]);
        
        // Set the authenticated user
        $request->setUserResolver(function () use ($keyModel) {
            return $keyModel->user;
        });

        return $next($request);
    }
}