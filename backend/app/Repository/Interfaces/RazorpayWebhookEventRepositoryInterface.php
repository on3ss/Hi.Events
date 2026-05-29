<?php

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\RazorpayWebhookEventDomainObject;

interface RazorpayWebhookEventRepositoryInterface extends RepositoryInterface
{
    public function findByEventId(
        string $eventId
    ): ?RazorpayWebhookEventDomainObject;

    public function markProcessing(
        string $eventId
    ): void;

    public function markProcessed(
        string $eventId,
        ?int $processingTimeMs = null
    ): void;

    public function markFailed(
        string $eventId,
        string $exception
    ): void;
}