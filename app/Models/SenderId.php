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
        'message_type', // 'transactional' or 'promotional'
        'purpose', // What they'll be sending
        'registration_option', // 'useGupta', 'customSender', 'standard'
        'status', // 'pending', 'approved', 'rejected'
        'verification_document',
        'rejection_reason',
        'notes', // Admin notes or system notes
        'external_id', // ID from HollaTags
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    /**
     * Get the networks this sender ID can use
     */
    public function getAvailableNetworksAttribute()
    {
        if ($this->message_type === 'promotional') {
            return ['MTN', 'GLO', '9MOBILE', 'AIRTEL'];
        }

        // For transactional, AIRTEL requires financial license
        $networks = ['MTN', 'GLO', '9MOBILE'];

        // Add AIRTEL if user has financial license (this would need to be tracked)
        // $networks[] = 'AIRTEL';

        return $networks;
    }

    /**
     * Get the requirements for this sender ID type
     */
    public function getRequirementsAttribute()
    {
        if ($this->message_type === 'promotional') {
            return ['CAC Certificate (recommended)'];
        }

        if ($this->registration_option === 'useGupta') {
            return ['CAC Certificate'];
        }

        return [
            'Four signed authorization letters (MTN, GLO, AIRTEL, 9MOBILE)',
            'CAC Certificate',
            'Website URL',
            'Financial operating license (for AIRTEL network)'
        ];
    }
}
