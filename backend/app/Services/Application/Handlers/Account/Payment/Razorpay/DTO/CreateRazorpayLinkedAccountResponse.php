<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class CreateRazorpayLinkedAccountResponse extends BaseDTO
{
    public function __construct(
        public readonly int $linkedAccountId,
        public readonly string $razorpayAccountId,
        public readonly bool $setupCompleted,
    ) {
    }
}