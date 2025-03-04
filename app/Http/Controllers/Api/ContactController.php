<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\ContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    protected $contactService;
    
    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }
    
    /**
     * Get all contacts for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $groupId = $request->get('group_id');
            
            $query = Contact::where('user_id', $user->id);
            
            // Filter by search term
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }
            
            // Filter by group
            if ($groupId) {
                $query->whereHas('groups', function ($q) use ($groupId) {
                    $q->where('contact_groups.id', $groupId);
                });
            }
            
            $contacts = $query->latest()->paginate($perPage);
            
            return response()->json($contacts);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch contacts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create a new contact
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'custom_fields' => 'nullable',
            'groups' => 'nullable|array',
            'groups.*' => 'integer|exists:contact_groups,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $result = $this->contactService->createContact($user, $request->all());
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            $contact = Contact::findOrFail($result['contact_id']);
            
            return response()->json([
                'message' => 'Contact created successfully',
                'contact' => $contact,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific contact
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $contact = Contact::where('user_id', $user->id)
                ->with('groups')
                ->findOrFail($id);
            
            return response()->json($contact);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update a contact
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email|max:255',
            'custom_fields' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $contact = Contact::where('user_id', $user->id)->findOrFail($id);
            
            $contact->update($request->only([
                'first_name', 'last_name', 'phone_number', 'email', 'custom_fields', 'status',
            ]));
            
            return response()->json([
                'message' => 'Contact updated successfully',
                'contact' => $contact,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a contact
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $contact = Contact::where('user_id', $user->id)->findOrFail($id);
            
            $contact->delete();
            
            return response()->json([
                'message' => 'Contact deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete contact',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Import contacts from CSV
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'mapping' => 'required|array',
            'mapping.first_name' => 'required|string',
            'mapping.phone' => 'required|string',
            'group_id' => 'nullable|integer|exists:contact_groups,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            
            // Ensure group belongs to user if provided
            if ($request->group_id) {
                $group = ContactGroup::where('user_id', $user->id)
                    ->where('id', $request->group_id)
                    ->firstOrFail();
            }
            
            // Store the uploaded file
            $filePath = $request->file('file')->storeAs(
                'imports', 
                'contacts_' . $user->id . '_' . time() . '.csv',
                'local'
            );
            
            // Get the full path
            $fullPath = storage_path('app/' . $filePath);
            
            // Import contacts
            $result = $this->contactService->importContactsFromCsv(
                $user,
                $fullPath,
                $request->mapping,
                $request->group_id
            );
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Contacts imported successfully',
                'imported' => $result['imported'],
                'failed' => $result['failed'],
                'existing' => $result['existing'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import contacts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get all contact groups for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function groups(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            
            $query = ContactGroup::where('user_id', $user->id)
                ->withCount('contacts');
            
            // Filter by search term
            if ($search) {
                $query->where('name', 'LIKE', "%{$search}%");
            }
            
            $groups = $query->latest()->paginate($perPage);
            
            return response()->json($groups);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch contact groups',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create a new contact group
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contacts' => 'nullable|array',
            'contacts.*' => 'integer|exists:contacts,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $result = $this->contactService->createContactGroup($user, $request->all());
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            $group = ContactGroup::with('contacts')
                ->withCount('contacts')
                ->findOrFail($result['group_id']);
            
            return response()->json([
                'message' => 'Contact group created successfully',
                'group' => $group,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create contact group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get a specific contact group
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showGroup(Request $request, $id)
    {
        try {
            $user = $request->user();
            $group = ContactGroup::where('user_id', $user->id)
                ->withCount('contacts')
                ->with('contacts')
                ->findOrFail($id);
            
            return response()->json($group);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch contact group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Update a contact group
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGroup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $group = ContactGroup::where('user_id', $user->id)->findOrFail($id);
            
            $group->update($request->only([
                'name', 'description',
            ]));
            
            return response()->json([
                'message' => 'Contact group updated successfully',
                'group' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update contact group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Delete a contact group
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyGroup(Request $request, $id)
    {
        try {
            $user = $request->user();
            $group = ContactGroup::where('user_id', $user->id)->findOrFail($id);
            
            $group->delete();
            
            return response()->json([
                'message' => 'Contact group deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete contact group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Add contacts to a group
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addContactsToGroup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*' => 'integer|exists:contacts,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $group = ContactGroup::where('user_id', $user->id)->findOrFail($id);
            
            $result = $this->contactService->addContactsToGroup($group->id, $request->contacts);
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Contacts added to group successfully',
                'added' => $result['added'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add contacts to group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Remove contacts from a group
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeContactsFromGroup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*' => 'integer|exists:contacts,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = $request->user();
            $group = ContactGroup::where('user_id', $user->id)->findOrFail($id);
            
            $result = $this->contactService->removeContactsFromGroup($group->id, $request->contacts);
            
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                ], 400);
            }
            
            return response()->json([
                'message' => 'Contacts removed from group successfully',
                'removed' => $result['removed'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove contacts from group',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}