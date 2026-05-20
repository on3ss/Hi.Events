<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\DTOs;

use HiEvents\DataTransferObjects\BaseDataObject;

class RazorpayTransferPayload extends BaseDataObject
{
    public function __construct(
        public readonly RazorpayTransferDTO $transfer,
    ) {
    }
}