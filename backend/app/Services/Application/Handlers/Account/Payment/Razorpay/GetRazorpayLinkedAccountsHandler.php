<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\Repository\Interfaces\AccountRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\GetRazorpayLinkedAccountsResponseDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\RazorpayLinkedAccountDTO;
use HiEvents\Services\Domain\Payment\Razorpay\RazorpayAccountSyncService;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Throwable;

class GetRazorpayLinkedAccountsHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly RazorpayClientFactory      $razorpayClientFactory,
        private readonly RazorpayAccountSyncService $razorpayAccountSyncService,
        private readonly LoggerInterface            $logger,
    )
    {
    }

    public function handle(int $accountId): GetRazorpayLinkedAccountsResponseDTO
    {
        $account = $this->accountRepository
            ->loadRelation(AccountRazorpayPlatformDomainObject::class)
            ->findById($accountId);

        $razorpayLinkedAccounts = $this->getRazorpayLinkedAccounts($account);
        $primaryRazorpayAccountId = $account->getActiveRazorpayAccountId();
        $hasCompletedSetup = $account->isRazorpaySetupComplete();

        return new GetRazorpayLinkedAccountsResponseDTO(
            account: $account,
            razorpayLinkedAccounts: $razorpayLinkedAccounts,
            primaryRazorpayAccountId: $primaryRazorpayAccountId,
            hasCompletedSetup: $hasCompletedSetup,
        );
    }

    private function getRazorpayLinkedAccounts(AccountDomainObject $account): Collection
    {
        $razorpayAccounts = collect();
        $razorpayPlatforms = $account->getAccountRazorpayPlatforms();

        if (!$razorpayPlatforms || $razorpayPlatforms->isEmpty()) {
            return $razorpayAccounts;
        }

        foreach ($razorpayPlatforms as $razorpayPlatform) {
            $razorpayAccount = $this->getRazorpayAccount($razorpayPlatform);
            if ($razorpayAccount) {
                $razorpayAccounts->push($razorpayAccount);
            }
        }

        return $razorpayAccounts;
    }

    private function getRazorpayAccount(AccountRazorpayPlatformDomainObject $razorpayPlatform): ?RazorpayLinkedAccountDTO
    {
        if (!$razorpayPlatform->getRazorpayAccountId()) {
            return null;
        }

        try {
            $razorpayClient = $this->razorpayClientFactory->create();
            $razorpayAccount = $razorpayClient->fetchLinkedAccount($razorpayPlatform->getRazorpayAccountId());

            $isSetupComplete = true; // Assuming created accounts are complete
            $connectUrl = null;

            if ($isSetupComplete && $razorpayPlatform->getRazorpaySetupCompletedAt() === null) {
                $this->razorpayAccountSyncService->markAccountAsComplete($razorpayPlatform, $razorpayAccount);
            }

            return new RazorpayLinkedAccountDTO(
                razorpayAccountId: $razorpayAccount->id,
                connectUrl: $connectUrl,
                isSetupComplete: $isSetupComplete,
                accountType: 'route',
                isPrimary: $razorpayPlatform->getRazorpaySetupCompletedAt() !== null,
            );
        } catch (Throwable $e) {
            $this->logger->error('Failed to retrieve Razorpay account', [
                'razorpay_account_id' => $razorpayPlatform->getRazorpayAccountId(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}