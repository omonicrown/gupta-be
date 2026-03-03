<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendDailySmsSummaryEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    /**
     * Trigger daily SMS summary email job.
     * Called by cron-job.org at 8am WAT daily.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailySmsSummary(Request $request)
    {
        // Validate the secret token
        $token = $request->query('token');
        $expectedToken = config('services.cron.secret_token');

        if (!$expectedToken || $token !== $expectedToken) {
            Log::warning('Unauthorized cron request attempted', [
                'ip' => $request->ip(),
                'endpoint' => 'daily-sms-summary',
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            // Run the job synchronously (no queue worker needed)
            SendDailySmsSummaryEmail::dispatchSync();

            Log::info('Daily SMS Summary job dispatched via cron endpoint');

            return response()->json([
                'status' => 'success',
                'message' => 'Daily SMS summary job dispatched successfully',
                'dispatched_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch daily SMS summary job', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to dispatch job',
            ], 500);
        }
    }
}
