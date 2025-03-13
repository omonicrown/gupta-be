<?php

namespace App\Services;

use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwaveService
{
    /**
     * Initialize payment for wallet funding
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string|null $redirectUrl
     * @param string|null $description
     * @return array
     */
    public function initializePayment(User $user, float $amount, string $currency = 'NGN', string $redirectUrl = null, string $description = null)
    {
        try {
            // Check minimum amount
            if ($amount < 100) {
                return [
                    'success' => false,
                    'error' => 'Minimum amount is 100 ' . $currency
                ];
            }

            // Create transaction reference
            $txRef = 'SMS-' . strtoupper(Str::random(8)) . '-' . time();

            // Create a pending transaction record
            $transaction = SmsTransaction::create([
                'user_id' => $user->id,
                'sms_wallet_id' => $user->sms_wallet ? $user->sms_wallet->id : null,
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'pending',
                'reference' => $txRef,
                'payment_method' => 'flutterwave',
                'description' => $description ?? 'SMS Wallet Funding',
                'meta_data' => [
                    'payment_type' => 'sms_wallet',
                    'initiated_at' => now()->toDateTimeString()
                ]
            ]);

            // Prepare Flutterwave request payload
            $payload = [
                'tx_ref' => $txRef,
                'amount' => $amount,
                'currency' => $currency,
                'payment_options' => 'card,banktransfer,ussd',
                'redirect_url' => $redirectUrl ?? config('app.url') . '/api/wallet/verify',
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number ?? ''
                ],
                'meta' => [
                    'payment_type' => 'sms_wallet',
                    'transaction_id' => $transaction->id
                ],
                'customizations' => [
                    'title' => 'SMS Wallet Funding',
                    'description' => $description ?? 'Add funds to your SMS wallet',
                    'logo' => config('app.logo', '')
                ]
            ];

            // Call Flutterwave API
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                // Log error
                Log::error('Flutterwave API Error', [
                    'error' => $err,
                    'transaction_id' => $transaction->id
                ]);

                return [
                    'success' => false,
                    'error' => 'Error connecting to payment gateway: ' . $err
                ];
            }

            $responseData = json_decode($response, true);

            if (!isset($responseData['status']) || $responseData['status'] !== 'success') {
                // Log error
                Log::warning('Flutterwave initialization failed', [
                    'response' => $responseData,
                    'transaction_id' => $transaction->id
                ]);

                return [
                    'success' => false,
                    'error' => $responseData['message'] ?? 'Payment initialization failed'
                ];
            }

            // Update transaction with payment details
            $transaction->update([
                'payment_details' => [
                    'payment_id' => $responseData['data']['id'] ?? null,
                    'initialized_at' => now()->toDateTimeString()
                ]
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'payment_link' => $responseData['data']['link'],
                'tx_ref' => $txRef
            ];
        } catch (\Exception $e) {
            Log::error('Payment Initialization Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'amount' => $amount
            ]);

            return [
                'success' => false,
                'error' => 'Payment initialization failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment using transaction reference
     *
     * @param string $reference
     * @return array
     */
    public function verifyPayment(string $reference)
    {
        try {
            // Call Flutterwave verify endpoint
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $reference,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error('Flutterwave Verification Error', [
                    'error' => $err,
                    'reference' => $reference
                ]);

                return [
                    'success' => false,
                    'error' => 'Error verifying payment: ' . $err
                ];
            }

            $responseData = json_decode($response, true);

            if (!isset($responseData['status']) || $responseData['status'] !== 'success') {
                Log::warning('Flutterwave verification failed', [
                    'response' => $responseData,
                    'reference' => $reference
                ]);

                return [
                    'success' => false,
                    'error' => $responseData['message'] ?? 'Payment verification failed',
                    'verified' => false
                ];
            }

            // Check if transaction was successful
            if ($responseData['data']['status'] !== 'successful') {
                return [
                    'success' => true,
                    'verified' => false,
                    'message' => 'Payment not successful',
                    'status' => $responseData['data']['status']
                ];
            }

            // Find transaction in our database
            $transaction = SmsTransaction::where('reference', $reference)->first();
            
            if ($transaction) {
                // Update transaction if found
                $transaction->update([
                    'status' => 'completed',
                    'payment_details' => array_merge($transaction->payment_details ?? [], [
                        'flw_ref' => $responseData['data']['flw_ref'],
                        'transaction_id' => $responseData['data']['id'],
                        'verification_time' => now()->toDateTimeString(),
                    ])
                ]);

                // Update wallet if transaction was a deposit
                if ($transaction->type === 'deposit') {
                    $this->updateWalletBalance($transaction);
                }
            }

            return [
                'success' => true,
                'verified' => true,
                'transaction_id' => $transaction->id ?? null,
                'payment_id' => $responseData['data']['id'],
            ];
        } catch (\Exception $e) {
            Log::error('Payment Verification Error', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process webhook from Flutterwave
     *
     * @param array $payload
     * @return array
     */
    public function processWebhook(array $payload)
    {
        try {
            // Validate payload
            if (!isset($payload['event']) || !isset($payload['data'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid webhook payload'
                ];
            }

            // Process different events
            $event = $payload['event'];
            $data = $payload['data'];

            switch ($event) {
                case 'charge.completed':
                    return $this->processChargeCompleted($data);
                case 'transfer.completed':
                    return $this->processTransferCompleted($data);
                case 'transfer.failed':
                    return $this->processTransferFailed($data);
                default:
                    return [
                        'success' => true,
                        'message' => 'Event not handled: ' . $event
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Webhook Processing Error', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'error' => 'Webhook processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process charge.completed event
     *
     * @param array $data
     * @return array
     */
    private function processChargeCompleted(array $data)
    {
        // Check if this is a payment for SMS wallet
        $meta = $data['meta'] ?? [];
        $paymentType = $meta['payment_type'] ?? null;
        
        if ($paymentType !== 'sms_wallet') {
            return [
                'success' => true,
                'message' => 'Not an SMS wallet payment'
            ];
        }

        // Extract transaction details
        $txRef = $data['tx_ref'] ?? null;
        $status = $data['status'] ?? null;
        
        if (!$txRef || $status !== 'successful') {
            return [
                'success' => false,
                'error' => 'Invalid transaction reference or status'
            ];
        }

        // Find transaction in database
        $transaction = SmsTransaction::where('reference', $txRef)->first();
        
        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Transaction not found'
            ];
        }

        // Check if transaction is already completed
        if ($transaction->status === 'completed') {
            return [
                'success' => true,
                'message' => 'Transaction already processed'
            ];
        }

        try {
            DB::beginTransaction();

            // Update transaction status
            $transaction->update([
                'status' => 'completed',
                'payment_details' => array_merge($transaction->payment_details ?? [], [
                    'flw_ref' => $data['flw_ref'] ?? null,
                    'transaction_id' => $data['id'] ?? null,
                    'processed_at' => now()->toDateTimeString(),
                ])
            ]);

            // Update wallet balance
            $this->updateWalletBalance($transaction);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction_id' => $transaction->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error processing charge completion', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process transfer.completed event
     *
     * @param array $data
     * @return array
     */
    private function processTransferCompleted(array $data)
    {
        // This would handle withdrawals
        // Implement as needed based on your withdrawal system
        return [
            'success' => true,
            'message' => 'Transfer completion processed'
        ];
    }

    /**
     * Process transfer.failed event
     *
     * @param array $data
     * @return array
     */
    private function processTransferFailed(array $data)
    {
        // This would handle failed withdrawals
        // Implement as needed based on your withdrawal system
        return [
            'success' => true,
            'message' => 'Transfer failure processed'
        ];
    }

    /**
     * Update wallet balance based on transaction
     *
     * @param SmsTransaction $transaction
     * @return void
     */
    private function updateWalletBalance(SmsTransaction $transaction)
    {
        // Get user wallet
        $wallet = SmsWallet::where('user_id', $transaction->user_id)->first();
        
        // Create wallet if it doesn't exist
        if (!$wallet) {
            $user = User::find($transaction->user_id);
            $wallet = SmsWallet::create([
                'user_id' => $transaction->user_id,
                'balance' => 0,
                'currency' => 'NGN',
                'status' => 'active',
            ]);
        }

        // Update wallet balance
        if ($transaction->type === 'deposit') {
            $wallet->update([
                'balance' => $wallet->balance + $transaction->amount
            ]);
        } elseif ($transaction->type === 'withdrawal') {
            $wallet->update([
                'balance' => $wallet->balance - $transaction->amount
            ]);
        }
    }
}