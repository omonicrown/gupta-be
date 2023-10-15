<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Product extends Model
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
        'link_id',
        'product_name',
        'product_description',
        'phone_number',
        'no_of_items',
        'user_id',
        'product_price',
        'product_image_1',
        'product_image_2',
        'product_image_3',
        'product_image_id_1',
        'product_image_id_2',
        'product_image_id_3'
       
    ];

   
}
