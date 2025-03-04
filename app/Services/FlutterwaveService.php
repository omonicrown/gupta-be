<?php

namespace App\Services;

use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwaveService
{
    protected $baseUrl;
    protected $secretKey;
    protected $publicKey;
    protected $encryptionKey;
    
    public function __construct()
    {
        $this->baseUrl = config('services.flutterwave.base_url');
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->publicKey = config('services.flutterwave.public_key');
        $this->encryptionKey = config('services.flutterwave.encryption_key');
    }
    
    /**
     * Get HTTP client instance with default headers
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function httpClient()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl);
    }
    
    /**
     * Initialize a payment transaction
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @param string $redirectUrl
     * @param string $description
     * @return array
     */
    public function initializePayment(User $user, float $amount, string $currency = 'NGN', string $redirectUrl = null, string $description = 'Wallet Funding')
    {
        try {
            $reference = 'FLW-' . Str::random(10) . '-' . time();
            
            // Create a pending transaction
            $transaction = SmsTransaction::create([
                'user_id' => $user->id,
                'sms_wallet_id' => $user->sms_wallet->id,
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'pending',
                'reference' => $reference,
                'payment_method' => 'flutterwave',
                'description' => $description,
            ]);
            
            $response = $this->httpClient()->post('/payments', [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'redirect_url' => $redirectUrl ?? route('payments.callback'),
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phonenumber' => $user->phone,
                ],
                'meta' => [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                ],
                'customizations' => [
                    'title' => 'Wallet Funding',
                    'description' => $description,
                ],
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update transaction with payment details
                $transaction->update([
                    'payment_details' => [
                        'payment_link' => $responseData['data']['link'] ?? null,
                        'flw_ref' => $responseData['data']['flw_ref'] ?? null,
                    ],
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transaction->id,
                    'payment_link' => $responseData['data']['link'] ?? null,
                    'data' => $responseData,
                ];
            }
            
            // Log error
            Log::error('Flutterwave API Error (Initialize Payment)', [
                'transaction_id' => $transaction->id,
                'status_code' => $response->status(),
                'response' => $response->json(),
            ]);
            
            // Mark transaction as failed
            $transaction->update(['status' => 'failed']);
            
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave Exception (Initialize Payment)', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verify a payment transaction
     *
     * @param string $transactionReference
     * @return array
     */
    public function verifyPayment(string $transactionReference)
    {
        try {
            $response = $this->httpClient()->get('/transactions/verify_by_reference?tx_ref=' . $transactionReference);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Check if payment was successful
                if ($responseData['data']['status'] === 'successful') {
                    // Find the transaction
                    $transaction = SmsTransaction::where('reference', $transactionReference)->first();
                    
                    if ($transaction) {
                        // Update transaction status and details
                        $transaction->update([
                            'status' => 'completed',
                            'payment_details' => array_merge($transaction->payment_details ?? [], [
                                'transaction_id' => $responseData['data']['id'] ?? null,
                                'charged_amount' => $responseData['data']['charged_amount'] ?? null,
                                'payment_type' => $responseData['data']['payment_type'] ?? null,
                            ]),
                        ]);
                        
                        // Update wallet balance
                        $wallet = SmsWallet::find($transaction->sms_wallet_id);
                        $wallet->update([
                            'balance' => $wallet->balance + $transaction->amount,
                        ]);
                        
                        return [
                            'success' => true,
                            'verified' => true,
                            'transaction_id' => $transaction->id,
                            'data' => $responseData,
                        ];
                    }
                }
                
                return [
                    'success' => true,
                    'verified' => false,
                    'data' => $responseData,
                ];
            }
            
            // Log error
            Log::error('Flutterwave API Error (Verify Payment)', [
                'transaction_reference' => $transactionReference,
                'status_code' => $response->status(),
                'response' => $response->json(),
            ]);
            
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Unknown error',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave Exception (Verify Payment)', [
                'transaction_reference' => $transactionReference,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
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
            // Verify signature
            $signature = request()->header('verif-hash');
            $secretHash = config('services.flutterwave.webhook_hash');
            
            if (!$signature || $signature !== $secretHash) {
                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }
            
            // Process transaction
            $reference = $payload['data']['tx_ref'] ?? null;
            
            if (!$reference) {
                return [
                    'success' => false,
                    'error' => 'Missing transaction reference',
                ];
            }
            
            // Find the transaction
            $transaction = SmsTransaction::where('reference', $reference)->first();
            
            if (!$transaction) {
                return [
                    'success' => false,
                    'error' => 'Transaction not found',
                ];
            }
            
            // Check if the transaction is already processed
            if ($transaction->status === 'completed') {
                return [
                    'success' => true,
                    'message' => 'Transaction already processed',
                ];
            }
            
            // Verify transaction status
            if ($payload['data']['status'] === 'successful') {
                // Update transaction status and details
                $transaction->update([
                    'status' => 'completed',
                    'payment_details' => array_merge($transaction->payment_details ?? [], [
                        'transaction_id' => $payload['data']['id'] ?? null,
                        'charged_amount' => $payload['data']['charged_amount'] ?? null,
                        'payment_type' => $payload['data']['payment_type'] ?? null,
                    ]),
                ]);
                
                // Update wallet balance
                $wallet = SmsWallet::find($transaction->sms_wallet_id);
                $wallet->update([
                    'balance' => $wallet->balance + $transaction->amount,
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Transaction processed successfully',
                ];
            }
            
            // Transaction failed
            $transaction->update([
                'status' => 'failed',
                'payment_details' => array_merge($transaction->payment_details ?? [], [
                    'failure_reason' => $payload['data']['status'] ?? 'Unknown',
                ]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Transaction marked as failed',
            ];
        } catch (\Exception $e) {
            Log::error('Flutterwave Exception (Process Webhook)', [
                'exception' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}