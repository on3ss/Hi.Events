<?php

namespace HiEvents\Services\Application\Handlers\Order\Payment\Razorpay;

use HiEvents\Exceptions\Razorpay\InvalidSignatureException;
use HiEvents\Repository\Interfaces\RazorpayWebhookEventRepositoryInterface;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayWebhookEnvelope;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayOrderPaidHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayPaymentAuthorizedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayPaymentCapturedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayPaymentFailedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayRefundHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayTransferCreatedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayTransferFailedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayTransferProcessedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayTransferReversedHandler;
use HiEvents\Services\Domain\Payment\Razorpay\RazorpayPaymentVerificationService;
use Illuminate\Log\Logger;
use JsonException;
use Spatie\LaravelData\Exceptions\CannotCreateData;
use Throwable;

class RazorpayWebhookHandler
{
    private static array $validEvents = [
        'payment.captured',
        'order.paid',
        'refund.processed',
        'payment.failed',
        'payment.authorized',

        'transfer.created',
        'transfer.processed',
        'transfer.failed',
        'transfer.reversed',
    ];

    public function __construct(
        private readonly RazorpayPaymentCapturedHandler $paymentCapturedHandler,
        private readonly RazorpayOrderPaidHandler $orderPaidHandler,
        private readonly RazorpayRefundHandler $refundHandler,
        private readonly RazorpayPaymentFailedHandler $paymentFailedHandler,
        private readonly RazorpayPaymentAuthorizedHandler $paymentAuthorizedHandler,

        private readonly RazorpayTransferCreatedHandler $transferCreatedHandler,
        private readonly RazorpayTransferProcessedHandler $transferProcessedHandler,
        private readonly RazorpayTransferFailedHandler $transferFailedHandler,
        private readonly RazorpayTransferReversedHandler $transferReversedHandler,

        private readonly RazorpayPaymentVerificationService $razorpayPaymentService,
        private readonly RazorpayWebhookEventRepositoryInterface $webhookEventRepository,
        private readonly Logger $logger,
    ) {}

    /**
     * @throws InvalidSignatureException
     * @throws JsonException
     * @throws CannotCreateData
     * @throws Throwable
     */
    public function handle(string $payload, string $signature, array $headers = []): void
    {
        $startedAt = microtime(true);

        $eventId = null;

        try {
            if (!$this->razorpayPaymentService->verifyWebhookSignature($payload, $signature)) {
                throw new InvalidSignatureException(__('Invalid Razorpay webhook signature'));
            }

            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            try {
                $envelope = RazorpayWebhookEnvelope::fromArray($data);
            } catch (\InvalidArgumentException $e) {
                $this->logger->debug('Unsupported or unknown webhook event', [
                    'event' => $data['event'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            $event = $envelope->event;

            if (!in_array($event, self::$validEvents, true)) {
                $this->logger->debug('Unsupported webhook event', [
                    'event' => $event,
                ]);

                return;
            }

            $eventId = match ($event) {
                'payment.captured',
                'payment.failed',
                'payment.authorized'
                    => $envelope->payload->payment->id,

                'order.paid'
                    => $envelope->payload->order->id,

                'refund.processed'
                    => $envelope->payload->refund->id,

                'transfer.created',
                'transfer.processed',
                'transfer.failed',
                'transfer.reversed'
                    => $envelope->payload->transfer->id,

                default => null,
            };

            if (!$eventId) {
                $this->logger->error('Could not extract event ID from payload', [
                    'event' => $event,
                ]);

                return;
            }

            $existingEvent = $this->webhookEventRepository
                ->findByEventId($eventId);

            if (
                $existingEvent &&
                $existingEvent->getStatus() === 'processed'
            ) {
                $this->logger->debug('Razorpay webhook event already processed', [
                    'event_id' => $eventId,
                    'type' => $event,
                ]);

                return;
            }

            $entityType = explode('.', $event)[0];

            if (!$existingEvent) {
                $this->webhookEventRepository->create([
                    'event_id' => $eventId,
                    'event_type' => $event,
                    'entity_id' => $eventId,
                    'entity_type' => $entityType,
                    'status' => 'received',
                    'payload' => $data,
                    'headers' => $headers,
                    'signature' => $signature,
                    'received_at' => now(),
                ]);
            } else {
                $this->webhookEventRepository->updateWhere(
                    attributes: [
                        'payload' => $data,
                        'headers' => $headers,
                        'signature' => $signature,
                        'exception' => null,
                    ],
                    where: [
                        'event_id' => $eventId,
                    ],
                );
            }

            $this->webhookEventRepository->markProcessing($eventId);

            $this->logger->debug('Processing Razorpay webhook', [
                'event' => $event,
                'event_id' => $eventId,
            ]);

            match ($event) {
                'payment.captured'
                    => $this->paymentCapturedHandler->handleEvent($envelope->payload),

                'order.paid'
                    => $this->orderPaidHandler->handleEvent($envelope->payload),

                'refund.processed'
                    => $this->refundHandler->handleEvent($envelope->payload),

                'payment.failed'
                    => $this->paymentFailedHandler->handleEvent($envelope->payload),

                'payment.authorized'
                    => $this->paymentAuthorizedHandler->handleEvent($envelope->payload),

                'transfer.created'
                    => $this->transferCreatedHandler->handleEvent($envelope->payload),

                'transfer.processed'
                    => $this->transferProcessedHandler->handleEvent($envelope->payload),

                'transfer.failed'
                    => $this->transferFailedHandler->handleEvent($envelope->payload),

                'transfer.reversed'
                    => $this->transferReversedHandler->handleEvent($envelope->payload),

                default
                    => $this->logger->debug('No handler for event', [
                        'event' => $event,
                    ]),
            };

            $processingTimeMs = (int) (
                (microtime(true) - $startedAt) * 1000
            );

            $this->webhookEventRepository->markProcessed(
                $eventId,
                $processingTimeMs
            );
        } catch (InvalidSignatureException $e) {
            $this->logger->error('Signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (JsonException $e) {
            $this->logger->error('Invalid JSON payload', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw $e;
        } catch (CannotCreateData $e) {
            $this->logger->error('Failed to create DTO from webhook payload', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw $e;
        } catch (Throwable $e) {

            if ($eventId !== null) {
                $this->webhookEventRepository->markFailed(
                    $eventId,
                    $e->getMessage()
                );
            }

            $this->logger->error('Unhandled exception processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}