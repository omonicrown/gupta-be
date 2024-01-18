<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount_paid',
        'user_email',
        'user_phone_number',
        'customer_email',
        'customer_phone_number',
        'customer_name',
        'paying_for',
        'transaction_status',
        'currency',
        'tnx_ref'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function user() : BelongsTo
    {
        return $this->BelongsTo(User::class);
    }
}
