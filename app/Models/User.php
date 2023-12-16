<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function multiLink(): HasMany  
    {
        return $this->hasMany(Link::class)->where('type','tiered');
    }

    public function link(): HasMany
    {
        return $this->hasMany(Link::class)->where('type','message')->orWhere('type','catalog');
    }

    public function redirectLinks(): HasMany
    {
        return $this->hasMany(Link::class)->where('type','url');
    }
}
