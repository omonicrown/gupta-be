<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\HollaTagsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSingleMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected $recipientPhone;
    protected $maxAttempts = 3;

    /**
     * Create a new job instance.
     *
     * @param Message $message
     * @param string $recipientPhone
     * @return void
     */
    public function __construct(Message $message, string $recipientPhone)
    {
        $this->message = $message;
        $this->recipientPhone = $recipientPhone;
    }

    /**
     * Execute the job.
     *
     * @param HollaTagsService $hollaTagsService
     * @return void
     */
    public function handle(HollaTagsService $hollaTagsService)
    {
        try {
            // Generate a callback URL with the message ID
            $callbackUrl = config('app.url') . '/api/webhooks/hollatags/delivery/' . $this->message->id;

            // Send message via HollaTags with callback URL
            $result = $hollaTagsService->sendSingleMessage($this->message, $this->recipientPhone, $callbackUrl);

            if (!$result['success']) {
                Log::error('Failed to send message', [
                    'message_id' => $this->message->id,
                    'recipient' => $this->recipientPhone,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                // Retry the job with exponential backoff
                if ($this->attempts() < $this->maxAttempts) {
                    $this->release(30 * $this->attempts());
                }

                return;
            }

            Log::info('Message sent successfully', [
                'message_id' => $this->message->id,
                'recipient' => $this->recipientPhone,
                'external_id' => $result['message_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Exception in SendSingleMessage job', [
                'message_id' => $this->message->id,
                'recipient' => $this->recipientPhone,
                'exception' => $e->getMessage(),
            ]);

            // Retry the job with exponential backoff
            if ($this->attempts() < $this->maxAttempts) {
                $this->release(30 * $this->attempts());
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendSingleMessage job failed', [
            'message_id' => $this->message->id,
            'recipient' => $this->recipientPhone,
            'exception' => $exception->getMessage(),
        ]);
    }
}

class SendBulkMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected $recipientPhones;
    protected $maxAttempts = 3;

    /**
     * Create a new job instance.
     *
     * @param Message $message
     * @param array $recipientPhones
     * @return void
     */
    public function __construct(Message $message, array $recipientPhones)
    {
        $this->message = $message;
        $this->recipientPhones = $recipientPhones;
    }

    /**
     * Execute the job.
     *
     * @param HollaTagsService $hollaTagsService
     * @return void
     */
    public function handle(HollaTagsService $hollaTagsService)
    {
        try {
            // Generate a callback URL with the message ID
            $callbackUrl = config('app.url') . '/api/webhooks/hollatags/delivery/' . $this->message->id;

            // Send bulk message via HollaTags with callback URL
            $result = $hollaTagsService->sendBulkMessage($this->message, $this->recipientPhones, $callbackUrl);

            if (!$result['success']) {
                Log::error('Failed to send bulk message', [
                    'message_id' => $this->message->id,
                    'recipient_count' => count($this->recipientPhones),
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                // For large batches, split and retry if needed
                if ($this->attempts() < $this->maxAttempts && count($this->recipientPhones) > 100) {
                    $chunks = array_chunk($this->recipientPhones, ceil(count($this->recipientPhones) / 2));

                    foreach ($chunks as $chunk) {
                        SendBulkMessages::dispatch($this->message, $chunk);
                    }
                } else if ($this->attempts() < $this->maxAttempts) {
                    $this->release(60 * $this->attempts());
                }

                return;
            }

            Log::info('Bulk message sent successfully', [
                'message_id' => $this->message->id,
                'recipient_count' => count($this->recipientPhones),
                'batch_id' => $result['batch_id'] ?? null,
            ]);

            // Update the message stats
            $this->message->update([
                'successful_sends' => $this->message->successful_sends + count($this->recipientPhones),
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in SendBulkMessages job', [
                'message_id' => $this->message->id,
                'recipient_count' => count($this->recipientPhones),
                'exception' => $e->getMessage(),
            ]);

            // Retry the job with exponential backoff
            if ($this->attempts() < $this->maxAttempts) {
                $this->release(60 * $this->attempts());
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendBulkMessages job failed', [
            'message_id' => $this->message->id,
            'recipient_count' => count($this->recipientPhones),
            'exception' => $exception->getMessage(),
        ]);

        // Update the message stats
        $this->message->update([
            'failed_sends' => $this->message->failed_sends + count($this->recipientPhones),
        ]);
    }
}

class ProcessScheduledMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = now();

        // Find all scheduled messages that are due to be sent
        $scheduledMessages = Message::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        foreach ($scheduledMessages as $message) {
            // Update message status to queued
            $message->update([
                'status' => 'queued',
            ]);

            // Queue message for sending
            // We'll use the same MessageService::sendMessage() method
            // This will handle wallet deduction and actual sending
            dispatch(new ProcessMessageSending($message->id));
        }
    }
}

class ProcessMessageSending implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageId;

    /**
     * Create a new job instance.
     *
     * @param int $messageId
     * @return void
     */
    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $messageService = app(\App\Services\MessageService::class);
        $messageService->sendMessage($this->messageId);
    }
}

class CheckMessageDeliveryStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageId;

    /**
     * Create a new job instance.
     *
     * @param int $messageId
     * @return void
     */
    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     *
     * @param HollaTagsService $hollaTagsService
     * @return void
     */
    public function handle(HollaTagsService $hollaTagsService)
    {
        $message = Message::find($this->messageId);

        if (!$message || !$message->external_message_id) {
            return;
        }

        // Only check status for messages in certain states
        if (!in_array($message->status, ['sent', 'queued'])) {
            return;
        }

        // Check status from HollaTags
        $result = $hollaTagsService->checkMessageStatus($message->external_message_id);

        if ($result['success']) {
            // Update message with latest status
            $status = $result['data']['status'] ?? null;

            if ($status) {
                $messageService = app(\App\Services\MessageService::class);
                $messageService->updateDeliveryStatus($message->external_message_id, $status);
            }
        }
    }
}