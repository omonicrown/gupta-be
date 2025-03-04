<?php

namespace App\Services;

use App\Models\SmsTransaction;
use App\Models\SmsWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Create wallet for user
     *
     * @param User $user
     * @param string $currency
     * @return \App\Models\SmsWallet
     */
    public function createWallet(User $user, string $currency = 'NGN')
    {
        return SmsWallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => $currency,
            'status' => 'active',
        ]);
    }
    
    /**
     * Get wallet balance
     *
     * @param User $user
     * @return float
     */
    public function getBalance(User $user)
    {
        $wallet = $user->sms_wallet; // Use property instead of method
    
        if (!$wallet) {
            return 0;
        }
    
        return $wallet->balance;
    }
    
    /**
     * Deduct from wallet for message sending
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @return array
     */
    public function deductForMessage(User $user, float $amount, string $description = 'SMS Sending')
    {
        try {
            $wallet = $user->sms_wallet;
            
            if (!$wallet) {
                return [
                    'success' => false,
                    'error' => 'User does not have a wallet',
                ];
            }
            
            if ($wallet->status !== 'active') {
                return [
                    'success' => false,
                    'error' => 'Wallet is not active',
                ];
            }
            
            if ($wallet->balance < $amount) {
                return [
                    'success' => false,
                    'error' => 'Insufficient balance',
                ];
            }
            
            // Start database transaction
            return DB::transaction(function () use ($wallet, $user, $amount, $description) {
                // Create transaction record
                $transaction = SmsTransaction::create([
                    'user_id' => $user->id,
                    'sms_wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'type' => 'message_payment',
                    'status' => 'pending',
                    'reference' => 'MSG-' . Str::random(10) . '-' . time(),
                    'payment_method' => 'wallet',
                    'description' => $description,
                ]);
                
                // Update wallet balance
                $wallet->update([
                    'balance' => $wallet->balance - $amount,
                ]);
                
                // Mark transaction as completed
                $transaction->update([
                    'status' => 'completed',
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transaction->id,
                    'new_balance' => $wallet->balance,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Wallet Service Exception (Deduct For Message)', [
                'user_id' => $user->id,
                'amount' => $amount,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Add funds to wallet manually (admin operation)
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @param User $admin
     * @return array
     */
    public function addFundsManually(User $user, float $amount, string $description = 'Manual Deposit', User $admin = null)
    {
        try {
            $wallet = $user->sms_wallet;
            
            if (!$wallet) {
                return [
                    'success' => false,
                    'error' => 'User does not have a wallet',
                ];
            }
            
            if ($wallet->status !== 'active') {
                return [
                    'success' => false,
                    'error' => 'Wallet is not active',
                ];
            }
            
            // Start database transaction
            return DB::transaction(function () use ($wallet, $user, $amount, $description, $admin) {
                // Create transaction record
                $transaction = SmsTransaction::create([
                    'user_id' => $user->id,
                    'sms_wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'status' => 'pending',
                    'reference' => 'MANUAL-' . Str::random(10) . '-' . time(),
                    'payment_method' => 'manual',
                    'description' => $description,
                    'meta_data' => [
                        'added_by' => $admin ? $admin->id : 'system',
                        'admin_name' => $admin ? $admin->name : null,
                    ],
                ]);
                
                // Update wallet balance
                $wallet->update([
                    'balance' => $wallet->balance + $amount,
                ]);
                
                // Mark transaction as completed
                $transaction->update([
                    'status' => 'completed',
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transaction->id,
                    'new_balance' => $wallet->balance,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Wallet Service Exception (Add Funds Manually)', [
                'user_id' => $user->id,
                'amount' => $amount,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get transaction history
     *
     * @param User $user
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getTransactionHistory(User $user, int $perPage = 10)
    {
        return SmsTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
    
    /**
     * Change wallet status
     *
     * @param User $user
     * @param string $status
     * @return array
     */
    public function changeWalletStatus(User $user, string $status)
    {
        try {
            $wallet = $user->sms_wallet;
            
            if (!$wallet) {
                return [
                    'success' => false,
                    'error' => 'User does not have a wallet',
                ];
            }
            
            if (!in_array($status, ['active', 'suspended'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid status',
                ];
            }
            
            $wallet->update([
                'status' => $status,
            ]);
            
            return [
                'success' => true,
                'wallet_id' => $wallet->id,
                'status' => $status,
            ];
        } catch (\Exception $e) {
            Log::error('Wallet Service Exception (Change Wallet Status)', [
                'user_id' => $user->id,
                'status' => $status,
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}