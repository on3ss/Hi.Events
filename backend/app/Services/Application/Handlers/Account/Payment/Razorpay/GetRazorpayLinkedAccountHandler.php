<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
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

        if (!$this->isEligible($account)) {
            abort(403, __('Account is not eligible for Razorpay.'));
        }

        $platforms = $account->getAccountRazorpayPlatforms();

        if (!$platforms || $platforms->isEmpty()) {
            return new GetRazorpayLinkedAccountsResponseDTO(
                account: $account,
                razorpayAccounts: collect(),
            );
        }

        $razorpayAccounts = $platforms->map(function (AccountRazorpayPlatformDomainObject $platform) use ($account){
            $onboardingData = $platform->getOnboardingData();
            if (is_string($onboardingData)) {
                $onboardingData = json_decode($onboardingData, true) ?: null;
            }

            return new RazorpayLinkedAccountDTO(
                id: $platform->getId(),
                status: $platform->getStatus(),
                razorpayAccountId: $platform->getRazorpayAccountId(),
                email: $account->getEmail(),
                legalBusinessName: $account->getName(),
                country: 'IN',
                onboardingData: $onboardingData,
            );
        });

        $primaryId = $razorpayAccounts->first()?->id;
        $hasCompletedSetup = $razorpayAccounts->contains(fn(RazorpayLinkedAccountDTO $a) => $a->razorpayAccountId);

        return new GetRazorpayLinkedAccountsResponseDTO(
            account: $account,
            razorpayAccounts: $razorpayAccounts,
            primaryRazorpayAccountId: $primaryId,
            hasCompletedSetup: $hasCompletedSetup,
        );
    }

    private function isEligible(AccountDomainObject $account): bool
    {
        return $account->getCurrencyCode() === 'INR'
            || optional($account->getConfiguration())->supports_razorpay;
    }
}