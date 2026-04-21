<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;


class CreateRazorpayLinkedAccountDTO extends BaseDataObject
{
    public function __construct(
        public readonly int                 $accountId,
    )
    {
    }
}
