<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;
use HiEvents\DomainObjects\AccountDomainObject;
use Illuminate\Support\Collection;

class GetRazorpayLinkedAccountsResponseDTO extends BaseDTO
{
    public function __construct(
        public readonly AccountDomainObject $account,
        public readonly Collection $razorpayAccounts,
        public readonly ?string $primaryRazorpayAccountId = null,
        public readonly bool $hasCompletedSetup = false,
    ) {}
}