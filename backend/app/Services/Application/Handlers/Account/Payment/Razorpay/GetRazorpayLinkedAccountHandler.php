<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Repository\Interfaces\AccountRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\GetRazorpayLinkedAccountsResponseDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\RazorpayLinkedAccountDTO;

class GetRazorpayLinkedAccountHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccountRazorpayPlatformRepositoryInterface $razorpayPlatformRepo
    ) {}

    public function handle(int $accountId): GetRazorpayLinkedAccountsResponseDTO
    {
        $account = $this->accountRepository
            ->loadRelation(AccountRazorpayPlatformDomainObject::class)
            ->findById($accountId);

        $platforms = $account->getAccountRazorpayPlatforms();

        if (!$platforms || $platforms->isEmpty()) {
            return new GetRazorpayLinkedAccountsResponseDTO(
                account: $account,
                razorpayAccounts: collect(),
            );
        }

        $razorpayAccounts = $platforms->map(function (AccountRazorpayPlatformDomainObject $platform) {
            return new RazorpayLinkedAccountDTO(
                id: $platform->getRazorpayAccountId(),
                isSetupComplete: $platform->getStatus() === 'active',
                country: 'IN',
            );
        });

        $primaryId = $razorpayAccounts->first()?->id;
        $hasCompletedSetup = $razorpayAccounts->contains(fn($a) => $a->isSetupComplete);

        return new GetRazorpayLinkedAccountsResponseDTO(
            account: $account,
            razorpayAccounts: $razorpayAccounts,
            primaryRazorpayAccountId: $primaryId,
            hasCompletedSetup: $hasCompletedSetup,
        );
    }
}