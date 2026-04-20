<?php

namespace HiEvents\Services\Domain\Payment\Razorpay;

use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\DomainObjects\Generated\AccountRazorpayPlatformDomainObjectAbstract;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;

class RazorpayAccountSyncService
{
    public function __construct(
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
    ) {
    }

    public function markAccountAsComplete(
        AccountRazorpayPlatformDomainObject $accountRazorpayPlatform,
        object $razorpayAccount
    ): void {
        $this->accountRazorpayPlatformRepository->updateWhere(
            attributes: [
                AccountRazorpayPlatformDomainObjectAbstract::RAZORPAY_SETUP_COMPLETED_AT => now(),
                AccountRazorpayPlatformDomainObjectAbstract::RAZORPAY_ACCOUNT_DETAILS => json_encode($razorpayAccount),
            ],
            where: [
                'id' => $accountRazorpayPlatform->getId(),
            ]
        );
    }
}
