<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Link extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

   
    protected $fillable = [
        'name',
        'title',
        'bio',
        'logo',
        'business_website',
        'business_policy',
        'redirect_link',
        'type',
        'logo_id',
        'user_id',
        'short_url_id',
        'created_at',
    ];

    protected $hidden = [
       
        'updated_at',
        'deleted_at'
    ];

    public function linkInfo(): HasOne
    {
        return $this->hasOne(LinkInfo::class);
    }

    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(Short::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
