<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\DTOs;

use HiEvents\DataTransferObjects\BaseDataObject;

class RazorpayAccountWebhookPayload extends BaseDataObject
{
    public function __construct(
        public readonly RazorpayAccountWebhookDTO $account,
    ) {}
}
