<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class UpdateRazorpayBusinessStageDTO extends BaseDTO
{
    public function __construct(
        public readonly int $accountId,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $legalBusinessName,
        public readonly string $businessType,
        public readonly string $contactName,
        public readonly RegisteredAddressDTO $registeredAddress,
        public readonly string $pan,
        public readonly ?string $gst = null,
        public readonly ?string $profileCategory = null,
        public readonly ?string $profileSubcategory = null,
    ) {
    }
}
