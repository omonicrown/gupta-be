<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveWallet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user->wallet) {
            return response()->json([
                'message' => 'Wallet not found. Please contact support.',
            ], 403);
        }
        
        if ($user->wallet->status !== 'active') {
            return response()->json([
                'message' => 'Your wallet is not active. Please contact support.',
            ], 403);
        }
        
        return $next($request);
    }
}