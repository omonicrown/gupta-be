<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SenderId;
use App\Services\HollaTagsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SenderIdController extends Controller
{
    protected $hollaTagsService;
    
    public function __construct(HollaTagsService $hollaTagsService)
    {
        $this->hollaTagsService = $hollaTagsService;
    }
    
    /**
     * Get all sender IDs for authenticated user
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
            
            $query = SenderId::where('user_id', $user->id);
            
            // Filter by status
            if ($status) {
                $query->where('status', $status);
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
     * Create a new sender ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|string|max:11|min:3',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            
            // Check if sender ID already exists for this user
            $existingSenderId = SenderId::where('user_id', $user->id)
                ->where('sender_id', $request->sender_id)
                ->first();
            
            if ($existingSenderId) {
                return response()->json([
                    'message' => 'You already have this Sender ID',
                    'sender_id' => $existingSenderId,
                ], 400);
            }
            
            // Create the sender ID locally first
            $senderId = SenderId::create([
                'user_id' => $user->id,
                'sender_id' => $request->sender_id,
                'status' => 'pending',
                'external_id' => 'SND-' . uniqid(),
            ]);
            
            // Register sender ID with HollaTags
            // $result = $this->hollaTagsService->registerSenderId($senderId);
            
          
            
            return response()->json([
                'message' => 'Sender ID created successfully and pending approval',
                'sender_id' => $senderId,
                'request_id' => $result['request_id'] ?? null,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create sender ID',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific sender ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $senderId = SenderId::where('user_id', $user->id)
                ->findOrFail($id);
            
            return response()->json($senderId);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch sender ID',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Upload verification document for a sender ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocument(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $senderId = SenderId::where('user_id', $user->id)
                ->findOrFail($id);
            
            // Check if sender ID is in a valid state for document upload
            if ($senderId->status !== 'pending') {
                return response()->json([
                    'message' => 'Document can only be uploaded for pending sender IDs',
                ], 400);
            }
            
            // Store the file
            $documentPath = $request->file('document')->store(
                'sender_id_documents/' . $user->id,
                'local'
            );
            
            // Get the full path
            $fullPath = storage_path('app/' . $documentPath);
            
            // Upload document to HollaTags
            $result = $this->hollaTagsService->uploadVerificationDocument($senderId, $fullPath);
            
            if (!$result['success']) {
                // Delete the stored file if API call fails
                Storage::delete($documentPath);
                
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            // Update sender ID with document information
            $senderId->update([
                'verification_document' => basename($documentPath),
            ]);
            
            return response()->json([
                'message' => 'Document uploaded successfully',
                'sender_id' => $senderId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Check sender ID status with HollaTags
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request, $id)
    {
        try {
            $user = $request->user();
            $senderId = SenderId::where('user_id', $user->id)
                ->findOrFail($id);
            
            // Check sender ID status with HollaTags
            $result = $this->hollaTagsService->checkSenderIdStatus($senderId);
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Sender ID status checked successfully',
                'sender_id' => $senderId->fresh(), // Get the updated sender ID
                'status' => $result['status'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to check sender ID status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a sender ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $senderId = SenderId::where('user_id', $user->id)
                ->findOrFail($id);
            
            // Check if sender ID is used by any messages
            $messagesCount = $senderId->messages()->count();
            
            if ($messagesCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete sender ID that is used by messages',
                    'messages_count' => $messagesCount,
                ], 400);
            }
            
            // Delete document if exists
            if ($senderId->verification_document) {
                Storage::delete('sender_id_documents/' . $user->id . '/' . $senderId->verification_document);
            }
            
            $senderId->delete();
            
            return response()->json([
                'message' => 'Sender ID deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete sender ID',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}