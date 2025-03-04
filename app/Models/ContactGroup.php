<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ContactGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Get the user that owns the contact group.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contacts for the group.
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_group_contact');
    }

    /**
     * Get the messages sent to this group.
     */
    public function messages()
    {
        return $this->morphToMany(Message::class, 'messageable');
    }
}