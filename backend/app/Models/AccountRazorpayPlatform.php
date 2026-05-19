<?php

namespace HiEvents\Models;

class AccountRazorpayPlatform extends BaseModel
{
    protected $fillable = [
        'account_id',
        'razorpay_account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
