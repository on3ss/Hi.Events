<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay;

use HiEvents\Exceptions\Razorpay\RazorpayAccountSetupException;
use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\UpdateRazorpaySettlementStageDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountResponse;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayApiClient;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Throwable;

class UpdateRazorpaySettlementStageHandler
{
    public function __construct(
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
        private readonly DatabaseManager $databaseManager,
        private readonly LoggerInterface $logger,
        private readonly RazorpayClientFactory $razorpayClientFactory,
    ) {
    }

    public function handle(UpdateRazorpaySettlementStageDTO $command): CreateRazorpayLinkedAccountResponse
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

            $onboardingData['settlement'] = $command->settlement->toArray();

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
            abort(400, __('Razorpay account ID is missing. Cannot proceed to settlement setup.'));
        }

        $client = $this->razorpayClientFactory->create();

        try {
            $onboardingData = $localAccount->getOnboardingData() ?? [];
            if (is_string($onboardingData)) {
                $onboardingData = json_decode($onboardingData, true) ?: [];
            }
            $productId = $onboardingData['product_id'] ?? null;

            if ($productId) {
                $this->updateProduct($client, $razorpayAccountId, $productId, $command);
            } else {
                $productId = $this->configureProduct($client, $razorpayAccountId, $command);

                // Save the product ID
                $onboardingData['product_id'] = $productId;
                $this->accountRazorpayPlatformRepository->updateById(
                    $localAccount->getId(),
                    ['onboarding_data' => json_encode($onboardingData)]
                );
            }

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
            $this->failLocal($localAccount, $e);
            throw new RazorpayAccountSetupException(
                $e->getMessage(),
                previous: $e
            );
        }
    }

    private function updateProduct(RazorpayApiClient $client, string $razorpayAccountId, string $productId, UpdateRazorpaySettlementStageDTO $command): void
    {
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
    }

    private function configureProduct(RazorpayApiClient $client, string $razorpayAccountId, UpdateRazorpaySettlementStageDTO $command): string
    {
        $product = $client->requestProductConfiguration($razorpayAccountId, [
            'product_name' => 'route',
            'tnc_accepted' => true,
        ]);
        $productId = $product->id;

        $this->logger->info('Product configuration requested', [
            'razorpay_account_id' => $razorpayAccountId,
            'product_id' => $productId,
        ]);

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

        $this->logger->error('Razorpay onboarding settlement stage failed', [
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
}
