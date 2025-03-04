<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sms_wallet_id',
        'amount',
        'fee',
        'type', // 'deposit', 'withdrawal', 'message_payment', 'refund'
        'status', // 'pending', 'completed', 'failed', 'reversed'
        'reference',
        'payment_method', // 'flutterwave', 'wallet', etc.
        'payment_details',
        'description',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'payment_details' => 'array',
        'meta_data' => 'array',
    ];

    /**
     * Get the user associated with the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet associated with the transaction.
     */
    public function sms_wallet()
    {
        return $this->belongsTo(SmsWallet::class);
    }
}