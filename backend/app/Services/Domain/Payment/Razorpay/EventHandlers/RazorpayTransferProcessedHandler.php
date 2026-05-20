<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayTransferPayload;

class RazorpayTransferProcessedHandler
{
    public function __construct(
        // Inject any dependencies needed for handling the transfer.processed event
    ) {
    }

    public function handleEvent(RazorpayTransferPayload $payload): void
    {
        // Implement logic to handle the transfer.processed event
        // For example, you might want to log the event or update your database records
    }
}