<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SenderId extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sender_id', // The actual sender ID text
        'status', // 'pending', 'approved', 'rejected'
        'verification_document',
        'rejection_reason',
        'external_id', // ID from HollaTags
    ];

    /**
     * Get the user that owns the sender ID.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages sent using this sender ID.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}