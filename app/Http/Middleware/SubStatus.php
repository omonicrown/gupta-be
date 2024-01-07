<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubStatus extends Controller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $current = Carbon::today();
        $dates = new Carbon(auth()->user()->sub_end);
        if ($dates <= $current) {
            try {
                DB::beginTransaction();
                $Link = User::where('id', auth()->user()->id)->first();
                $Link->sub_status = 'inactive';
                $Link->save();
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollback();
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        if (auth()->user()->sub_status === 'active') {
            return $next($request);
        }

        if (auth()->user()->sub_status === 'trial') {
            return $next($request);
        }

        return $this->success('Subscription Expired', 'sub_expired');
    }
}
