<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendMonthlySalesSummary extends Command
{
    protected $signature = 'send:monthly-sales-summary';
    protected $description = 'Send monthly sales summary to users';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Get the first and last day of the last month
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Fetch all users
        $users = User::all();

        foreach ($users as $user) {
            // Get all transactions for the user within the month
            $monthlyTransactions = Transaction::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->get();

            // if ($monthlyTransactions->count() > 0) {
            // Calculate total amount paid and total products sold
            $totalSales = $monthlyTransactions->sum('amount_paid');
            $totalProducts = $monthlyTransactions->sum('product_qty');

            // Prepare data for the email
            $data = [
                'user' => $user,
                'transactions' => $monthlyTransactions,
                'totalSales' => $totalSales,
                'totalProducts' => $totalProducts,
                'month' => $startOfLastMonth->format('F Y'),
            ];

            // Send the email
            Mail::send('emails.monthly_sales_summary', $data, function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Monthly Sales Summary');
            });

            $this->info('Monthly sales summary sent to: ' . $user->email);
            // }
        }

        $this->info('Monthly sales summaries have been sent to all users.');
    }
}
