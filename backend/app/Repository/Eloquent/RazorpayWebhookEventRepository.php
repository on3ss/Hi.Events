<?php

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\RazorpayWebhookEventDomainObject;
use HiEvents\Models\RazorpayWebhookEvent;
use HiEvents\Repository\Interfaces\RazorpayWebhookEventRepositoryInterface;

class RazorpayWebhookEventRepository extends BaseRepository implements RazorpayWebhookEventRepositoryInterface
{
    protected function getModel(): string
    {
        return RazorpayWebhookEvent::class;
    }

    public function getDomainObject(): string
    {
        return RazorpayWebhookEventDomainObject::class;
    }

    public function findByEventId(
        string $eventId
    ): ?RazorpayWebhookEventDomainObject {
        return $this->findFirstWhere([
            'event_id' => $eventId,
        ]);
    }

    public function markProcessing(string $eventId): void
    {
        $event = $this->findByEventId($eventId);

        $this->updateWhere(
            attributes: [
                'status' => 'processing',
                'retry_count' => ($event?->getRetryCount() ?? 0) + 1,
            ],
            where: [
                'event_id' => $eventId,
            ]
        );
    }

    public function markProcessed(
        string $eventId,
        ?int $processingTimeMs = null
    ): void {
        $this->updateWhere(
            attributes: [
                'status' => 'processed',
                'processed_at' => now(),
                'processing_time_ms' => $processingTimeMs,
            ],
            where: [
                'event_id' => $eventId,
            ]
        );
    }

    public function markFailed(
        string $eventId,
        string $exception
    ): void {
        $event = $this->findByEventId($eventId);

        $this->updateWhere(
            attributes: [
                'status' => 'failed',
                'exception' => $exception,
                'retry_count' => ($event?->getRetryCount() ?? 0) + 1,
            ],
            where: [
                'event_id' => $eventId,
            ]
        );
    }
}