<?php

namespace HiEvents\Models;

class RazorpayTransfer extends BaseModel
{
    protected $fillable = [
        'order_id',
        'razorpay_transfer_id',
        'razorpay_payment_id',
        'razorpay_order_id',
        'linked_account_id',
        'amount',
        'currency',
        'status',
        'raw_payload',
        'processed_at',
        'failed_at',
        'reversed_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}