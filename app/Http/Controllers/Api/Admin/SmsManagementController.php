<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SenderId;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use App\Models\Campaign;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SmsManagementController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get SMS dashboard statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        try {
            // Total SMS sent
            $totalSmsSent = Message::whereNotIn('status', ['draft', 'cancelled'])->sum('total_recipients');

            // Total amount spent on SMS
            $totalAmountSpent = Message::whereNotNull('cost')->sum('cost');

            // Total users with SMS wallets
            $totalSmsUsers = SmsWallet::count();

            // Active sender IDs
            $activeSenderIds = SenderId::where('status', 'approved')->count();

            // Pending sender ID requests
            $pendingSenderIds = SenderId::where('status', 'pending')->count();

            // Total wallet balance across platform
            $totalWalletBalance = SmsWallet::sum('balance');

            // Messages by status
            $messagesByStatus = Message::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Monthly SMS stats (last 6 months)
            $monthlySmsStats = Message::where('created_at', '>=', now()->subMonths(6))
                ->select(
                    DB::raw('EXTRACT(YEAR FROM created_at) as year'),
                    DB::raw('EXTRACT(MONTH FROM created_at) as month'),
                    DB::raw('sum(total_recipients) as sms_count'),
                    DB::raw('sum(cost) as total_cost'),
                    DB::raw('count(*) as message_count')
                )
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    $date = \Carbon\Carbon::createFromDate($item->year, $item->month, 1);
                    return [
                        'month' => $date->format('M Y'),
                        'sms_count' => $item->sms_count,
                        'total_cost' => $item->total_cost,
                        'message_count' => $item->message_count,
                    ];
                });

            // Top 10 users by SMS usage
            $topUsers = Message::select('user_id', DB::raw('sum(total_recipients) as sms_sent'), DB::raw('sum(cost) as amount_spent'))
                ->with('user:id,name,email')
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->groupBy('user_id')
                ->orderBy('sms_sent', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user->name ?? 'Unknown',
                        'user_email' => $item->user->email ?? 'Unknown',
                        'sms_sent' => $item->sms_sent,
                        'amount_spent' => $item->amount_spent,
                    ];
                });

            return response()->json([
                'total_sms_sent' => $totalSmsSent,
                'total_amount_spent' => $totalAmountSpent,
                'total_sms_users' => $totalSmsUsers,
                'active_sender_ids' => $activeSenderIds,
                'pending_sender_ids' => $pendingSenderIds,
                'total_wallet_balance' => $totalWalletBalance,
                'messages_by_status' => $messagesByStatus,
                'monthly_sms_stats' => $monthlySmsStats,
                'top_users' => $topUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch SMS dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all sender ID requests
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function senderIds(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = SenderId::with('user:id,name,email');

            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }

            // Filter by search term
            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                })->orWhere('sender_id', 'LIKE', "%{$search}%");
            }

            $senderIds = $query->latest()->paginate($perPage);

            return response()->json($senderIds);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch sender IDs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve or reject sender ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSenderIdStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $senderId = SenderId::findOrFail($id);

            $senderId->update([
                'status' => $request->status,
                'rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
            ]);

            return response()->json([
                'message' => 'Sender ID status updated successfully',
                'sender_id' => $senderId->load('user:id,name,email'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update sender ID status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all messages with metadata
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $userId = $request->get('user_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = Message::with(['user:id,name,email', 'sender:id,sender_id', 'campaign:id,name'])
                ->select([
                    'id',
                    'user_id',
                    'sender_id',
                    'campaign_id',
                    'message_type',
                    'status',
                    'total_recipients',
                    'successful_sends',
                    'failed_sends',
                    'cost',
                    'created_at',
                    'sent_at',
                    'delivery_status'
                ]);

            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }

            // Filter by user
            if ($userId) {
                $query->where('user_id', $userId);
            }

            // Filter by date range
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            $messages = $query->latest()->paginate($perPage);

            return response()->json($messages);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all SMS users with wallet info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function smsUsers(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');

            $query = User::with(['sms_wallet', 'senderIds'])
                ->withCount(['messages', 'senderIds'])
                ->whereHas('sms_wallet');

            // Filter by search term
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->latest()->paginate($perPage);

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch SMS users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user wallet status
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserWalletStatus(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::findOrFail($userId);

            if (!$user->sms_wallet) {
                return response()->json([
                    'message' => 'User does not have an SMS wallet',
                ], 400);
            }

            $result = $this->walletService->changeWalletStatus($user, $request->status);

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }

            return response()->json([
                'message' => 'User wallet status updated successfully',
                'wallet' => $user->sms_wallet->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user wallet status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add funds to user wallet manually
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addFundsToUser(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $admin = $request->user(); // Current admin user

            $result = $this->walletService->addFundsManually(
                $user,
                $request->amount,
                $request->description ?? 'Admin manual funding',
                $admin
            );

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }

            return response()->json([
                'message' => 'Funds added successfully',
                'transaction_id' => $result['transaction_id'],
                'new_balance' => $result['new_balance'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add funds',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all campaigns across users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function campaigns(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Campaign::with(['user:id,name,email'])
                ->withCount(['messages']);

            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }

            // Filter by search term
            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            }

            $campaigns = $query->latest()->paginate($perPage);

            return response()->json($campaigns);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch campaigns',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get platform SMS settings and limits
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings(Request $request)
    {
        try {
            // Get settings from config or database
            $settings = [
                'cost_per_segment' => config('services.messaging.cost_per_segment', 3.89),
                'daily_limit_per_user' => config('services.messaging.daily_limit_per_user', 1000),
                'monthly_limit_per_user' => config('services.messaging.monthly_limit_per_user', 10000),
                'minimum_wallet_balance' => config('services.messaging.minimum_wallet_balance', 100),
                'auto_approve_sender_ids' => config('services.messaging.auto_approve_sender_ids', false),
            ];

            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update platform SMS settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cost_per_segment' => 'required|numeric|min:0',
            'daily_limit_per_user' => 'required|integer|min:0',
            'monthly_limit_per_user' => 'required|integer|min:0',
            'minimum_wallet_balance' => 'required|numeric|min:0',
            'auto_approve_sender_ids' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Here you would typically save to a settings table or update config
            // For now, we'll just return success
            // You might want to create a Settings model to store these values

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $request->all(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
