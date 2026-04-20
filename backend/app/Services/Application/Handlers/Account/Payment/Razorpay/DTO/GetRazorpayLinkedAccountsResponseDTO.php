<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;
use HiEvents\DomainObjects\AccountDomainObject;
use Illuminate\Support\Collection;

class GetRazorpayLinkedAccountsResponseDTO extends BaseDataObject
{
    public function __construct(
        public readonly AccountDomainObject $account,
        public readonly Collection $razorpayLinkedAccounts,
        public readonly ?string $primaryRazorpayAccountId = null,
        public readonly bool $hasCompletedSetup = false,
    ) {
    }
}