<?php

namespace HiEvents\Models;

class AccountRazorpayPlatform extends BaseModel
{
    protected $casts = [
        'razorpay_account_details' => 'array',
        'razorpay_setup_completed_at' => 'datetime',
    ];
}
