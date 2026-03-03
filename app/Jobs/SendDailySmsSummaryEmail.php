<?php

namespace App\Jobs;

use App\Mail\DailySmsSummary;
use App\Models\Message;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailySmsSummaryEmail
{
    use Dispatchable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Daily SMS Summary Job started');

        try {
            // Get all users who have an SMS wallet (i.e. SMS customers)
            $userIds = SmsWallet::where('status', 'active')->pluck('user_id');

            $users = User::whereIn('id', $userIds)->get();

            if ($users->isEmpty()) {
                Log::info('No active SMS customers found. Skipping.');
                return;
            }

            $yesterday = now()->subDay();
            $startOfYesterday = $yesterday->copy()->startOfDay();
            $endOfYesterday = $yesterday->copy()->endOfDay();

            foreach ($users as $user) {
                try {
                    $this->sendSummaryToUser($user, $startOfYesterday, $endOfYesterday);
                } catch (\Exception $e) {
                    Log::error('Failed to send daily SMS summary to user', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Daily SMS Summary Job completed', [
                'users_processed' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Daily SMS Summary Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Gather stats and send email to a single user.
     */
    protected function sendSummaryToUser(User $user, $startOfDay, $endOfDay): void
    {
        // Wallet balance
        $wallet = $user->sms_wallet;
        $walletBalance = $wallet ? $wallet->balance : 0;
        $currency = $wallet ? $wallet->currency : 'NGN';

        // Messages sent yesterday
        $messageStats = Message::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->select(
                DB::raw('COUNT(*) as total_messages'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'),
                DB::raw('COALESCE(SUM(total_recipients), 0) as total_recipients'),
                DB::raw('COALESCE(SUM(cost), 0) as total_cost')
            )
            ->first();

        // Total spent yesterday (from wallet transactions)
        $totalSpent = SmsTransaction::where('user_id', $user->id)
            ->where('type', 'message_payment')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');

        // Total deposited yesterday
        $totalDeposited = SmsTransaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');

        $data = [
            'user_name' => $user->name,
            'date' => $startOfDay->format('l, M d, Y'),
            'wallet_balance' => number_format($walletBalance, 2),
            'currency' => $currency,
            'total_messages' => $messageStats->total_messages ?? 0,
            'delivered_count' => $messageStats->delivered_count ?? 0,
            'failed_count' => $messageStats->failed_count ?? 0,
            'total_recipients' => $messageStats->total_recipients ?? 0,
            'total_cost' => number_format($messageStats->total_cost ?? 0, 2),
            'total_spent' => number_format($totalSpent, 2),
            'total_deposited' => number_format($totalDeposited, 2),
        ];

        Mail::to($user->email)->send(new DailySmsSummary($data));
    }
}
