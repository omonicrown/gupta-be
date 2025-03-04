<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'key', 'name', 'expires_at', 'is_active'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate a new API key
    public static function generateFor($userId, $name, $expiresAt = null)
    {
        return self::create([
            'user_id' => $userId,
            'key' => Str::random(64), // 64 character random string
            'name' => $name,
            'expires_at' => $expiresAt,
        ]);
    }
}