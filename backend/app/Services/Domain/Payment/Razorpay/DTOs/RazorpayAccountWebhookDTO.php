<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\DTOs;

use HiEvents\DataTransferObjects\BaseDataObject;

class RazorpayAccountWebhookDTO extends BaseDataObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $entity,
        public readonly string $status,
        public readonly ?array $notes,
        public readonly ?array $reject_reasons = null,
        public readonly ?array $rejection_reasons = null,
    ) {
    }
}
