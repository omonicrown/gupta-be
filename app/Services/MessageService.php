<?php

namespace App\Services;

use App\Jobs\SendBulkMessages;
use App\Jobs\SendSingleMessage;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\SenderId;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageService
{
    protected $hollaTagsService;
    protected $walletService;
    
    public function __construct(HollaTagsService $hollaTagsService, WalletService $walletService)
    {
        $this->hollaTagsService = $hollaTagsService;
        $this->walletService = $walletService;
    }
    
    /**
     * Calculate message cost
     *
     * @param string $content
     * @param int $recipientCount
     * @return float
     */
    public function calculateMessageCost(string $content, int $recipientCount)
    {
        // Calculate number of message segments based on content length
        $length = mb_strlen($content);
        $segmentLength = 160; // Standard SMS segment length
        $segments = ceil($length / $segmentLength);
        
        // Get cost per segment from configuration
        $costPerSegment = config('services.messaging.cost_per_segment', 4.0);
        
        // Calculate total cost
        return $segments * $recipientCount * $costPerSegment;
    }
    
    /**
     * Create a new message
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function createMessage(User $user, array $data)
    {
        try {
            // Check if sender ID exists and is approved
            $senderId = SenderId::where('id', $data['sender_id'])
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();
            
            if (!$senderId) {
                return [
                    'success' => false,
                    'error' => 'Invalid or unapproved Sender ID',
                ];
            }
            
            // Create new message
            $message = Message::create([
                'user_id' => $user->id,
                'sender_id' => $senderId->id,
                'content' => $data['content'],
                'message_type' => $data['message_type'] ?? 'sms',
                'status' => 'draft',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'message_template_id' => $data['message_template_id'] ?? null,
            ]);
            
            // Attach recipients
            if (isset($data['contacts']) && is_array($data['contacts'])) {
                $contacts = Contact::where('user_id', $user->id)
                    ->whereIn('id', $data['contacts'])
                    ->get();
                
                $message->contacts()->attach($contacts, ['status' => 'pending']);
            }
            
            if (isset($data['groups']) && is_array($data['groups'])) {
                $groups = ContactGroup::where('user_id', $user->id)
                    ->whereIn('id', $data['groups'])
                    ->get();
                
                $message->contactGroups()->attach($groups, ['status' => 'pending']);
            }
            
            // Count total recipients
            $contactsCount = $message->contacts()->count();
            $groupContactsCount = DB::table('contact_group_contact')
                ->whereIn('contact_group_id', $message->contactGroups()->pluck('id')->toArray())
                ->distinct('contact_id')
                ->count('contact_id');
            
            $totalRecipients = $contactsCount + $groupContactsCount;
            
            // Update message with recipient count
            $message->update([
                'total_recipients' => $totalRecipients,
            ]);
            
            return [
                'success' => true,
                'message_id' => $message->id,
                'total_recipients' => $totalRecipients,
            ];
        } catch (\Exception $e) {
            Log::error('Message Service Exception (Create Message)', [
                'user_id' => $user->id,
                'data' => $data,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Send a message immediately
     *
     * @param int $messageId
     * @return array
     */
    public function sendMessage(int $messageId)
    {
        try {
            $message = Message::with(['contacts', 'contactGroups.contacts'])
                ->findOrFail($messageId);
                
            
            // Check if message is already sent
            if (in_array($message->status, ['sent', 'delivered'])) {
                return [
                    'success' => false,
                    'error' => 'Message already sent',
                ];
            }
            
            // Get all recipients
            $contactPhones = $message->contacts->pluck('phone_number')->toArray();
            
            // Get all contacts from all groups
            $groupContacts = collect();
            foreach ($message->contactGroups as $group) {
                $groupContacts = $groupContacts->merge($group->contacts);
            }
            
            $groupPhones = $groupContacts->pluck('phone_number')->toArray();
            
            // Combine and remove duplicates
            $allPhones = array_unique(array_merge($contactPhones, $groupPhones));
            
            // Calculate cost
            $cost = $this->calculateMessageCost($message->content, count($allPhones));
            
            // Check wallet balance
            $user = User::find($message->user_id);
            $balance = $this->walletService->getBalance($user);
            
            if ($balance < $cost) {
                return [
                    'success' => false,
                    'error' => 'Insufficient wallet balance',
                    'required' => $cost,
                    'balance' => $balance,
                ];
            }
            
            // Deduct amount from wallet
            $walletDeduction = $this->walletService->deductForMessage($user, $cost, 'Message Sending: ' . $messageId);
            
            if (!$walletDeduction['success']) {
                return [
                    'success' => false,
                    'error' => $walletDeduction['error'],
                ];
            }
            
            // Update message with cost
            $message->update([
                'cost' => $cost,
                'status' => 'queued',
            ]);
            
            // Queue message for sending
            if (count($allPhones) > 100) {
                // Use bulk sending for large batches
                SendBulkMessages::dispatch($message, $allPhones);
            } else {
                // Send individual messages for smaller batches
                foreach ($allPhones as $phone) {
                    SendSingleMessage::dispatch($message, $phone);
                }
            }
            
            return [
                'success' => true,
                'message_id' => $message->id,
                'recipients' => count($allPhones),
                'cost' => $cost,
            ];
        } catch (Exception $e) {
            Log::error('Message Service Exception (Send Message)', [
                'message_id' => $messageId,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update message delivery status
     *
     * @param string $externalMessageId
     * @param string $status
     * @param array $metadata
     * @return array
     */
    public function updateDeliveryStatus(string $externalMessageId, string $status, array $metadata = [])
    {
        try {
            $message = Message::where('external_message_id', $externalMessageId)->first();
            
            if (!$message) {
                return [
                    'success' => false,
                    'error' => 'Message not found',
                ];
            }
            
            // Map HollaTags status to our status
            $statusMap = [
                'delivered' => 'delivered',
                'read' => 'delivered',
                'failed' => 'failed',
                'undelivered' => 'failed',
                'accepted' => 'sent',
                'sent' => 'sent',
                'queued' => 'queued',
            ];
            
            $messageStatus = $statusMap[$status] ?? 'sent';
            
            // Update message status
            $message->update([
                'status' => $messageStatus,
                'delivery_status' => $status,
                'delivery_status_time' => now(),
            ]);
            
            // Update success/failure counts
            if ($messageStatus === 'delivered') {
                $message->increment('successful_sends');
            } elseif ($messageStatus === 'failed') {
                $message->increment('failed_sends');
            }
            
            return [
                'success' => true,
                'message_id' => $message->id,
                'status' => $messageStatus,
            ];
        } catch (\Exception $e) {
            Log::error('Message Service Exception (Update Delivery Status)', [
                'external_message_id' => $externalMessageId,
                'status' => $status,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Create a message template
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function createTemplate(User $user, array $data)
    {
        try {
            $template = MessageTemplate::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'content' => $data['content'],
                'description' => $data['description'] ?? null,
                'message_type' => $data['message_type'] ?? 'sms',
                'variables' => $data['variables'] ?? null,
            ]);
            
            return [
                'success' => true,
                'template_id' => $template->id,
            ];
        } catch (\Exception $e) {
            Log::error('Message Service Exception (Create Template)', [
                'user_id' => $user->id,
                'data' => $data,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Create a campaign
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function createCampaign(User $user, array $data)
    {
        try {
            $campaign = Campaign::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]);
            
            return [
                'success' => true,
                'campaign_id' => $campaign->id,
            ];
        } catch (\Exception $e) {
            Log::error('Message Service Exception (Create Campaign)', [
                'user_id' => $user->id,
                'data' => $data,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Schedule message for later delivery
     *
     * @param int $messageId
     * @param \DateTime $scheduledAt
     * @return array
     */
    public function scheduleMessage(int $messageId, \DateTime $scheduledAt)
    {
        try {
            $message = Message::findOrFail($messageId);
            
            // Ensure message is in draft status
            if ($message->status !== 'draft') {
                return [
                    'success' => false,
                    'error' => 'Only draft messages can be scheduled',
                ];
            }
            
            // Update message with scheduled time
            $message->update([
                'scheduled_at' => $scheduledAt,
                'status' => 'scheduled',
            ]);
            
            return [
                'success' => true,
                'message_id' => $message->id,
                'scheduled_at' => $scheduledAt,
            ];
        } catch (\Exception $e) {
            Log::error('Message Service Exception (Schedule Message)', [
                'message_id' => $messageId,
                'scheduled_at' => $scheduledAt,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
 * Send a message directly (for API users)
 *
 * @param User $user
 * @param array $data
 * @return array
 */
public function sendDirectMessage(User $user, array $data)
{
    try {
        // Check if sender ID exists and is approved
        $senderId = SenderId::where('id', $data['sender_id'])
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();
        
        if (!$senderId) {
            return [
                'success' => false,
                'error' => 'Invalid or unapproved Sender ID',
            ];
        }
        
        // Validate recipients
        $recipients = $data['direct_recipients'] ?? [];
        if (empty($recipients)) {
            return [
                'success' => false,
                'error' => 'No recipients specified',
            ];
        }
        
        // Calculate cost
        $cost = $this->calculateMessageCost($data['content'], count($recipients));
        
        // Check wallet balance
        $balance = $this->walletService->getBalance($user);
        if ($balance < $cost) {
            return [
                'success' => false,
                'error' => 'Insufficient wallet balance',
                'required' => $cost,
                'balance' => $balance,
            ];
        }
        
        // Deduct amount from wallet
        $walletDeduction = $this->walletService->deductForMessage($user, $cost, 'API Message: ' . ($data['reference'] ?? 'No reference'));
        
        if (!$walletDeduction['success']) {
            return [
                'success' => false,
                'error' => $walletDeduction['error'],
            ];
        }
        
        // Create the message record
        $message = Message::create([
            'user_id' => $user->id,
            'sender_id' => $senderId->id,
            'content' => $data['content'],
            'message_type' => $data['message_type'] ?? 'sms',
            'status' => 'queued',
            'total_recipients' => count($recipients),
            'cost' => $cost,
            'reference' => $data['reference'] ?? null,
            'webhook_url' => $data['webhook_url'] ?? null,
        ]);
        
        // Send the message immediately
        if (count($recipients) == 1) {
            // Send single message
            $result = $this->hollaTagsService->sendSingleMessage($message, $recipients[0]);
        } else {
            // Send bulk message
            $result = $this->hollaTagsService->sendBulkMessage($message, $recipients);
        }
        
        if (!$result['success']) {
            // Log the error but don't refund - message is still queued for retry
            Log::error('API Message sending failed', [
                'message_id' => $message->id,
                'error' => $result['error'],
            ]);
            
            return [
                'success' => false,
                'error' => $result['error'],
                'message_id' => $message->id,
            ];
        }
        
        return [
            'success' => true,
            'message_id' => $message->id,
            'external_id' => $result['message_id'] ?? $result['batch_id'] ?? null,
            'recipients_count' => count($recipients),
        ];
    } catch (\Exception $e) {
        Log::error('Exception in direct message sending', [
            'user_id' => $user->id,
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
}