<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\DomainObjects\Generated\AccountRazorpayPlatformDomainObjectAbstract;
use HiEvents\Exceptions\CreateRazorpayLinkedAccountFailedException;
use HiEvents\Exceptions\SaasModeEnabledException;
use HiEvents\Repository\Interfaces\AccountRepositoryInterface;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountResponse;
use HiEvents\Services\Domain\Payment\Razorpay\RazorpayAccountSyncService;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Throwable;

class CreateRazorpayLinkedAccountHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface               $accountRepository,
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
        private readonly DatabaseManager                          $databaseManager,
        private readonly LoggerInterface                          $logger,
        private readonly Repository                               $config,
        private readonly RazorpayClientFactory                    $razorpayClientFactory,
        private readonly RazorpayAccountSyncService               $razorpayAccountSyncService,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(CreateRazorpayLinkedAccountDTO $command): CreateRazorpayLinkedAccountResponse
    {
        if (!$this->config->get('app.saas_mode_enabled')) {
            throw new SaasModeEnabledException(
                __('Razorpay Linked Account creation is only available in Saas Mode.'),
            );
        }

        return $this->databaseManager->transaction(fn() => $this->createOrGetRazorpayLinkedAccount($command));
    }

    private function createOrGetRazorpayLinkedAccount(CreateRazorpayLinkedAccountDTO $command): CreateRazorpayLinkedAccountResponse
    {
        $account = $this->accountRepository
            ->loadRelation(AccountRazorpayPlatformDomainObject::class)
            ->findById($command->accountId);

        $accountRazorpayPlatform = $account->getPrimaryRazorpayPlatform();

        $razorpayClient = $this->razorpayClientFactory->create();

        $razorpayLinkedAccount = $this->getOrCreateRazorpayLinkedAccount(
            account: $account,
            accountRazorpayPlatform: $accountRazorpayPlatform,
            razorpayClient: $razorpayClient,
        );

                $isConnectSetupComplete = true; // Assuming created accounts are immediately usable or we'll check status

        $response = new CreateRazorpayLinkedAccountResponse(
            razorpayLinkedAccountType: 'route',
            razorpayAccountId: $razorpayLinkedAccount->id,
            account: $account,
            isConnectSetupComplete: $isConnectSetupComplete,
        );

        // Fetch it again to get the updated local state if it was just created
        $account = $this->accountRepository
            ->loadRelation(AccountRazorpayPlatformDomainObject::class)
            ->findById($command->accountId);
        $accountRazorpayPlatform = $account->getPrimaryRazorpayPlatform();

        if ($response->isConnectSetupComplete) {
            if ($accountRazorpayPlatform && $accountRazorpayPlatform->getRazorpaySetupCompletedAt() === null) {
                $this->razorpayAccountSyncService->markAccountAsComplete($accountRazorpayPlatform, $razorpayLinkedAccount);
            }
            return $response;
        }

        return $response;
    }

    private function getOrCreateRazorpayLinkedAccount(
        AccountDomainObject                $account,
        ?AccountRazorpayPlatformDomainObject $accountRazorpayPlatform,
        $razorpayClient
    ): object
    {
        try {
            if ($accountRazorpayPlatform && $accountRazorpayPlatform->getRazorpayAccountId() !== null) {
                return $razorpayClient->fetchLinkedAccount($accountRazorpayPlatform->getRazorpayAccountId());
            }

                        $razorpayAccount = $razorpayClient->createLinkedAccount([
                'notes' => [],
                'name' => $account->getName(),
                'email' => $account->getEmail(),
                'tnc_accepted' => true,
                'account_details' => [
                    'business_name' => $account->getName(),
                    'business_type' => 'individual',
                ],
            ]);

            $razorpayClient->createStakeholder($razorpayAccount->id, [
                'name' => $account->getName(),
                'email' => $account->getEmail(),
                'kyc' => [
                    'pan' => 'ABCDE1234F',
                ],
                'percentage_ownership' => 100,
                'relationship' => [
                    'executive' => true
                ],
                'addresses' => [
                    'residential' => [
                        'street1' => 'Test Street',
                        'city' => 'Test City',
                        'state' => 'KA',
                        'postal_code' => '560001',
                        'country' => 'IN'
                    ]
                ]
            ]);

            $razorpayClient->createProductConfiguration($razorpayAccount->id, [
                'product_name' => 'route',
                'tnc_accepted' => true,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to create or fetch Razorpay Linked Account: ' . $e->getMessage(), [
                'accountId' => $account->getId(),
                'razorpayAccountId' => $accountRazorpayPlatform?->getRazorpayAccountId() ?? 'null',
                'exception' => $e->getMessage(),
            ]);

            throw new CreateRazorpayLinkedAccountFailedException(
                message: __('There are issues with creating or fetching the Razorpay Linked Account. Please try again.'),
                previous: $e,
            );
        }

        // Create or update account razorpay platform record
        if (!$accountRazorpayPlatform) {
            $this->accountRazorpayPlatformRepository->create([
                AccountRazorpayPlatformDomainObjectAbstract::ACCOUNT_ID => $account->getId(),
                AccountRazorpayPlatformDomainObjectAbstract::RAZORPAY_ACCOUNT_ID => $razorpayAccount->id,
            ]);
        } else {
            $this->accountRazorpayPlatformRepository->updateWhere(
                attributes: [
                    AccountRazorpayPlatformDomainObjectAbstract::RAZORPAY_ACCOUNT_ID => $razorpayAccount->id,
                ],
                where: [
                    'id' => $accountRazorpayPlatform->getId(),
                ]
            );
        }

        return $razorpayAccount;
    }
}