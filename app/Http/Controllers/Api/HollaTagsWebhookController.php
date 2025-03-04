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
        // HollaTags uses uppercase status values in their callbacks
        $statusMap = [
            'DELIVRD' => 'delivered',
            'ACCEPTD' => 'sent',
            'EXPIRED' => 'failed',
            'UNDELIV' => 'failed',
            'REJECTD' => 'failed',
            'DELETED' => 'failed',
            'UNKNOWN' => 'unknown'
        ];
        
        $deliveryStatus = $statusMap[strtoupper($status)] ?? 'unknown';
        
        // Update message in the database directly
        $message->update([
            'status' => $deliveryStatus,
            'delivery_status' => strtoupper($status),
            'delivery_status_time' => now()
        ]);
        
        // Update success/failure counts based on status
        if ($deliveryStatus === 'delivered') {
            $message->increment('successful_sends');
        } elseif ($deliveryStatus === 'failed') {
            $message->increment('failed_sends');
        }
        
        // If cost is provided, update the message cost
        if ($cost !== null) {
            $message->update(['cost' => $cost]);
        }
        
        Log::info('Message status updated via webhook', [
            'message_id' => $message->id,
            'external_id' => $messageId,
            'status' => $deliveryStatus,
            'original_status' => $status
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