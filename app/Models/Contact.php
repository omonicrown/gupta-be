<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'custom_fields',
        'status'
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    /**
     * Get the user that owns the contact.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the groups that the contact belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_contact');
    }

    /**
     * Get the messages sent to this contact.
     */
    public function messages()
    {
        return $this->morphToMany(Message::class, 'messageable');
    }
}