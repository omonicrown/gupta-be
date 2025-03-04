<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SmsTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get total messages count by status
            $messagesStats = Message::where('user_id', $user->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            // Get total contacts count
            $contactsCount = $user->contacts()->count();
            
            // Get total contact groups count
            $groupsCount = $user->contactGroups()->count();

            $apiKeyCount = $user->apiKeys()->count();
            
            // Get current wallet balance
            $walletBalance = $user->sms_wallet ? $user->sms_wallet->balance : 0;
            
            // Get monthly message sending stats (for the last 6 months)
            $monthlyStats = Message::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(
                    DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                    DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                    DB::raw('count(*) as messages_count'),
                    DB::raw('sum(cost) as total_cost')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            // Format monthly stats
            $formattedMonthlyStats = $monthlyStats->map(function ($item) {
                $date = \Carbon\Carbon::createFromDate($item->year, $item->month, 1);
                return [
                    'month' => $date->format('M Y'),
                    'messages_count' => $item->messages_count,
                    'total_cost' => $item->total_cost,
                ];
            });
            
            return response()->json([
                'messages_stats' => $messagesStats,
                'contacts_count' => $contactsCount,
                'groups_count' => $groupsCount,
                'wallet_balance' => $walletBalance,
                'monthly_stats' => $formattedMonthlyStats,
                'api_key_count' => $apiKeyCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get message analytics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request)
    {
        try {
            $user = $request->user();
            $startDate = $request->get('start_date') ? new \DateTime($request->get('start_date')) : now()->subDays(30);
            $endDate = $request->get('end_date') ? new \DateTime($request->get('end_date')) : now();
            
            // Get daily message stats
            $dailyStats = Message::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as messages_count'),
                    DB::raw('sum(successful_sends) as successful_count'),
                    DB::raw('sum(failed_sends) as failed_count'),
                    DB::raw('sum(cost) as total_cost')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            // Get delivery status distribution
            $deliveryStats = Message::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            // Get top 10 most used sender IDs
            $topSenderIds = Message::where('messages.user_id', $user->id)
                ->whereBetween('messages.created_at', [$startDate, $endDate])
                ->join('sender_ids', 'messages.sender_id', '=', 'sender_ids.id')
                ->select('sender_ids.sender_id as name', DB::raw('count(*) as count'))
                ->groupBy('sender_ids.sender_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
            
            return response()->json([
                'daily_stats' => $dailyStats,
                'delivery_stats' => $deliveryStats,
                'top_sender_ids' => $topSenderIds,
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch message analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get financial analytics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function financial(Request $request)
    {
        try {
            $user = $request->user();
            $startDate = $request->get('start_date') ? new \DateTime($request->get('start_date')) : now()->subDays(30);
            $endDate = $request->get('end_date') ? new \DateTime($request->get('end_date')) : now();
            
            // Get daily transaction stats
            $dailyStats = SmsTransaction::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('sum(CASE WHEN type = \'deposit\' THEN amount ELSE 0 END) as deposits'),
                    DB::raw('sum(CASE WHEN type = \'message_payment\' THEN amount ELSE 0 END) as spending')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            // Get transaction type distribution
            $transactionStats = SmsTransaction::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
                ->groupBy('type')
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => $item->type,
                        'count' => $item->count,
                        'total_amount' => $item->total_amount,
                    ];
                })
                ->keyBy('type')
                ->toArray();
            
            // Get total deposits and spending for the period
            $totals = [
                'deposits' => $dailyStats->sum('deposits'),
                'spending' => $dailyStats->sum('spending'),
                'balance' => $user->sms_wallet ? $user->sms_wallet->balance : 0,
            ];
            
            return response()->json([
                'daily_stats' => $dailyStats,
                'transaction_stats' => $transactionStats,
                'totals' => $totals,
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch financial analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get campaign analytics
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function campaign(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Get campaign
            $campaign = $user->campaigns()->findOrFail($id);
            
            // Get message stats for campaign
            $messageStats = Message::where('user_id', $user->id)
                ->where('campaign_id', $campaign->id)
                ->select(
                    'status',
                    DB::raw('count(*) as count'),
                    DB::raw('sum(successful_sends) as successful_count'),
                    DB::raw('sum(failed_sends) as failed_count'),
                    DB::raw('sum(cost) as total_cost')
                )
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'status' => $item->status,
                        'count' => $item->count,
                        'successful_count' => $item->successful_count,
                        'failed_count' => $item->failed_count,
                        'total_cost' => $item->total_cost,
                    ];
                })
                ->keyBy('status')
                ->toArray();
            
            // Get daily message stats for campaign
            $dailyStats = Message::where('user_id', $user->id)
                ->where('campaign_id', $campaign->id)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as messages_count'),
                    DB::raw('sum(successful_sends) as successful_count'),
                    DB::raw('sum(failed_sends) as failed_count'),
                    DB::raw('sum(cost) as total_cost')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            // Get totals
            $totals = [
                'messages_count' => Message::where('campaign_id', $campaign->id)->count(),
                'recipients_count' => Message::where('campaign_id', $campaign->id)->sum('total_recipients'),
                'successful_count' => Message::where('campaign_id', $campaign->id)->sum('successful_sends'),
                'failed_count' => Message::where('campaign_id', $campaign->id)->sum('failed_sends'),
                'total_cost' => Message::where('campaign_id', $campaign->id)->sum('cost'),
            ];
            
            return response()->json([
                'campaign' => $campaign,
                'message_stats' => $messageStats,
                'daily_stats' => $dailyStats,
                'totals' => $totals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch campaign analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Export message analytics as CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function exportMessages(Request $request)
    {
        try {
            $user = $request->user();
            $startDate = $request->get('start_date') ? new \DateTime($request->get('start_date')) : now()->subDays(30);
            $endDate = $request->get('end_date') ? new \DateTime($request->get('end_date')) : now();
            
            // Get messages data
            $messages = Message::where('user_id', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with(['sender'])
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                        'sender_id' => $message->sender->sender_id,
                        'content' => $message->content,
                        'status' => $message->status,
                        'total_recipients' => $message->total_recipients,
                        'successful_sends' => $message->successful_sends,
                        'failed_sends' => $message->failed_sends,
                        'cost' => $message->cost,
                    ];
                })
                ->toArray();
            
            // Create CSV file
            $filename = 'message_analytics_' . now()->format('Y-m-d') . '.csv';
            $path = storage_path('app/exports/' . $filename);
            
            // Ensure directory exists
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }
            
            // Write CSV
            $file = fopen($path, 'w');
            
            // Add headers
            fputcsv($file, array_keys($messages[0] ?? []));
            
            // Add data
            foreach ($messages as $message) {
                fputcsv($file, $message);
            }
            
            fclose($file);
            
            // Return file for download
            return response()->download($path, $filename, [
                'Content-Type' => 'text/csv',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export message analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}