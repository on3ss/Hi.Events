<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class StakeholderDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ResidentialAddressDTO $residentialAddress,
        public readonly ?string $pan = null,
        public readonly ?array $notes = null,
    ) {
    }
}