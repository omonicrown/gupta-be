<?php

namespace App\Services;

use App\Models\Message;
use App\Models\SenderId;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HollaTagsService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $defaultSenderId;
    protected $cachePrefix = 'hollatags_';
    protected $cacheTime = 3600; // 1 hour

    public function __construct()
    {
        $this->baseUrl = 'https://sms.hollatags.com/api';
        $this->username = 'gupta'; // Move to config
        $this->password = '0m0nicr0'; // Move to config
    }

    /**
     * Get HTTP client instance with default headers
     *
     * @param int $timeout Timeout in seconds
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function httpClient(int $timeout = 30)
    {
        return Http::timeout($timeout)
            ->asForm() // Use form encoding for this API
            ->baseUrl($this->baseUrl);
    }

    /**
     * Send a single SMS message
     *
     * @param Message $message
     * @param string $to
     * @param string|null $callbackUrl
     * @return array
     */
    public function sendSingleMessage(Message $message, string $to, string $callbackUrl = null)
    {
        try {
            // Normalize phone number to required format
            $to = $this->normalizePhoneNumber($to);

            // Get the sender ID
            $sender = SenderId::findOrFail($message->sender_id);

            // Prepare the payload
            $payload = [
                'user' => $sender->sender_id == 'gupta' ? $this->username : strtolower($sender->sender_id),
                'pass' => $sender->sender_id == 'gupta' ? $this->password : strtolower($sender->sender_id),
                'to' => $to,
                'from' => $sender->sender_id,
                'msg' => $message->content,
                'message_uuid' => Str::uuid()->toString(),
                'enable_msg_id' => true,
                'type' => 0, // Plain text SMS
            ];

            // Add callback URL if provided or if message has webhook_url
            if ($callbackUrl) {
                $payload['callback_url'] = $callbackUrl;
            } elseif ($message->webhook_url) {
                $payload['callback_url'] = $message->webhook_url;
            }

            // Make the API request
            $response = $this->httpClient()->post('/send', $payload);

            // Process response
            if ($response->successful()) {
                $responseBody = $response->body();

                // HollaTags response format is phone,message_id
                $parts = explode(',', $responseBody);
                $messageId = $parts[1] ?? null;

                if ($messageId) {
                    // Log success
                    Log::info('HollaTags API: Message sent successfully', [
                        'message_id' => $message->id,
                        'to' => $to,
                        'external_id' => $messageId,
                    ]);

                    // Update message with external ID and status
                    $message->update([
                        'external_message_id' => $messageId,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'data' => $responseBody,
                    ];
                }
            }

            // Log error
            Log::error('HollaTags API Error (Send SMS)', [
                'message_id' => $message->id,
                'to' => $to,
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->body() ?? 'Unknown error',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('HollaTags Exception (Send SMS)', [
                'message_id' => $message->id,
                'to' => $to,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a bulk SMS message
     *
     * @param Message $message
     * @param array $recipients
     * @param string|null $callbackUrl
     * @return array
     */
    public function sendBulkMessage(Message $message, array $recipients, string $callbackUrl = null)
    {
        try {
            // Check if recipients list is empty
            if (empty($recipients)) {
                return [
                    'success' => false,
                    'error' => 'Recipients list is empty',
                ];
            }

            // Normalize phone numbers
            $normalizedRecipients = array_map([$this, 'normalizePhoneNumber'], $recipients);

            // Remove duplicates
            $cleanedRecipients = array_unique(array_filter($normalizedRecipients));

            if (empty($cleanedRecipients)) {
                return [
                    'success' => false,
                    'error' => 'No valid recipients after cleaning',
                ];
            }

            // Format recipients for HollaTags (comma-separated string)
            $recipientsString = implode(',', $cleanedRecipients);

            // Get the sender ID
            $sender = SenderId::findOrFail($message->sender_id);

            // Prepare the payload
            $payload = [
                'user' => $this->username,
                'pass' => $this->password,
                'to' => $recipientsString,
                'from' => $sender->sender_id,
                'msg' => $message->content,
                'message_uuid' => Str::uuid()->toString(),
                'enable_msg_id' => true,
                'type' => 0, // Plain text SMS
            ];

            if ($callbackUrl) {
                $payload['callback_url'] = $callbackUrl;
            } elseif ($message->webhook_url) {
                $payload['callback_url'] = $message->webhook_url;
            }

            // Make the API request
            $response = $this->httpClient(60)->post('/send', $payload);

            // Process response
            if ($response->successful()) {
                $responseBody = $response->body();

                // Extract batch ID if available
                // For bulk messages, response might be different
                // This depends on HollaTags API format
                // $batchId = 'BATCH-' . $message->id . '-' . time();

                // HollaTags response format is phone,message_id
                $parts = explode(',', $responseBody);
                $messageId = $parts[1] ?? null;

                // Log success
                Log::info('HollaTags API: Bulk message sent successfully', [
                    'message_id' => $message->id,
                    'recipients_count' => count($cleanedRecipients),
                    'response' => $responseBody,
                ]);

                // Update message with batch ID and status
                $message->update([
                    'external_message_id' => $messageId,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'total_recipients' => count($cleanedRecipients),
                ]);

                return [
                    'success' => true,
                    'batch_id' => $messageId,
                    'recipients_count' => count($cleanedRecipients),
                    'data' => $responseBody,
                ];
            }

            // Log error
            Log::error('HollaTags API Error (Bulk SMS)', [
                'message_id' => $message->id,
                'recipients_count' => count($cleanedRecipients),
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->body() ?? 'Unknown error',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('HollaTags Exception (Bulk SMS)', [
                'message_id' => $message->id,
                'recipients_count' => count($recipients),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize phone number format for HollaTags API
     * Format required: 2348012345678 (no + sign)
     *
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone)
    {
        // Remove the + sign if present
        if (substr($phone, 0, 1) === '+') {
            $phone = substr($phone, 1);
        }

        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Format for Nigerian numbers: if starts with 0, replace with 234
        if (substr($phone, 0, 1) === '0') {
            $phone = '234' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Check message delivery status
     *
     * @param string $messageId
     * @return array
     */
    public function checkMessageStatus(string $messageId)
    {
        try {
            // Check cache first
            $cacheKey = $this->cachePrefix . 'message_status_' . $messageId;
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // HollaTags might have a different endpoint for status checks
            // Adjust this according to their API documentation
            $payload = [
                'user' => $this->username,
                'pass' => $this->password,
                'msg_id' => $messageId
            ];

            $response = $this->httpClient()->post('/status', $payload);

            if ($response->successful()) {
                $responseBody = $response->body();

                $result = [
                    'success' => true,
                    'data' => $responseBody,
                ];

                // Cache the result
                Cache::put($cacheKey, $result, $this->cacheTime);

                return $result;
            }

            $result = [
                'success' => false,
                'error' => $response->body() ?? 'Unknown error',
                'status_code' => $response->status(),
            ];

            // Cache failure for a shorter period
            Cache::put($cacheKey, $result, 300); // 5 minutes

            return $result;
        } catch (\Exception $e) {
            Log::error('HollaTags Exception (Check Message Status)', [
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
     * Get account balance
     *
     * @return array
     */
    public function getBalance()
    {
        try {
            // Check cache first
            $cacheKey = $this->cachePrefix . 'account_balance';
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Adjust payload according to HollaTags documentation
            $payload = [
                'user' => $this->username,
                'pass' => $this->password
            ];

            $response = $this->httpClient()->post('/balance', $payload);

            if ($response->successful()) {
                $responseBody = $response->body();

                // Parse balance from response
                // Adjust this based on HollaTags response format
                $balance = $responseBody ?? 0;

                $result = [
                    'success' => true,
                    'balance' => $balance,
                    'currency' => 'NGN',
                ];

                // Cache the result
                Cache::put($cacheKey, $result, 300); // 5 minutes

                return $result;
            }

            $result = [
                'success' => false,
                'error' => $response->body() ?? 'Unknown error',
                'status_code' => $response->status(),
            ];

            return $result;
        } catch (\Exception $e) {
            Log::error('HollaTags Exception (Get Balance)', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process delivery report webhook
     * Adjust this based on how HollaTags sends delivery reports
     *
     * @param array $payload
     * @return array
     */
    public function processDeliveryReport(array $payload)
    {
        try {
            if (isset($payload['msg_id'])) {
                $messageId = $payload['msg_id'];
                $status = $payload['status'] ?? null;

                // Find the message in our system
                $message = Message::where('external_message_id', $messageId)->first();

                if ($message && $status) {
                    // Map HollaTags status to our status
                    // Adjust this mapping based on HollaTags status codes
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

                    Log::info('HollaTags Webhook: Delivery status updated', [
                        'message_id' => $message->id,
                        'external_message_id' => $messageId,
                        'status' => $status,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Delivery status updated',
                    ];
                }

                Log::warning('HollaTags Webhook: Message not found', [
                    'external_message_id' => $messageId,
                ]);

                return [
                    'success' => false,
                    'error' => 'Message not found',
                ];
            }

            Log::warning('HollaTags Webhook: Invalid payload', [
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => 'Invalid payload',
            ];
        } catch (\Exception $e) {
            Log::error('HollaTags Exception (Process Delivery Report)', [
                'payload' => $payload,
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
