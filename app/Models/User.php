<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'account_type',
        'country',
        'business_category',
        'sub_status',
        'sub_start',
        'sub_end',
        'role',
        'no_of_wlink',
        'no_of_rlink',
        'no_of_mlink',
        'no_of_mstore',
        'created_at',
        'updated_at',
        'no_of_malink',
        'user_ip',
        'sub_type',
        'status',
        'address',
        'service_type', // NEW FIELD ADDED
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // NEW METHODS TO CHECK SERVICE ACCESS
    /**
     * Check if user has access to WhatsApp services
     */
    public function hasWhatsAppAccess()
    {
        return in_array($this->service_type, ['whatsapp', 'all']);
    }

    /**
     * Check if user has access to SMS services
     */
    public function hasSmsAccess()
    {
        return in_array($this->service_type, ['sms', 'all']);
    }

    /**
     * Check if user has access to all services
     */
    public function hasAllServicesAccess()
    {
        return $this->service_type === 'all';
    }

    // EXISTING RELATIONSHIPS
    public function multiLink(): HasMany
    {
        return $this->hasMany(Link::class)->where('type', 'tiered');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(VendorWallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function link(): HasMany
    {
        return $this->hasMany(Link::class)->where('type', 'message')->orWhere('type', 'catalog');
    }

    public function redirectLinks(): HasMany
    {
        return $this->hasMany(Link::class)->where('type', 'url');
    }

    /**
     * Get the SMS wallet associated with the user.
     */
    public function sms_wallet()
    {
        return $this->hasOne(SmsWallet::class);
    }

    /**
     * Get the contacts for the user.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the contact groups for the user.
     */
    public function contactGroups()
    {
        return $this->hasMany(ContactGroup::class);
    }

    /**
     * Get the messages sent by the user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the sender IDs for the user.
     */
    public function senderIds()
    {
        return $this->hasMany(SenderId::class);
    }

    /**
     * Get the SMS transactions for the user.
     */
    public function sms_transactions()
    {
        return $this->hasMany(SmsTransaction::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }
}
