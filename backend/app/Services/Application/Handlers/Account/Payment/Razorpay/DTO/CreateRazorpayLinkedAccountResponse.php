<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;
use HiEvents\DomainObjects\AccountDomainObject;

class CreateRazorpayLinkedAccountResponse extends BaseDTO
{
    public function __construct(
        public string              $razorpayLinkedAccountType,
        public string              $razorpayAccountId,
        public AccountDomainObject $account,
        public bool                $isConnectSetupComplete,
        public ?string             $connectUrl = null,
    )
    {
    }
}
