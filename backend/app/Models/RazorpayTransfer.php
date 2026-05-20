<?php

namespace HiEvents\Models;

class RazorpayTransfer extends BaseModel
{
    protected $fillable = [
        'order_id',
        'razorpay_transfer_id',
        'razorpay_payment_id',
        'linked_account_id',
        'amount',
        'currency',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}