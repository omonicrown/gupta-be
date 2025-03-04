<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiMessagingController extends Controller
{
    protected $messageService;
    
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    
    /**
     * Send a message immediately via API
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string',
            'message' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|string',
            'webhook_url' => 'nullable|url',
            'reference' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $user = $request->user(); // Authenticated API user
            
            // Get or verify sender ID
            $senderId = $this->resolveSenderId($user, $request->sender_id);
            if (!$senderId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or unauthorized sender ID'
                ], 400);
            }
            
            // Prepare data for message service
            $messageData = [
                'sender_id' => $senderId->id,
                'content' => $request->message,
                'message_type' => 'sms',
                'direct_recipients' => $request->recipients, // Special field for direct API sending
                'reference' => $request->reference ?? null,
                'webhook_url' => $request->webhook_url ?? null,
            ];


            
            
            // Send message immediately
            $result = $this->messageService->sendDirectMessage($user, $messageData);
            
            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Failed to send message',
                ], 400);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $result['message_id'],
                    'external_id' => $result['external_id'] ?? null,
                    'recipients_count' => $result['recipients_count'] ?? 0,
                    'reference' => $request->reference ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Resolve sender ID from string input
     * 
     * @param \App\Models\User $user
     * @param string $senderIdInput
     * @return \App\Models\SenderId|null
     */
    protected function resolveSenderId($user, $senderIdInput)
    {
        // Check if input is an ID
        if (is_numeric($senderIdInput)) {
            return $user->senderIds()->where('external_id', $senderIdInput)->first();
        }
        
        // Check if input is a sender ID string
        return $user->senderIds()
            ->where('external_id', $senderIdInput)
            ->where('status', 'approved')
            ->first();
    }
    
    /**
     * Check message status
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_id' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $user = $request->user();
            $messageId = $request->message_id;
            
            // Find message in our database
            $message = $user->messages()->where(function($query) use ($messageId) {
                $query->where('id', $messageId)
                      ->orWhere('external_message_id', $messageId);
            })->first();
            
            if (!$message) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'message_id' => $message->id,
                    'external_id' => $message->external_message_id,
                    'status' => $message->status,
                    'delivery_status' => $message->delivery_status,
                    'sent_at' => $message->sent_at ? $message->sent_at->toIso8601String() : null,
                    'delivered_at' => $message->delivery_status_time ? $message->delivery_status_time->toIso8601String() : null,
                    'recipients_count' => $message->total_recipients,
                    // 'successful_count' => $message->total_recipients,
                    'failed_count' => $message->failed_sends,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}