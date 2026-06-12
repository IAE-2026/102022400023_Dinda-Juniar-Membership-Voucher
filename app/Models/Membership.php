<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    protected $fillable = [
        'member_code',
        'name',
        'email',
        'phone',
        'membership_type',
        'status',
        'receipt_number',
        'discount_percent',
        'registered_at',
        'expired_at',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
        'registered_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function usageHistory(): HasMany
    {
        return $this->hasMany(MembershipUsage::class);
    }
}
