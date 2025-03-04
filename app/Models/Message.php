<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sender_id',
        'content',
        'message_type', // 'sms', 'mms', etc.
        'status', // 'draft', 'queued', 'sent', 'delivered', 'failed'
        'scheduled_at',
        'sent_at',
        'campaign_id',
        'message_template_id',
        'external_message_id', // ID from HollaTags
        'delivery_status',
        'delivery_status_time',
        'total_recipients',
        'successful_sends',
        'failed_sends',
        'cost'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivery_status_time' => 'datetime',
        'cost' => 'decimal:4',
    ];

    /**
     * Get the user that owns the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sender ID associated with the message.
     */
    public function sender()
    {
        return $this->belongsTo(SenderId::class, 'sender_id');
    }

    /**
     * Get all of the contacts that received the message.
     */
    public function contacts()
    {
        return $this->morphedByMany(Contact::class, 'messageable');
    }

    /**
     * Get all of the contact groups that received the message.
     */
    public function contactGroups()
    {
        return $this->morphedByMany(ContactGroup::class, 'messageable')
            ->select('contact_groups.*'); // Specify all fields from contact_groups table
    }

    /**
     * Get the campaign associated with the message.
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the message template associated with the message.
     */
    public function messageTemplate()
    {
        return $this->belongsTo(MessageTemplate::class);
    }
}