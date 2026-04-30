<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

class SettlementDTO
{
    public function __construct(
        public readonly string $accountNumber,
        public readonly string $ifscCode,
        public readonly string $beneficiaryName,
    ) {
    }
}