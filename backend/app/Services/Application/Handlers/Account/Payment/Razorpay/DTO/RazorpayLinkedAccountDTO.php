<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

class RazorpayLinkedAccountDTO
{
    public function __construct(
        public readonly string $id,
        public readonly bool $isSetupComplete,
        public readonly ?string $connectUrl = null,
        public readonly string $country = 'IN',
    ) {}
}