<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;


class CreateRazorpayLinkedAccountDTO extends BaseDataObject
{
    public function __construct(
        public readonly int                 $accountId,
        public readonly string              $phone,
        public readonly string              $contactName,
        public readonly string              $companyName,
        public readonly string              $businessType,
        public readonly string              $category,
        public readonly string              $subcategory,
        public readonly string              $pan,
        public readonly ?string             $gst,
        public readonly string              $street1,
        public readonly string              $city,
        public readonly string              $state,
        public readonly string              $postalCode,
    )
    {
    }
}
