<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\Repository\Interfaces\RazorpayTransferRepositoryInterface;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayTransferPayload;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\Logger;
use Throwable;

class RazorpayTransferReversedHandler
{
    public function __construct(
        private readonly RazorpayTransferRepositoryInterface $transferRepository,
        private readonly ConnectionInterface $dbConnection,
        private readonly Logger $logger,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handleEvent(RazorpayTransferPayload $payload): void
    {
        $transferEntity = $payload->transfer;

        $existingTransfer = $this->transferRepository
            ->findByRazorpayTransferId($transferEntity->id);

        if (!$existingTransfer) {
            $this->logger->warning('Razorpay transfer not found for reversed webhook', [
                'razorpay_transfer_id' => $transferEntity->id,
            ]);

            return;
        }

        // Idempotency protection
        if (
            $existingTransfer->getStatus() === 'reversed' &&
            $existingTransfer->getReversedAt() !== null
        ) {
            $this->logger->info('Razorpay transfer already marked as reversed', [
                'razorpay_transfer_id' => $transferEntity->id,
            ]);

            return;
        }

        $this->dbConnection->transaction(function () use ($transferEntity, $payload) {

            $this->transferRepository->updateByRazorpayTransferId(
                $transferEntity->id,
                [
                    'status' => $transferEntity->status,
                    'reversed_at' => now(),
                    'raw_payload' => $payload->toArray(),
                ]
            );

            $this->logger->warning('Razorpay transfer reversed', [
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