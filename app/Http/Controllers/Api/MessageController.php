<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Campaign;
use App\Models\SenderId;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    protected $messageService;
    
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    
    /**
     * Get all messages for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $search = $request->get('search');
            $campaignId = $request->get('campaign_id');
            
            $query = Message::where('user_id', $user->id);
            
            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }
            
            // Filter by campaign
            if ($campaignId) {
                $query->where('campaign_id', $campaignId);
            }
            
            // Filter by search term (search in content)
            if ($search) {
                $query->where('content', 'LIKE', "%{$search}%");
            }
            
            $messages = $query->with(['sender', 'campaign'])
                ->latest()
                ->paginate($perPage);
            
            return response()->json($messages);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create a new message
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|integer|exists:sender_ids,id',
            'content' => 'required|string',
            'message_type' => 'nullable|string|in:sms,mms',
            'scheduled_at' => 'nullable|date|after:now',
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'message_template_id' => 'nullable|integer|exists:message_templates,id',
            'contacts' => 'required_without:groups|array',
            'contacts.*' => 'integer|exists:contacts,id',
            'groups' => 'required_without:contacts|array',
            'groups.*' => 'integer|exists:contact_groups,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            
            // Ensure sender ID belongs to user
            $senderId = SenderId::where('id', $request->sender_id)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$senderId) {
                return response()->json([
                    'message' => 'Invalid sender ID',
                ], 400);
            }
            
            // Create the message
            $result = $this->messageService->createMessage($user, $request->all());
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            $message = Message::with(['sender', 'campaign', 'contacts', 'contactGroups'])
                ->findOrFail($result['message_id']);
            
            return response()->json([
                'message' => 'Message created successfully',
                'data' => $message,
                'recipients' => $result['total_recipients'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific message
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $message = Message::where('user_id', $user->id)
                ->with(['sender', 'campaign', 'contacts', 'contactGroups'])
                ->findOrFail($id);
            
            return response()->json($message);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Send a message
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request, $id)
    {
        try {
            $user = $request->user();
           
            $message = Message::where('user_id', $user->id)
                // ->where('status', 'draft')
                ->where('id', $id)
                ->first();

                //  dd($message);
            
            $result = $this->messageService->sendMessage($message->id);
           
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                    'required' => $result['required'] ?? null,
                    'balance' => $result['balance'] ?? null,
                ], 400);
            }
            
            return response()->json([
                'message' => 'Message queued for sending',
                'recipients' => $result['recipients'],
                'cost' => $result['cost'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Schedule a message for later delivery
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date|after:now',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $message = Message::where('user_id', $user->id)
                ->where('status', 'draft')
                ->findOrFail($id);
            
            $scheduledAt = new \DateTime($request->scheduled_at);
            $result = $this->messageService->scheduleMessage($message->id, $scheduledAt);
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Message scheduled successfully',
                'scheduled_at' => $scheduledAt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Cancel a scheduled message
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = $request->user();
            $message = Message::where('user_id', $user->id)
                ->where('status', 'scheduled')
                ->findOrFail($id);
            
            // Update message status back to draft
            $message->update([
                'status' => 'draft',
                'scheduled_at' => null,
            ]);
            
            return response()->json([
                'message' => 'Scheduled message canceled',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel scheduled message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a message (draft only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $message = Message::where('user_id', $user->id)
                ->where('status', 'draft')
                ->findOrFail($id);
            
            $message->delete();
            
            return response()->json([
                'message' => 'Message deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get message templates
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function templates(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            
            $query = MessageTemplate::where('user_id', $user->id);
            
            // Filter by search term
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%");
                });
            }
            
            $templates = $query->latest()->paginate($perPage);
            
            return response()->json($templates);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch templates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create a message template
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'message_type' => 'nullable|string|in:sms,mms',
            'variables' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $result = $this->messageService->createTemplate($user, $request->all());
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            $template = MessageTemplate::findOrFail($result['template_id']);
            
            return response()->json([
                'message' => 'Template created successfully',
                'template' => $template,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific message template
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showTemplate(Request $request, $id)
    {
        try {
            $user = $request->user();
            $template = MessageTemplate::where('user_id', $user->id)
                ->findOrFail($id);
            
            return response()->json($template);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update a message template
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTemplate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'message_type' => 'nullable|string|in:sms,mms',
            'variables' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $template = MessageTemplate::where('user_id', $user->id)
                ->findOrFail($id);
            
            $template->update($request->only([
                'name', 'content', 'description', 'message_type', 'variables',
            ]));
            
            return response()->json([
                'message' => 'Template updated successfully',
                'template' => $template,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a message template
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyTemplate(Request $request, $id)
    {
        try {
            $user = $request->user();
            $template = MessageTemplate::where('user_id', $user->id)
                ->findOrFail($id);
            
            // Check if template is used by any messages
            $messagesCount = Message::where('message_template_id', $template->id)->count();
            
            if ($messagesCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete template as it is used by messages',
                    'messages_count' => $messagesCount,
                ], 400);
            }
            
            $template->delete();
            
            return response()->json([
                'message' => 'Template deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get campaigns
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function campaigns(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $search = $request->get('search');
            
            $query = Campaign::where('user_id', $user->id)
                ->withCount('messages');
            
            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }
            
            // Filter by search term
            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%");
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
     * Create a campaign
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCampaign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $result = $this->messageService->createCampaign($user, $request->all());
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            $campaign = Campaign::findOrFail($result['campaign_id']);
            
            return response()->json([
                'message' => 'Campaign created successfully',
                'campaign' => $campaign,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific campaign
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCampaign(Request $request, $id)
    {
        try {
            $user = $request->user();
            $campaign = Campaign::where('user_id', $user->id)
                ->withCount('messages')
                ->with(['messages' => function($query) {
                    $query->latest()->limit(10);
                }])
                ->findOrFail($id);
            
            return response()->json($campaign);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update a campaign
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCampaign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:draft,scheduled,in_progress,completed,canceled',
            'scheduled_at' => 'nullable|date|after:now',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $campaign = Campaign::where('user_id', $user->id)
                ->findOrFail($id);
            
            $campaign->update($request->only([
                'name', 'description', 'status', 'scheduled_at',
            ]));
            
            return response()->json([
                'message' => 'Campaign updated successfully',
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a campaign
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyCampaign(Request $request, $id)
    {
        try {
            $user = $request->user();
            $campaign = Campaign::where('user_id', $user->id)
                ->findOrFail($id);
            
            // Check if campaign has active messages
            $activeMessagesCount = Message::where('campaign_id', $campaign->id)
                ->whereNotIn('status', ['draft', 'failed'])
                ->count();
            
            if ($activeMessagesCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete campaign with active messages',
                    'active_messages_count' => $activeMessagesCount,
                ], 400);
            }
            
            $campaign->delete();
            
            return response()->json([
                'message' => 'Campaign deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}