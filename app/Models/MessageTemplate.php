<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'description',
        'message_type', // 'sms', 'mms', etc.
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    /**
     * Get the user that owns the message template.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages that use this template.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}