<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HollaTagsWebhookController extends Controller
{
    protected $messageService;
    
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    
    /**
     * Handle delivery status webhook from HollaTags
     *
     * @param Request $request
     * @param string|null $reference Optional reference ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleDeliveryStatus(Request $request, $reference = null)
    {
        // Log the incoming webhook data
        Log::info('HollaTags Webhook Received', [
            'payload' => $request->all(),
            'reference' => $reference
        ]);
        
        try {
            // Extract data from request
            $messageId = $request->input('message_id');
            $status = $request->input('status');
            $statusCode = $request->input('status_code');
            $doneDate = $request->input('done_date');
            $operator = $request->input('operator');
            $cost = $request->input('cost');
            
            if (!$messageId || !$status) {
                Log::warning('HollaTags Webhook: Missing required fields', [
                    'payload' => $request->all()
                ]);
                
                // Return OK to prevent HollaTags from retrying
                return response()->json(['status' => 'ok']);
            }
            
            // Find the message by external ID
            $message = Message::where('external_message_id', $messageId)->first();
            
            if (!$message) {
                Log::warning('HollaTags Webhook: Message not found', [
                    'external_message_id' => $messageId
                ]);
                
                return response()->json(['status' => 'ok']);
            }
            
            // Map HollaTags status to our system status
            $deliveryStatus = strtolower($status);
            
            // Update message status using the message service
            $this->messageService->updateDeliveryStatus($messageId, $deliveryStatus, [
                'status_code' => $statusCode,
                'done_date' => $doneDate,
                'operator' => $operator,
                'cost' => $cost
            ]);
            
            return response()->json(['status' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('HollaTags Webhook Error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Still return OK to prevent HollaTags from retrying
            return response()->json(['status' => 'ok']);
        }
    }
}