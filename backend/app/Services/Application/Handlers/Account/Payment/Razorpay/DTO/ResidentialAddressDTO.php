<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class ResidentialAddressDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $country = 'IN',
    ) {
    }
}