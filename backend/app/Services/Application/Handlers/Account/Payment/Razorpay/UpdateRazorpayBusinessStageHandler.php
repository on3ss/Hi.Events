<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\Exceptions\Razorpay\RazorpayAccountSetupException;
use HiEvents\Exceptions\SaasModeEnabledException;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Repository\Interfaces\AccountRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\UpdateRazorpayBusinessStageDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountResponse;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayApiClient;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Razorpay\Api\Errors\BadRequestError;
use Throwable;

class UpdateRazorpayBusinessStageHandler
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

    public function handle(UpdateRazorpayBusinessStageDTO $command): CreateRazorpayLinkedAccountResponse
    {
        if (!$this->config->get('app.saas_mode_enabled')) {
            throw new SaasModeEnabledException(
                __('This feature is only available in SaaS mode.')
            );
        }

        $localAccount = $this->databaseManager->transaction(function () use ($command) {
            $account = $this->accountRepository->findById($command->accountId);

            if (!$this->isEligible($account)) {
                abort(403, __('This account is not eligible for onboarding.'));
            }

            $existing = $this->accountRazorpayPlatformRepository->findByAccountId($command->accountId);

            $onboardingData = $existing ? ($existing->getOnboardingData() ?? []) : [];
            if (is_string($onboardingData)) {
                $onboardingData = json_decode($onboardingData, true) ?: [];
            }

            $onboardingData['business'] = $command->toArray();

            if ($existing) {
                $this->accountRazorpayPlatformRepository->updateById(
                    $existing->getId(),
                    ['onboarding_data' => json_encode($onboardingData)]
                );
                return $this->accountRazorpayPlatformRepository->findById($existing->getId());
            }

            return $this->accountRazorpayPlatformRepository->create([
                'account_id' => $command->accountId,
                'status' => 'initiated',
                'onboarding_data' => json_encode($onboardingData),
            ]);
        });

        if ($localAccount->getStatus() === 'active') {
            return $this->buildResponse($localAccount, true);
        }

        $client = $this->razorpayClientFactory->create();

        try {
            $razorpayAccountId = $localAccount->getRazorpayAccountId();

            if (!$razorpayAccountId) {
                $razorpayAccountId = $this->createRemoteAccount($client, $command, $localAccount);
            } else {
                $this->updateRemoteAccount($client, $razorpayAccountId, $command);
            }

            return $this->buildResponse($localAccount, false, $razorpayAccountId);
        } catch (Throwable $e) {
            $this->failLocal($localAccount, $e);

            throw new RazorpayAccountSetupException(
                $this->mapToUserMessage($e),
                previous: $e
            );
        }
    }

    private function updateRemoteAccount(RazorpayApiClient $client, string $razorpayAccountId, UpdateRazorpayBusinessStageDTO $command): void
    {
        $client->updateLinkedAccount($razorpayAccountId, [
            'email' => $command->email ?? '',
            'phone' => $command->phone ?? '',
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
    }

    private function createRemoteAccount(
        RazorpayApiClient $client,
        UpdateRazorpayBusinessStageDTO $command,
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

        $this->logger->error('Razorpay onboarding business stage failed', [
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

    private function buildResponse(object $localAccount, bool $completed, ?string $razorpayAccountId = null): CreateRazorpayLinkedAccountResponse
    {
        return new CreateRazorpayLinkedAccountResponse(
            linkedAccountId: $localAccount->getId(),
            razorpayAccountId: $razorpayAccountId ?: ($localAccount->getRazorpayAccountId() ?? ''),
            setupCompleted: $completed,
        );
    }

    private function isEligible(AccountDomainObject $account): bool
    {
        return $account->getCurrencyCode() === 'INR';
    }

    private function isDuplicateEmailError(Throwable $e): bool
    {
        return $e instanceof RazorpayAccountSetupException;
    }
}
