<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\DTOs;

use HiEvents\DataTransferObjects\BaseDataObject;

class RazorpayTransferDTO extends BaseDataObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $entity,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $recipient,
        public readonly string $status,
        public readonly ?string $source,
        public readonly ?array $notes,
        public readonly ?int $created_at,
    ) {
    }
}