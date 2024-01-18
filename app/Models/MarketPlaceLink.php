<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class MarketPlaceLink extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

   
    protected $fillable = [
        'link_name',
        'user_id',
        'brand_primary_color',
        'brand_description',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'brand_logo',
        'brand_logo_id'


       
    ];

   
}
