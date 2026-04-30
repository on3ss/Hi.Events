<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountRazorpayPlatformDomainObject;
use HiEvents\Exceptions\Razorpay\RazorpayAccountSetupException;
use HiEvents\Exceptions\SaasModeEnabledException;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
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
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
        private readonly DatabaseManager $databaseManager,
        private readonly LoggerInterface $logger,
        private readonly Repository $config,
        private readonly RazorpayClientFactory $razorpayClientFactory,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(CreateRazorpayLinkedAccountDTO $command): CreateRazorpayLinkedAccountResponse
    {
        if (!$this->config->get('app.saas_mode_enabled')) {
            throw new SaasModeEnabledException(
                __('Razorpay Linked Account creation is only available in Saas Mode.')
            );
        }

        // 1. Create local placeholder record in its own transaction (prevents duplicates)
        $localAccount = $this->databaseManager->transaction(function () use ($command) {
            $existing = $this->accountRazorpayPlatformRepository->findByAccountId($command->accountId);
            if (!$this->isEligible($existing)) {
                abort(403, __('Account is not eligible for Razorpay onboarding.'));
            }
            if ($existing) {
                // If already active, just return success
                if ($existing->getStatus() === 'active') {
                    return $existing;
                }
                // Otherwise, we can reuse the existing record and attempt to complete setup
                return $existing;
            }

            $domainObject = new AccountRazorpayPlatformDomainObject();
            $domainObject->setAccountId($command->accountId);
            $domainObject->setStatus('created');
            return $this->accountRazorpayPlatformRepository->create($domainObject->toArray());
        });

        // If already active, no further action needed
        if ($localAccount->getStatus() === 'active') {
            return new CreateRazorpayLinkedAccountResponse(
                linkedAccountId: $localAccount->getId(),
                razorpayAccountId: $localAccount->getRazorpayAccountId(),
                setupCompleted: true,
            );
        }

        $client = $this->razorpayClientFactory->create();

        try {
            // 2. Create (or fetch) the remote Razorpay account
            $razorpayAccountId = $localAccount->getRazorpayAccountId();
            if (!$razorpayAccountId) {
                // create new Razorpay linked account
                $razorpayAccount = $client->createLinkedAccount([
                    'email' => $command->email ?? '',
                    'phone' => $command->phone ?? '',
                    'type' => 'route',
                    'reference_id' => (string) $command->accountId,
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
                    'legal_info' => [
                        'pan' => $command->pan ?? '',
                        'gst' => $command->gst ?? '',
                    ],
                ]);

                $razorpayAccountId = $razorpayAccount->id;
                $localAccount->setRazorpayAccountId($razorpayAccountId);
                $localAccount->setRazorpayAccountDetails(json_encode($razorpayAccount->toArray()));
                $this->accountRazorpayPlatformRepository->updateWhere(
                    ['id' => $localAccount->getId()],
                    $localAccount->toArray()
                );
            }

            // 3. Stakeholder management (if data provided)
            if ($command->stakeholder) {
                $this->upsertStakeholder($client, $razorpayAccountId, $command->stakeholder);
            }

            // 4. Product configuration & activation
            $this->configureProduct($client, $razorpayAccountId, $command);

            // 5. Verify product activation status before marking active
            $productConfig = $client->fetchProductConfiguration($razorpayAccountId, 'route');
            if ($productConfig->activation_status === 'activated') {
                $localAccount->setStatus('active');
                $localAccount->setActivatedAt(now()->toDateTimeString());
                $this->accountRazorpayPlatformRepository->updateWhere(
                    ['id' => $localAccount->getId()],
                    $localAccount->toArray()
                );
                $this->logger->info('Razorpay account fully activated', ['razorpay_account_id' => $razorpayAccountId]);
            } else {
                // Not yet activated, move to pending_activation
                $localAccount->setStatus('pending_activation');
                $this->accountRazorpayPlatformRepository->updateWhere(
                    ['id' => $localAccount->getId()],
                    ['status' => 'pending_activation']
                );
                $this->logger->warning('Product configuration not yet activated', [
                    'razorpay_account_id' => $razorpayAccountId,
                    'activation_status' => $productConfig->activation_status,
                ]);
            }

            return new CreateRazorpayLinkedAccountResponse(
                linkedAccountId: $localAccount->getId(),
                razorpayAccountId: $razorpayAccountId,
                setupCompleted: $localAccount->getStatus() === 'active',
            );
        } catch (Error $e) {
            // Mark local record as failed and throw
            $this->accountRazorpayPlatformRepository->updateWhere(
                ['id' => $localAccount->getId()],
                ['status' => 'failed']
            );
            $this->logger->error('Razorpay setup failed', [
                'account_id' => $command->accountId,
                'error' => $e->getMessage(),
            ]);
            throw RazorpayAccountSetupException::fromRazorpayError($e, 'linked account setup');
        }
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

    private function configureProduct(RazorpayApiClient $client, string $razorpayAccountId, CreateRazorpayLinkedAccountDTO $command): void
    {
        // request product configuration
        $client->requestProductConfiguration($razorpayAccountId, ['product_name' => 'route', 'tnc_accepted' => true]);

        // update settlement if provided
        if ($command->settlement) {
            $client->updateProductConfiguration($razorpayAccountId, 'route', [
                'settlements' => [
                    'account_number' => $command->settlement->accountNumber,
                    'ifsc_code' => $command->settlement->ifscCode,
                    'beneficiary_name' => $command->settlement->beneficiaryName,
                ],
                'tnc_accepted' => true,
            ]);
        }
    }

    private function isStakeholderDuplicateError(BadRequestError $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'already exists') || str_contains($message, 'duplicate');
    }

    private function isEligible($account): bool
    {
        return $account->country === 'IN'
            || optional($account->configuration)->supports_razorpay
            || $account->is_vendor;
    }
}