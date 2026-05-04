<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class RazorpayLinkedAccountDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly ?string $razorpayAccountId,
        public readonly ?string $email,
        public readonly ?string $legalBusinessName,
        public readonly string $country = 'IN',
    ) {}
}