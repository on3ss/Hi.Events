<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\Repository\Interfaces\AccountRazorpayPlatformRepositoryInterface;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayAccountWebhookPayload;
use Illuminate\Log\Logger;
use Throwable;

class RazorpayAccountStatusHandler
{
    public function __construct(
        private readonly AccountRazorpayPlatformRepositoryInterface $accountRazorpayPlatformRepository,
        private readonly Logger $logger,
    ) {
    }

    public function handleEvent(string $event, RazorpayAccountWebhookPayload $payload): void
    {
        $accountEntity = $payload->account;

        $razorpayAccountId = $accountEntity->id;

        $platform = $this->accountRazorpayPlatformRepository->findByRazorpayAccountId($razorpayAccountId);

        if (!$platform) {
            $this->logger->warning('Razorpay platform record not found for webhook', [
                'event' => $event,
                'razorpay_account_id' => $razorpayAccountId,
            ]);
            return;
        }

        $updateData = [];

        if (isset($accountEntity->status)) {
            $updateData['status'] = $this->mapRazorpayStatusToLocalStatus($accountEntity->status);
        }

        if ($accountEntity->status === 'activated') {
            $updateData['activated_at'] = now()->toDateTimeString();
        }

        // Add error details if rejected
        if ($accountEntity->status === 'rejected') {
            $reasons = $accountEntity->reject_reasons ?? $accountEntity->rejection_reasons ?? [];
            $updateData['error'] = json_encode(['reasons' => $reasons]);
        }

        // Always update the raw details
        $currentDetails = $platform->getRazorpayAccountDetails() ?? [];
        if (is_string($currentDetails)) {
            $currentDetails = json_decode($currentDetails, true) ?: [];
        }

        $mergedDetails = array_merge($currentDetails, $accountEntity->toArray());
        $updateData['razorpay_account_details'] = json_encode($mergedDetails);

        $this->accountRazorpayPlatformRepository->updateById($platform->getId(), $updateData);

        $this->logger->info('Razorpay account webhook processed', [
            'event' => $event,
            'razorpay_account_id' => $razorpayAccountId,
            'local_account_id' => $platform->getAccountId(),
            'new_status' => $updateData['status'] ?? null,
        ]);
    }

    private function mapRazorpayStatusToLocalStatus(string $razorpayStatus): string
    {
        return match ($razorpayStatus) {
            'activated' => 'active',
            'needs_clarification' => 'failed',
            'rejected' => 'failed',
            'under_review' => 'pending_activation',
            'created' => 'pending_activation',
            default => $razorpayStatus,
        };
    }
}
