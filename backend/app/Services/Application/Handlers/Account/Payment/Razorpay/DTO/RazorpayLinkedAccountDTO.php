<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;


class RazorpayLinkedAccountDTO extends BaseDataObject
{
    public function __construct(
        public readonly ?string         $razorpayAccountId = null,
        public readonly ?string         $connectUrl = null,
        public readonly bool            $isSetupComplete = false,

        public readonly ?string         $accountType = null,
        public readonly bool            $isPrimary = false,
        public readonly ?string         $country = null,
    )
    {
    }
}
