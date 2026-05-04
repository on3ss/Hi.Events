<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\Exceptions\Razorpay\RazorpayAccountSetupException;
use HiEvents\Exceptions\SaasModeEnabledException;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Repository\Interfaces\AccountRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountResponse;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayApiClient;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Error;
use Throwable;

class CreateRazorpayLinkedAccountHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
        private readonly DatabaseManager $databaseManager,
        private readonly LoggerInterface $logger,
        private readonly Repository $config,
        private readonly RazorpayClientFactory $razorpayClientFactory,
    ) {
    }

    public function handle(CreateRazorpayLinkedAccountDTO $command): CreateRazorpayLinkedAccountResponse
    {
        if (!$this->config->get('app.saas_mode_enabled')) {
            throw new SaasModeEnabledException(
                __('This feature is only available in SaaS mode.')
            );
        }

        // STEP 1: Local record (no external calls)
        $localAccount = $this->databaseManager->transaction(function () use ($command) {

            $account = $this->accountRepository->findById($command->accountId);

            if (!$this->isEligible($account)) {
                abort(403, __('This account is not eligible for onboarding.'));
            }

            $existing = $this->accountRazorpayPlatformRepository->findByAccountId($command->accountId);

            if ($existing) {
                return $existing;
            }

            return $this->accountRazorpayPlatformRepository->create([
                'account_id' => $command->accountId,
                'status' => 'initiated',
                // 'email' => $command->email,
                // 'phone' => $command->phone,
                // 'external_ref' => hash('sha256', $command->accountId),
            ]);
        });

        if ($localAccount->getStatus() === 'active') {
            return $this->buildResponse($localAccount, true);
        }

        $client = $this->razorpayClientFactory->create();

        try {

            // STEP 2: Ensure account_id
            $razorpayAccountId = $localAccount->getRazorpayAccountId()
                ?: $this->createRemoteAccount($client, $command, $localAccount);

            // STEP 3: Continue setup (safe / idempotent)
            $this->upsertStakeholderSafe($client, $razorpayAccountId, $command);
            $productId = $this->configureProductSafe($client, $razorpayAccountId, $command);

            $productConfig = $client->fetchProductConfiguration($razorpayAccountId, $productId);

            $isActive = $productConfig->activation_status === 'activated';

            $this->accountRazorpayPlatformRepository->updateWhere(
                ['id' => $localAccount->getId()],
                [
                    'status' => $isActive ? 'active' : 'pending_activation',
                    'activated_at' => $isActive ? now()->toDateTimeString() : null,
                ]
            );

            return $this->buildResponse($localAccount, $isActive);

        } catch (Throwable $e) {

            // 🔥 Centralized failure handling
            $this->failLocal($localAccount, $e);

            throw new RazorpayAccountSetupException(
                $this->mapToUserMessage($e),
                previous: $e
            );
        }
    }

    private function createRemoteAccount(
        RazorpayApiClient $client,
        CreateRazorpayLinkedAccountDTO $command,
        object $localAccount
    ): string {
        $this->accountRazorpayPlatformRepository->updateById(
            $localAccount->getId(),
            ['status' => 'creating_remote']
        );

        try {
            $account = $client->createLinkedAccount([
                'email' => $command->email ?? '',
                'phone' => $command->phone ?? '',
                'type' => 'route',
                'legal_business_name' => $command->legalBusinessName ?? '',
                'business_type' => $command->businessType ?? 'partnership',
                'contact_name' => $command->contactName ?? '',
                'profile' => [
                    'category' => $command->profileCategory ?? 'healthcare',
                    'subcategory' => $command->profileSubcategory ?? 'clinic',
                    'addresses' => [
                        'registered' => [
                            'street1' => $command->registeredAddress?->street1 ?? '',
                            'street2' => $command->registeredAddress?->street2 ?? '',
                            'city' => $command->registeredAddress?->city ?? '',
                            'state' => $command->registeredAddress?->state ?? '',
                            'postal_code' => $command->registeredAddress?->postalCode ?? '',
                            'country' => $command->registeredAddress?->country ?? 'IN',
                        ],
                    ],
                ],
            ]);

            $this->accountRazorpayPlatformRepository->updateById(
                $localAccount->getId(),
                [
                    'razorpay_account_id' => $account->id,
                    'razorpay_account_details' => json_encode($account->toArray()),
                    'status' => 'created',
                ]
            );

            return $account->id;

        } catch (BadRequestError $e) {

            // Duplicate case → mark orphan, don't expose internals
            if ($this->isDuplicateEmailError($e)) {

                $this->accountRazorpayPlatformRepository->updateById(
                    $localAccount->getId(),
                    [
                        'status' => 'orphaned_remote',
                        'error' => $e->getMessage(),
                    ]
                );

                $this->logger->error('Duplicate Razorpay account without recoverable ID', [
                    'email' => $command->email,
                    'account_id' => $command->accountId,
                    'razorpay_error' => $e->getMessage(),
                ]);

                throw new RazorpayAccountSetupException(
                    __('This account is already registered. Please contact support.')
                );
            }

            throw $e;
        }
    }

    private function failLocal(object $localAccount, Throwable $e): void
    {
        $this->accountRazorpayPlatformRepository->updateById(
            $localAccount->getId(),
            [
                'status' => 'failed',
                'error' => json_encode([
                    'raw' => $e->getMessage(),
                    'type' => get_class($e),
                ]),
            ]
        );

        $this->logger->error('Razorpay onboarding failed', [
            'account_id' => $localAccount->getId(),
            'error' => $e->getMessage(),
        ]);
    }

    private function mapToUserMessage(Throwable $e): string
    {
        if ($e instanceof RazorpayAccountSetupException) {
            return $e->getMessage();
        }

        $msg = $e->getMessage();

        if (str_contains($msg, 'addresses')) {
            return __('Please provide a valid business address.');
        }

        if (str_contains($msg, 'email')) {
            return __('Please provide a valid email address.');
        }

        if (str_contains($msg, 'phone')) {
            return __('Please provide a valid phone number.');
        }

        if ($this->isDuplicateEmailError($e)) {
            return __('This account is already registered. Please contact support.');
        }

        return __('We could not complete the onboarding. Please try again.');
    }

    private function upsertStakeholderSafe($client, string $accountId, $command): void
    {
        if (!$command->stakeholder)
            return;

        try {
            $this->upsertStakeholder($client, $accountId, $command->stakeholder);
        } catch (Throwable $e) {
            $this->logger->warning('Stakeholder step failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function configureProductSafe($client, string $accountId, $command): string
    {
        return $this->configureProduct($client, $accountId, $command);
    }

    private function upsertStakeholder(RazorpayApiClient $client, string $razorpayAccountId, object $stakeholderData): void
    {
        try {
            $client->createStakeholder($razorpayAccountId, [
                'name' => $stakeholderData->name,
                'email' => $stakeholderData->email,
                'addresses' => [
                    'residential' => [
                        'street' => $stakeholderData->residentialAddress->street ?? '',
                        'city' => $stakeholderData->residentialAddress->city ?? '',
                        'state' => $stakeholderData->residentialAddress->state ?? '',
                        'postal_code' => $stakeholderData->residentialAddress->postalCode ?? '',
                        'country' => $stakeholderData->residentialAddress->country ?? 'IN',
                    ],
                ],
                'kyc' => [
                    'pan' => $stakeholderData->pan ?? '',
                ],
                'notes' => $stakeholderData->notes ?? [],
            ]);
            $this->logger->info('Stakeholder created', ['razorpay_account_id' => $razorpayAccountId]);
        } catch (BadRequestError $e) {
            // Handle duplicate stakeholder gracefully
            if ($this->isStakeholderDuplicateError($e)) {
                $this->logger->warning('Stakeholder already exists, skipping creation', [
                    'razorpay_account_id' => $razorpayAccountId,
                    'error' => $e->getMessage(),
                ]);
            } else {
                throw RazorpayAccountSetupException::fromRazorpayError($e, 'creating stakeholder');
            }
        } catch (Error $e) {
            throw RazorpayAccountSetupException::fromRazorpayError($e, 'creating stakeholder');
        }
    }

    private function configureProduct(RazorpayApiClient $client, string $razorpayAccountId, CreateRazorpayLinkedAccountDTO $command): string
    {
        // 1. Request product configuration – returns the product object containing the real id
        $product = $client->requestProductConfiguration($razorpayAccountId, [
            'product_name' => 'route',
            'tnc_accepted' => true,
        ]);
        $productId = $product->id;

        $this->logger->info('Product configuration requested', [
            'razorpay_account_id' => $razorpayAccountId,
            'product_id' => $productId,
        ]);

        // 2. Update with settlement details (if provided) – use the actual product id
        if ($command->settlement) {
            $client->updateProductConfiguration($razorpayAccountId, $productId, [
                'settlements' => [
                    'account_number' => $command->settlement->accountNumber,
                    'ifsc_code' => $command->settlement->ifscCode,
                    'beneficiary_name' => $command->settlement->beneficiaryName,
                ],
                'tnc_accepted' => true,
            ]);

            $this->logger->info('Product configuration updated with settlement', [
                'razorpay_account_id' => $razorpayAccountId,
                'product_id' => $productId,
            ]);
        }

        return $productId;
    }

    private function buildResponse(object $localAccount, bool $completed): CreateRazorpayLinkedAccountResponse
    {
        return new CreateRazorpayLinkedAccountResponse(
            linkedAccountId: $localAccount->getId(),
            razorpayAccountId: $localAccount->getRazorpayAccountId(),
            setupCompleted: $completed,
        );
    }

    private function isEligible(AccountDomainObject $account): bool
    {
        return $account->getCurrencyCode() === 'INR';
    }

    private function isStakeholderDuplicateError(BadRequestError $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'already exists') || str_contains($message, 'duplicate');
    }

    private function isDuplicateEmailError(Throwable $e): bool
    {
        return $e instanceof RazorpayAccountSetupException;
    }
}