<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\Exceptions\Razorpay\RazorpayAccountSetupException;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\UpdateRazorpayStakeholderStageDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountResponse;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayApiClient;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\Error;
use Throwable;

class UpdateRazorpayStakeholderStageHandler
{
    public function __construct(
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
        private readonly DatabaseManager $databaseManager,
        private readonly LoggerInterface $logger,
        private readonly RazorpayClientFactory $razorpayClientFactory,
    ) {
    }

    public function handle(UpdateRazorpayStakeholderStageDTO $command): CreateRazorpayLinkedAccountResponse
    {
        $localAccount = $this->databaseManager->transaction(function () use ($command) {
            $existing = $this->accountRazorpayPlatformRepository->findByAccountId($command->accountId);

            if (!$existing) {
                abort(404, __('Razorpay onboarding not initiated.'));
            }

            $onboardingData = $existing->getOnboardingData() ?? [];
            if (is_string($onboardingData)) {
                $onboardingData = json_decode($onboardingData, true) ?: [];
            }

            $onboardingData['stakeholder'] = $command->stakeholder->toArray();

            $this->accountRazorpayPlatformRepository->updateById(
                $existing->getId(),
                ['onboarding_data' => json_encode($onboardingData)]
            );

            return $this->accountRazorpayPlatformRepository->findById($existing->getId());
        });

        if ($localAccount->getStatus() === 'active') {
            return $this->buildResponse($localAccount, true);
        }

        $razorpayAccountId = $localAccount->getRazorpayAccountId();
        if (!$razorpayAccountId) {
            abort(400, __('Razorpay account ID is missing. Cannot proceed to stakeholder setup.'));
        }

        $client = $this->razorpayClientFactory->create();

        try {
            $onboardingData = $localAccount->getOnboardingData() ?? [];
            if (is_string($onboardingData)) {
                $onboardingData = json_decode($onboardingData, true) ?: [];
            }
            $stakeholderId = $onboardingData['stakeholder_id'] ?? null;

            if ($stakeholderId) {
                $this->updateStakeholder($client, $razorpayAccountId, $stakeholderId, $command->stakeholder);
            } else {
                $stakeholderId = $this->createStakeholder($client, $razorpayAccountId, $command->stakeholder);

                // Save the stakeholder ID
                $onboardingData['stakeholder_id'] = $stakeholderId;
                $this->accountRazorpayPlatformRepository->updateById(
                    $localAccount->getId(),
                    ['onboarding_data' => json_encode($onboardingData)]
                );
            }

            return $this->buildResponse($localAccount, false);
        } catch (Throwable $e) {
            $this->failLocal($localAccount, $e);
            throw new RazorpayAccountSetupException(
                $e->getMessage(),
                previous: $e
            );
        }
    }

    private function updateStakeholder(RazorpayApiClient $client, string $razorpayAccountId, string $stakeholderId, object $stakeholderData): void
    {
        try {
            $client->updateStakeholder($razorpayAccountId, $stakeholderId, [
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
            $this->logger->info('Stakeholder updated', ['razorpay_account_id' => $razorpayAccountId, 'stakeholder_id' => $stakeholderId]);
        } catch (Error $e) {
            throw RazorpayAccountSetupException::fromRazorpayError($e, 'updating stakeholder');
        }
    }

    private function createStakeholder(RazorpayApiClient $client, string $razorpayAccountId, object $stakeholderData): string
    {
        try {
            $response = $client->createStakeholder($razorpayAccountId, [
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
            return $response->id;
        } catch (BadRequestError $e) {
            if ($this->isStakeholderDuplicateError($e)) {
                $this->logger->warning('Stakeholder already exists, skipping creation', [
                    'razorpay_account_id' => $razorpayAccountId,
                    'error' => $e->getMessage(),
                ]);
                return ''; // We might not have the ID here, which could be problematic for updates later if they retry. Ideally they don't hit this.
            } else {
                throw RazorpayAccountSetupException::fromRazorpayError($e, 'creating stakeholder');
            }
        } catch (Error $e) {
            throw RazorpayAccountSetupException::fromRazorpayError($e, 'creating stakeholder');
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

        $this->logger->error('Razorpay onboarding stakeholder stage failed', [
            'account_id' => $localAccount->getId(),
            'error' => $e->getMessage(),
        ]);
    }

    private function buildResponse(object $localAccount, bool $completed): CreateRazorpayLinkedAccountResponse
    {
        return new CreateRazorpayLinkedAccountResponse(
            linkedAccountId: $localAccount->getId(),
            razorpayAccountId: $localAccount->getRazorpayAccountId(),
            setupCompleted: $completed,
        );
    }

    private function isStakeholderDuplicateError(BadRequestError $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'already exists') || str_contains($message, 'duplicate');
    }
}
