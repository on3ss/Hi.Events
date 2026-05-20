<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\Repository\Interfaces\RazorpayTransferRepositoryInterface;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayTransferPayload;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\Logger;

class RazorpayTransferCreatedHandler
{
    public function __construct(
        private readonly RazorpayTransferRepositoryInterface $transferRepository,
        private readonly ConnectionInterface $databaseConnection,
        private readonly Logger $logger,
    ) {
    }

    public function handleEvent(RazorpayTransferPayload $payload): void
    {
        // Implement the logic to handle the transfer.created event
        // You can access the event data from the $payload variable
        // For example:
        // $transferId = $payload->transfer->id;
        // $amount = $payload->transfer->amount;
        // $status = $payload->transfer->status;
        // Perform necessary actions based on the transfer details
    }
}