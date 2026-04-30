<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class CreateRazorpayLinkedAccountDTO extends BaseDTO
{
    public function __construct(
        public readonly int $accountId,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $legalBusinessName = null,
        public readonly ?string $businessType = null,
        public readonly ?string $contactName = null,
        public readonly ?string $profileCategory = null,
        public readonly ?string $profileSubcategory = null,
        public readonly ?RegisteredAddressDTO $registeredAddress = null,
        public readonly ?string $pan = null,
        public readonly ?string $gst = null,
        public readonly ?StakeholderDTO $stakeholder = null,
        public readonly ?SettlementDTO $settlement = null,
    ) {
    }
}