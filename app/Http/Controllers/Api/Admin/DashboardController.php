<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\MarketPlaceLink;
use App\Models\Message;
use App\Models\Product;
use App\Models\SenderId;
use App\Models\SmsWallet;
use App\Models\User;
use App\Services\DashboardService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getLinksCount(Request $request)
    {
        try {
            // Existing link statistics
            $totalUsers = User::count();
            $totalClicks = Link::sum('total_click');
            $totalWhatsappLink = Link::whereIn('type', ['message', 'catalog'])->count();
            $totalRedirectLink = Link::where('type', 'url')->count();
            $totalMultiLink = Link::where('type', 'tiered')->count();
            $totalMarketLink = MarketPlaceLink::count();
            $totalProducts = Product::count();

            // NEW SMS Statistics
            $totalSmsSent = Message::whereNotIn('status', ['draft', 'cancelled'])->sum('total_recipients');
            $totalSmsRevenue = Message::whereNotNull('cost')->sum('cost');
            $activeSenderIds = SenderId::where('status', 'approved')->count();
            $pendingSenderIds = SenderId::where('status', 'pending')->count();
            $totalSmsUsers = SmsWallet::count();
            $totalWalletBalance = SmsWallet::sum('balance');

            // Message delivery rates
            $deliveredMessages = Message::where('status', 'delivered')->sum('successful_sends');
            $failedMessages = Message::where('status', 'failed')->sum('failed_sends');
            $totalSentMessages = $deliveredMessages + $failedMessages;
            $deliveryRate = $totalSentMessages > 0 ? round(($deliveredMessages / $totalSentMessages) * 100, 2) : 0;

            // Recent SMS activity (last 7 days)
            $recentSmsActivity = Message::where('created_at', '>=', now()->subDays(7))
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->sum('total_recipients');

            return response()->json([
                'status' => 'success',
                'data' => [
                    // Existing stats
                    'total_users' => $totalUsers,
                    'total_Clicks' => $totalClicks,
                    'total_whatsapp_link' => $totalWhatsappLink,
                    'total_redirect_link' => $totalRedirectLink,
                    'total_multi_link' => $totalMultiLink,
                    'total_market_link' => $totalMarketLink,
                    'total_products' => $totalProducts,

                    // NEW SMS stats
                    'total_sms_sent' => $totalSmsSent,
                    'total_sms_revenue' => round($totalSmsRevenue, 2),
                    'active_sender_ids' => $activeSenderIds,
                    'pending_sender_ids' => $pendingSenderIds,
                    'total_sms_users' => $totalSmsUsers,
                    'total_wallet_balance' => round($totalWalletBalance, 2),
                    'delivery_rate' => $deliveryRate,
                    'recent_sms_activity' => $recentSmsActivity,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single user data (existing method - keeping it)
     */
    public function getSingleUser($id)
    {
        try {
            $user = User::with([
                'sms_wallet',
                'senderIds',
                'messages' => function ($query) {
                    $query->latest()->limit(10);
                }
            ])
                ->withCount(['messages', 'senderIds'])
                ->findOrFail($id);

            // Get user's SMS statistics
            $userSmsStats = [
                'total_messages_sent' => $user->messages()->whereNotIn('status', ['draft', 'cancelled'])->count(),
                'total_sms_sent' => $user->messages()->whereNotIn('status', ['draft', 'cancelled'])->sum('total_recipients'),
                'total_spent' => $user->messages()->whereNotNull('cost')->sum('cost'),
                'wallet_balance' => $user->sms_wallet ? $user->sms_wallet->balance : 0,
                'wallet_status' => $user->sms_wallet ? $user->sms_wallet->status : 'none',
                'sender_ids_count' => $user->sender_ids_count,
                'approved_sender_ids' => $user->senderIds()->where('status', 'approved')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user_data' => $user,
                    'sms_stats' => $userSmsStats,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
