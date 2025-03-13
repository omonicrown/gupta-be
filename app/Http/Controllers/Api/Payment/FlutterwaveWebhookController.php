<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VendorWallet;
use App\Models\Witdrawal;
use App\Mail\completeTransaction;
use App\Mail\failedTransaction;
use App\Mail\CustomerReciept;
use App\Mail\VendorReciept;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FlutterwaveWebhookController extends BaseController
{
    /**
     * Handle Flutterwave webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature if available
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Invalid webhook signature', [
                'payload' => $request->all(),
                'headers' => $request->header()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature'
            ], 400);
        }

        try {
            // Log the webhook data
            Log::info('Flutterwave Webhook Received', [
                'payload' => $request->all()
            ]);

            // Get event type and data
            $eventType = $request->input('event');
            $data = $request->input('data');

            if (!$eventType || !$data) {
                Log::warning('Invalid webhook data', [
                    'payload' => $request->all()
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid webhook data'
                ], 400);
            }

            // Process based on event type
            switch ($eventType) {
                case 'charge.completed':
                    return $this->handleChargeCompleted($data);
                case 'transfer.completed':
                    return $this->handleTransferCompleted($data);
                case 'transfer.failed':
                    return $this->handleTransferFailed($data);
                default:
                    Log::info('Unhandled webhook event', [
                        'event' => $eventType
                    ]);
                    return response()->json([
                        'status' => true,
                        'message' => 'Unhandled event type'
                    ]);
            }
        } catch (Exception $e) {
            Log::error('Webhook Processing Error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify webhook signature from Flutterwave
     *
     * @param Request $request
     * @return bool
     */
    private function verifyWebhookSignature(Request $request)
    {
        // Get the signature from the header
        $signature = $request->header('verif-hash');
        
        // If no signature in testing environment, allow it
        if (!$signature && app()->environment('local', 'testing')) {
            return true;
        }

        // Get the secret hash from environment
        $secretHash = env('FLUTTERWAVE_SECRET_HASH');
        
        // If we don't have the secret hash, we can't verify
        if (!$secretHash) {
            Log::warning('Flutterwave secret hash not configured');
            return false;
        }

        // Verify the signature
        return hash_equals($secretHash, $signature);
    }

    /**
     * Handle charge.completed event
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleChargeCompleted($data)
    {
        // Extract relevant data
        $status = $data['status'] ?? null;
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'NGN';
        $txRef = $data['tx_ref'] ?? null;
        $flwRef = $data['flw_ref'] ?? null;
        $customerId = $data['customer']['id'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;
        $transactionId = $data['id'] ?? null;
        $meta = $data['meta'] ?? [];

        // If status isn't successful, log and return
        if ($status !== 'successful') {
            Log::info('Charge not successful', [
                'status' => $status,
                'tx_ref' => $txRef
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Charge not successful'
            ]);
        }

        // Check meta data to determine payment type
        $paymentType = $meta['payment_type'] ?? 'unknown';

        // Process based on payment type
        switch ($paymentType) {
            case 'sms_wallet':
                return $this->processSmsWalletTopup($data);
            case 'subscription':
                return $this->processSubscriptionPayment($data);
            case 'product':
                return $this->processProductPayment($data);
            default:
                // Try to determine payment type from other data
                if (isset($meta['phone_number']) && is_numeric($meta['phone_number'])) {
                    // It's likely a product payment with customer phone in meta
                    return $this->processProductPayment($data);
                } else if (isset($meta['subscription_type'])) {
                    // It's a subscription payment
                    return $this->processSubscriptionPayment($data);
                } else {
                    // Find existing transaction by tx_ref
                    $transaction = SmsTransaction::where('reference', $txRef)->first();
                    if ($transaction) {
                        return $this->processSmsWalletTopup($data);
                    }
                    
                    $transaction = Transaction::where('tnx_ref', $txRef)->first();
                    if ($transaction) {
                        return $this->processProductPayment($data);
                    }
                }
                
                Log::info('Unknown payment type', [
                    'tx_ref' => $txRef,
                    'meta' => $meta
                ]);
                
                return response()->json([
                    'status' => true,
                    'message' => 'Payment recorded but not processed (unknown type)'
                ]);
        }
    }

    /**
     * Process SMS wallet top-up
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function processSmsWalletTopup($data)
    {
        // Extract relevant data
        $status = $data['status'] ?? null;
        $amount = floatval($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'NGN';
        $txRef = $data['tx_ref'] ?? null;
        $flwRef = $data['flw_ref'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;
        $transactionId = $data['id'] ?? null;
        $meta = $data['meta'] ?? [];

        // Transaction must have a reference
        if (!$txRef) {
            Log::warning('Missing transaction reference', [
                'data' => $data
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Missing transaction reference'
            ], 400);
        }

        // Find user by email
        $user = User::where('email', $customerEmail)->first();
        if (!$user) {
            Log::warning('User not found for SMS wallet top-up', [
                'email' => $customerEmail,
                'tx_ref' => $txRef
            ]);
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check minimum amount for top-up
        if ($amount < 1000) {
            Log::warning('Amount below minimum for SMS wallet top-up', [
                'amount' => $amount,
                'user_id' => $user->id,
                'tx_ref' => $txRef
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Amount below minimum'
            ], 400);
        }

        // Find the transaction by reference
        $transaction = SmsTransaction::where('reference', $txRef)->first();

        try {
            DB::beginTransaction();

            // Get or create wallet
            $wallet = SmsWallet::where('user_id', $user->id)->first();
            if (!$wallet) {
                $wallet = SmsWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => $currency,
                    'status' => 'active',
                ]);
            }

            // If transaction already exists, check if it's already completed
            if ($transaction) {
                if ($transaction->status === 'completed') {
                    // Already processed
                    Log::info('Transaction already processed', [
                        'transaction_id' => $transaction->id,
                        'tx_ref' => $txRef
                    ]);
                    DB::commit();
                    return response()->json([
                        'status' => true,
                        'message' => 'Transaction already processed'
                    ]);
                }
                
                // Update transaction status
                $transaction->update([
                    'status' => 'completed',
                    'payment_details' => array_merge($transaction->payment_details ?? [], [
                        'flw_ref' => $flwRef,
                        'transaction_id' => $transactionId,
                        'payment_time' => now()->toDateTimeString(),
                    ])
                ]);
            } else {
                // Create new transaction record
                $transaction = SmsTransaction::create([
                    'user_id' => $user->id,
                    'sms_wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'status' => 'completed',
                    'reference' => $txRef,
                    'payment_method' => 'flutterwave',
                    'description' => 'SMS Wallet Top-up',
                    'payment_details' => [
                        'flw_ref' => $flwRef,
                        'transaction_id' => $transactionId,
                        'payment_time' => now()->toDateTimeString(),
                    ],
                    'meta_data' => $meta
                ]);
            }

            // Update wallet balance
            $wallet->update([
                'balance' => $wallet->balance + $amount
            ]);

            // Send notification email (you can implement this)
            // $this->sendWalletTopupNotification($user, $amount, $transaction);

            DB::commit();

            Log::info('SMS Wallet top-up successful', [
                'user_id' => $user->id,
                'amount' => $amount,
                'tx_ref' => $txRef,
                'transaction_id' => $transaction->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'SMS Wallet top-up processed successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SMS Wallet top-up failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'tx_ref' => $txRef,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing SMS wallet top-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process subscription payment
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function processSubscriptionPayment($data)
    {
        // Extract relevant data
        $status = $data['status'] ?? null;
        $amount = floatval($data['amount'] ?? 0);
        $txRef = $data['tx_ref'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;

        try {
            // Find user by email
            $user = User::where('email', $customerEmail)->first();
            if (!$user) {
                Log::warning('User not found for subscription payment', [
                    'email' => $customerEmail,
                    'tx_ref' => $txRef
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Determine subscription type and duration based on amount
            $current = Carbon::now();
            $subType = null;
            $subEnd = null;

            // Basic subscription
            if ($amount == 1500 || $amount == 4275 || $amount == 8550 || $amount == 17100) {
                $subType = 'basic';
                $noOfLinks = 25;
                
                if ($amount == 1500) {
                    $subEnd = $current->copy()->addDays(30);
                } elseif ($amount == 4275) {
                    $subEnd = $current->copy()->addMonths(3);
                } elseif ($amount == 8550) {
                    $subEnd = $current->copy()->addMonths(6);
                } else {
                    $subEnd = $current->copy()->addMonths(12);
                }
            }
            // Popular subscription
            elseif ($amount == 2500 || $amount == 7125 || $amount == 14250 || $amount == 28500) {
                $subType = 'popular';
                $noOfLinks = 100;
                
                if ($amount == 2500) {
                    $subEnd = $current->copy()->addDays(30);
                } elseif ($amount == 7125) {
                    $subEnd = $current->copy()->addMonths(3);
                } elseif ($amount == 14250) {
                    $subEnd = $current->copy()->addMonths(6);
                } else {
                    $subEnd = $current->copy()->addMonths(12);
                }
            }
            // Premium subscription
            elseif ($amount == 7000 || $amount == 19950 || $amount == 39900 || $amount == 79800) {
                $subType = 'premium';
                $noOfLinks = 2000;
                
                if ($amount == 7000) {
                    $subEnd = $current->copy()->addDays(30);
                } elseif ($amount == 19950) {
                    $subEnd = $current->copy()->addMonths(3);
                } elseif ($amount == 39900) {
                    $subEnd = $current->copy()->addMonths(6);
                } else {
                    $subEnd = $current->copy()->addMonths(12);
                }
            }

            // If we couldn't determine the subscription type, log and return
            if (!$subType || !$subEnd) {
                Log::warning('Unknown subscription amount', [
                    'amount' => $amount,
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Unknown subscription amount'
                ], 400);
            }

            DB::beginTransaction();

            // Update user subscription
            $user->update([
                'sub_type' => $subType,
                'sub_status' => 'active',
                'sub_start' => $current->toDateString(),
                'sub_end' => $subEnd->toDateString(),
                'no_of_wlink' => $noOfLinks,
                'no_of_rlink' => $noOfLinks,
                'no_of_mlink' => $noOfLinks,
                'no_of_mstore' => ($subType == 'basic' ? 250 : ($subType == 'popular' ? 500 : 5000)),
                'no_of_malink' => ($subType == 'basic' ? 5 : ($subType == 'popular' ? 100 : 200)),
            ]);

            // Create or update subscription record
            $subscription = \App\Models\Subscription::updateOrCreate(
                ['tnx_ref' => $txRef],
                [
                    'user_id' => $user->id,
                    'sub_type' => $subType,
                    'amount_paid' => $amount,
                    'user_email' => $user->email,
                    'subscription_status' => 'paid',
                    'currency' => 'ngn',
                ]
            );

            DB::commit();

            Log::info('Subscription payment processed', [
                'user_id' => $user->id,
                'subscription_type' => $subType,
                'amount' => $amount,
                'tx_ref' => $txRef
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Subscription payment processed successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Subscription payment processing failed', [
                'error' => $e->getMessage(),
                'tx_ref' => $txRef
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing subscription payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process product payment
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function processProductPayment($data)
    {
        // Extract relevant data
        $status = $data['status'] ?? null;
        $amount = floatval($data['amount'] ?? 0);
        $txRef = $data['tx_ref'] ?? null;
        $flwRef = $data['flw_ref'] ?? null;
        $customerPhone = $data['customer']['phone'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;
        $customerName = $data['customer']['name'] ?? null;

        try {
            // Find transaction by customer phone number (stored in meta)
            $transaction = Transaction::where('id', $customerPhone)
                ->orWhere('tnx_ref', $txRef)
                ->first();

            if (!$transaction) {
                Log::warning('Transaction not found for product payment', [
                    'tx_ref' => $txRef,
                    'customer_phone' => $customerPhone
                ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            // Check if transaction is already processed
            if ($transaction->transaction_status === 'successful') {
                Log::info('Transaction already processed', [
                    'transaction_id' => $transaction->id,
                    'tx_ref' => $txRef
                ]);
                return response()->json([
                    'status' => true,
                    'message' => 'Transaction already processed'
                ]);
            }

            DB::beginTransaction();

            // Update the transaction
            $transaction->update([
                'amount_paid' => $amount,
                'transaction_status' => 'successful',
                'tnx_ref' => $txRef
            ]);

            // Update vendor wallet
            $wallet = VendorWallet::where('user_id', $transaction->user_id)->first();
            if ($wallet && $wallet->last_tnx_ref !== $txRef) {
                $wallet->update([
                    'previous_amount' => $wallet->total_amount,
                    'total_amount' => $wallet->total_amount + $amount,
                    'last_tnx_ref' => $txRef
                ]);

                // Send emails to customer and vendor
                $this->sendProductPaymentEmails($transaction, $amount);
            }

            DB::commit();

            Log::info('Product payment processed', [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'tx_ref' => $txRef
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Product payment processed successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Product payment processing failed', [
                'error' => $e->getMessage(),
                'tx_ref' => $txRef
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing product payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle transfer.completed event
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleTransferCompleted($data)
    {
        // Extract relevant data
        $status = $data['status'] ?? null;
        $amount = floatval($data['amount'] ?? 0);
        $reference = $data['reference'] ?? null;

        // Find withdrawal request
        $withdrawal = Witdrawal::where('reference', $reference)->first();
        if (!$withdrawal) {
            Log::warning('Withdrawal not found', [
                'reference' => $reference
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Withdrawal not found'
            ]);
        }

        try {
            // Update withdrawal status
            $withdrawal->update([
                'status' => 'SUCCESSFUL'
            ]);

            // Send successful withdrawal email
            $details = [
                'custname' => $withdrawal->first_name,
                'amount' => $amount
            ];

            Mail::to($withdrawal->email)->send(new completeTransaction($details));

            Log::info('Withdrawal completed successfully', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $amount,
                'reference' => $reference
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Withdrawal completed successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error processing withdrawal completion', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing withdrawal completion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle transfer.failed event
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleTransferFailed($data)
    {
        // Extract relevant data
        $status = $data['status'] ?? null;
        $amount = floatval($data['amount'] ?? 0);
        $reference = $data['reference'] ?? null;

        // Find withdrawal request
        $withdrawal = Witdrawal::where('reference', $reference)->first();
        if (!$withdrawal) {
            Log::warning('Withdrawal not found', [
                'reference' => $reference
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Withdrawal not found'
            ]);
        }

        try {
            DB::beginTransaction();

            // Update withdrawal status
            $withdrawal->update([
                'status' => 'FAILED'
            ]);

            // Refund amount to vendor wallet
            $wallet = VendorWallet::where('user_email', $withdrawal->email)->first();
            if ($wallet) {
                $wallet->update([
                    'total_amount' => $wallet->total_amount + $amount
                ]);
            }

            // Send failed withdrawal email
            $details = [
                'custname' => $withdrawal->first_name,
                'amount' => $amount
            ];

            Mail::to($withdrawal->email)->send(new failedTransaction($details));

            DB::commit();

            Log::info('Withdrawal failed and refunded', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $amount,
                'reference' => $reference
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Withdrawal failed and amount refunded'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing withdrawal failure', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error processing withdrawal failure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send product payment notification emails
     *
     * @param Transaction $transaction
     * @param float $amount
     * @return void
     */
    private function sendProductPaymentEmails($transaction, $amount)
    {
        try {
            // Send email to customer
            $customerDetails = [
                'custname' => $transaction->customer_name,
                'vendor_contact' => $transaction->user_phone_number,
                'pay_for' => $transaction->paying_for,
                'quantity' => $transaction->product_qty,
                'amount' => $amount
            ];
            
            Mail::to($transaction->customer_email)
                ->send(new CustomerReciept($customerDetails));

            // Send email to vendor
            $vendorDetails = [
                'custname' => $transaction->customer_name,
                'customer_contact' => $transaction->customer_phone_number,
                'pay_for' => $transaction->paying_for,
                'quantity' => $transaction->product_qty,
                'amount' => $amount
            ];
            
            Mail::to($transaction->user_email)
                ->send(new VendorReciept($vendorDetails));

        } catch (Exception $e) {
            Log::error('Error sending product payment emails', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);
        }
    }
}