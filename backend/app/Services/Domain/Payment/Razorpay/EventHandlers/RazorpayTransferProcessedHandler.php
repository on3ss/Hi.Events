<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\Repository\Interfaces\RazorpayTransferRepositoryInterface;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayTransferPayload;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\Logger;

class RazorpayTransferProcessedHandler
{
    public function __construct(
        private readonly RazorpayTransferRepositoryInterface $transferRepository,
        private readonly ConnectionInterface $dbConnection,
        private readonly Logger $logger,
    ) {
    }

    public function handleEvent(RazorpayTransferPayload $payload): void
    {
        $transferEntity = $payload->transfer;

        $existingTransfer = $this->transferRepository
            ->findByRazorpayTransferId($transferEntity->id);

        if (!$existingTransfer) {
            $this->logger->warning('Razorpay transfer not found for processed webhook', [
                'razorpay_transfer_id' => $transferEntity->id,
            ]);

            return;
        }

        // Idempotency protection
        if (
            $existingTransfer->getStatus() === 'processed' &&
            $existingTransfer->getProcessedAt() !== null
        ) {
            $this->logger->info('Razorpay transfer already processed', [
                'razorpay_transfer_id' => $transferEntity->id,
            ]);

            return;
        }

        $this->dbConnection->transaction(function () use ($transferEntity, $payload) {

            $this->transferRepository->updateByRazorpayTransferId(
                $transferEntity->id,
                [
                    'status' => $transferEntity->status,
                    'processed_at' => now(),
                    'last_webhook_received_at' => now(),
                    'raw_payload' => $payload->toArray(),
                ]
            );

            $this->logger->info('Razorpay transfer processed successfully', [
                'razorpay_transfer_id' => $transferEntity->id,
                'razorpay_payment_id' => $transferEntity->source,
                'linked_account_id' => $transferEntity->recipient,
                'amount' => $transferEntity->amount,
                'currency' => $transferEntity->currency,
                'status' => $transferEntity->status,
            ]);
        });
    }
}