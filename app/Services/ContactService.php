<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactService
{
    /**
     * Create a new contact
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function createContact(User $user, array $data)
    {
        try {
            // Validate phone number format
            if (!$this->validatePhoneNumber($data['phone_number'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format',
                ];
            }

            // Check if contact already exists for this user
            $existingContact = Contact::where('user_id', $user->id)
                ->where('phone_number', $data['phone_number'])
                ->first();

            if ($existingContact) {
                return [
                    'success' => false,
                    'error' => 'Contact with this phone number already exists',
                    'contact_id' => $existingContact->id,
                ];
            }

            // Create new contact
            $contact = Contact::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone_number' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'custom_fields' => is_string($data['custom_fields'] ?? null)
                    ? json_decode($data['custom_fields'], true)
                    : ($data['custom_fields'] ?? null),
                'status' => 'active',
            ]);

            // Add to groups if specified
            if (isset($data['groups']) && is_array($data['groups'])) {
                $groups = ContactGroup::where('user_id', $user->id)
                    ->whereIn('id', $data['groups'])
                    ->get();

                $contact->groups()->attach($groups);
            }

            return [
                'success' => true,
                'contact_id' => $contact->id,
            ];
        } catch (\Exception $e) {
            Log::error('Contact Service Exception (Create Contact)', [
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
     * Validate phone number format
     *
     * @param string $phone
     * @return bool
     */
    protected function validatePhoneNumber(string $phone)
    {
        // Basic validation: Phone should start with + and contain only digits
        return preg_match('/^\+[0-9]{10,15}$/', $phone);
    }

    /**
     * Create a new contact group
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    public function createContactGroup(User $user, array $data)
    {
        try {
            $group = ContactGroup::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Add contacts to group if specified
            if (isset($data['contacts']) && is_array($data['contacts'])) {
                $contacts = Contact::where('user_id', $user->id)
                    ->whereIn('id', $data['contacts'])
                    ->get();

                $group->contacts()->attach($contacts);
            }

            return [
                'success' => true,
                'group_id' => $group->id,
            ];
        } catch (\Exception $e) {
            Log::error('Contact Service Exception (Create Contact Group)', [
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
     * Import contacts from CSV
     *
     * @param User $user
     * @param string $filePath
     * @param array $mapping
     * @param int|null $groupId
     * @return array
     */
    public function importContactsFromCsv(User $user, string $filePath, array $mapping, ?int $groupId = null)
    {
        try {
            // Open file
            $file = fopen($filePath, 'r');
            if (!$file) {
                return [
                    'success' => false,
                    'error' => 'Failed to open file',
                ];
            }

            // Get header row
            $headers = fgetcsv($file);
            if (!$headers) {
                fclose($file);
                return [
                    'success' => false,
                    'error' => 'Empty or invalid CSV file',
                ];
            }

            // Validate mapping
            $requiredFields = ['first_name', 'phone'];
            foreach ($requiredFields as $field) {
                if (!isset($mapping[$field]) || !in_array($mapping[$field], $headers)) {
                    fclose($file);
                    return [
                        'success' => false,
                        'error' => "Required field '$field' is not mapped to a CSV column",
                    ];
                }
            }

            // Get the group if specified
            $group = null;
            if ($groupId) {
                $group = ContactGroup::where('id', $groupId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$group) {
                    fclose($file);
                    return [
                        'success' => false,
                        'error' => 'Invalid group ID',
                    ];
                }
            }

            // Start database transaction
            $importedCount = 0;
            $failedCount = 0;
            $existingCount = 0;

            DB::beginTransaction();

            try {
                // Process rows
                while (($row = fgetcsv($file)) !== false) {
                    $data = [];

                    // Map CSV data to fields
                    foreach ($mapping as $field => $column) {
                        $columnIndex = array_search($column, $headers);
                        if ($columnIndex !== false && isset($row[$columnIndex])) {
                            $data[$field] = $row[$columnIndex];
                        }
                    }

                    // Skip if required fields are missing
                    if (empty($data['first_name']) || empty($data['phone_number'])) {
                        $failedCount++;
                        continue;
                    }

                    // Normalize phone number
                    $data['phone_number'] = $this->normalizePhoneNumber($data['phone_number']);

                    // Skip if phone number is invalid
                    if (!$this->validatePhoneNumber($data['phone_number'])) {
                        $failedCount++;
                        continue;
                    }

                    // Check if contact already exists
                    $existingContact = Contact::where('user_id', $user->id)
                        ->where('phone_number', $data['phone_number'])
                        ->first();

                    if ($existingContact) {
                        // Update existing contact's groups if needed
                        if ($group) {
                            $existingContact->groups()->syncWithoutDetaching([$group->id]);
                        }

                        $existingCount++;
                        continue;
                    }

                    // Create new contact
                    $contact = Contact::create([
                        'user_id' => $user->id,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'] ?? null,
                        'phone_number' => $data['phone_number'],
                        'email' => $data['email'] ?? null,
                        'custom_fields' => isset($data['custom_fields']) ? json_decode($data['custom_fields'], true) : null,
                        'status' => 'active',
                    ]);

                    // Add to group if specified
                    if ($group) {
                        $contact->groups()->attach($group);
                    }

                    $importedCount++;
                }

                DB::commit();

                fclose($file);

                return [
                    'success' => true,
                    'imported' => $importedCount,
                    'failed' => $failedCount,
                    'existing' => $existingCount,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                fclose($file);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Contact Service Exception (Import Contacts)', [
                'user_id' => $user->id,
                'file' => $filePath,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize phone number format
     *
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone)
    {
        // Remove all non-digit characters except the + sign
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Add + if not present
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Add contacts to a group
     *
     * @param int $groupId
     * @param array $contactIds
     * @return array
     */
    public function addContactsToGroup(int $groupId, array $contactIds)
    {
        try {
            $group = ContactGroup::findOrFail($groupId);

            // Ensure contacts belong to the same user as the group
            $contacts = Contact::where('user_id', $group->user_id)
                ->whereIn('id', $contactIds)
                ->get();

            if ($contacts->count() === 0) {
                return [
                    'success' => false,
                    'error' => 'No valid contacts found',
                ];
            }

            // Add contacts to group
            $group->contacts()->syncWithoutDetaching($contacts->pluck('id')->toArray());

            return [
                'success' => true,
                'added' => $contacts->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Contact Service Exception (Add Contacts To Group)', [
                'group_id' => $groupId,
                'contact_ids' => $contactIds,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove contacts from a group
     *
     * @param int $groupId
     * @param array $contactIds
     * @return array
     */
    public function removeContactsFromGroup(int $groupId, array $contactIds)
    {
        try {
            $group = ContactGroup::findOrFail($groupId);

            // Remove contacts from group
            $group->contacts()->detach($contactIds);

            return [
                'success' => true,
                'removed' => count($contactIds),
            ];
        } catch (\Exception $e) {
            Log::error('Contact Service Exception (Remove Contacts From Group)', [
                'group_id' => $groupId,
                'contact_ids' => $contactIds,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}