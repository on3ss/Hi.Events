<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\DTOs;

use Spatie\LaravelData\Data;
use InvalidArgumentException;

class RazorpayWebhookEnvelope extends Data
{
    public function __construct(
        public readonly string $entity,
        public readonly string $account_id,
        public readonly string $event,
        public readonly RazorpayOrderPaidPayload|RazorpayPaymentPayload|RazorpayRefundPayload|RazorpayAccountWebhookPayload $payload,
        public readonly int $created_at,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $event = $data['event'];
        $payloadData = $data['payload'];

        $payload = match ($event) {
            'order.paid' => RazorpayOrderPaidPayload::from([
                'order' => $payloadData['order']['entity'],
                'payment' => $payloadData['payment']['entity'],
            ]),
            'payment.captured', 'payment.failed', 'payment.authorized' => RazorpayPaymentPayload::from([
                'payment' => $payloadData['payment']['entity'],
            ]),
            'refund.processed' => RazorpayRefundPayload::from([
                'refund' => $payloadData['refund']['entity'],
            ]),
            'account.instantiated', 'account.under_review', 'account.funds_on_hold', 'account.status_updated' => RazorpayAccountWebhookPayload::from([
                'account' => $payloadData['account']['entity'],
            ]),
            default => throw new InvalidArgumentException("Unknown event: {$event}"),
        };

        return new self(
            entity: $data['entity'],
            account_id: $data['account_id'],
            event: $event,
            payload: $payload,
            created_at: $data['created_at'],
        );
    }
}