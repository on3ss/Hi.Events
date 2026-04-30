<?php

namespace HiEvents\Models;

class AccountRazorpayPlatform extends BaseModel
{
    protected $fillable = [
        'account_id',
        'razorpay_account_id',
        'status',
        'razorpay_account_details',
        'activated_at',
    ];

    protected $casts = [
        'razorpay_account_details' => 'array',
        'activated_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}