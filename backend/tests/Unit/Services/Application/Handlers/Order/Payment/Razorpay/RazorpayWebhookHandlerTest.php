<?php

namespace Tests\Unit\Services\Application\Handlers\Order\Payment\Razorpay;

use HiEvents\DomainObjects\RazorpayWebhookEventDomainObject;
use HiEvents\Exceptions\Razorpay\InvalidSignatureException;
use HiEvents\Repository\Interfaces\RazorpayWebhookEventRepositoryInterface;
use HiEvents\Services\Application\Handlers\Order\Payment\Razorpay\RazorpayWebhookHandler;
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
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class RazorpayWebhookHandlerTest extends TestCase
{
    private RazorpayPaymentCapturedHandler&MockObject $paymentCapturedHandlerMock;

    private RazorpayOrderPaidHandler&MockObject $orderPaidHandlerMock;

    private RazorpayRefundHandler&MockObject $refundHandlerMock;

    private RazorpayPaymentFailedHandler&MockObject $paymentFailedHandlerMock;

    private RazorpayPaymentAuthorizedHandler&MockObject $paymentAuthorizedHandlerMock;

    private RazorpayTransferCreatedHandler&MockObject $transferCreatedHandlerMock;

    private RazorpayTransferProcessedHandler&MockObject $transferProcessedHandlerMock;

    private RazorpayTransferFailedHandler&MockObject $transferFailedHandlerMock;

    private RazorpayTransferReversedHandler&MockObject $transferReversedHandlerMock;

    private RazorpayPaymentVerificationService&MockObject $verificationServiceMock;

    private RazorpayWebhookEventRepositoryInterface&MockObject $webhookRepositoryMock;

    private Logger&MockObject $loggerMock;

    private RazorpayWebhookHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentCapturedHandlerMock = $this->createMock(RazorpayPaymentCapturedHandler::class);
        $this->orderPaidHandlerMock = $this->createMock(RazorpayOrderPaidHandler::class);
        $this->refundHandlerMock = $this->createMock(RazorpayRefundHandler::class);
        $this->paymentFailedHandlerMock = $this->createMock(RazorpayPaymentFailedHandler::class);
        $this->paymentAuthorizedHandlerMock = $this->createMock(RazorpayPaymentAuthorizedHandler::class);

        $this->transferCreatedHandlerMock = $this->createMock(RazorpayTransferCreatedHandler::class);
        $this->transferProcessedHandlerMock = $this->createMock(RazorpayTransferProcessedHandler::class);
        $this->transferFailedHandlerMock = $this->createMock(RazorpayTransferFailedHandler::class);
        $this->transferReversedHandlerMock = $this->createMock(RazorpayTransferReversedHandler::class);

        $this->verificationServiceMock = $this->createMock(RazorpayPaymentVerificationService::class);
        $this->webhookRepositoryMock = $this->createMock(RazorpayWebhookEventRepositoryInterface::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->handler = new RazorpayWebhookHandler(
            $this->paymentCapturedHandlerMock,
            $this->orderPaidHandlerMock,
            $this->refundHandlerMock,
            $this->paymentFailedHandlerMock,
            $this->paymentAuthorizedHandlerMock,

            $this->transferCreatedHandlerMock,
            $this->transferProcessedHandlerMock,
            $this->transferFailedHandlerMock,
            $this->transferReversedHandlerMock,

            $this->verificationServiceMock,
            $this->webhookRepositoryMock,
            $this->loggerMock,
        );
    }

    public function test_handle_throws_exception_on_invalid_signature(): void
    {
        $payload = '{"test":"data"}';
        $signature = 'invalid_sig';

        $this->verificationServiceMock
            ->method('verifyWebhookSignature')
            ->with($payload, $signature)
            ->willReturn(false);

        $this->expectException(InvalidSignatureException::class);

        $this->handler->handle($payload, $signature);
    }

    public function test_handle_returns_early_if_event_already_processed(): void
    {
        $payload = $this->createPaymentWebhookJson(
            'payment.captured',
            'pay_123'
        );

        $signature = 'valid_sig';

        $event = $this->createMock(RazorpayWebhookEventDomainObject::class);

        $event->method('getStatus')
            ->willReturn('processed');

        $this->verificationServiceMock
            ->method('verifyWebhookSignature')
            ->willReturn(true);

        $this->webhookRepositoryMock
            ->expects($this->once())
            ->method('findByEventId')
            ->with('pay_123')
            ->willReturn($event);

        $this->paymentCapturedHandlerMock
            ->expects($this->never())
            ->method('handleEvent');

        $this->handler->handle($payload, $signature);
    }

    public function test_handle_routes_to_payment_captured_handler(): void
    {
        $payload = $this->createPaymentWebhookJson(
            'payment.captured',
            'pay_123'
        );

        $signature = 'valid_sig';

        $this->verificationServiceMock
            ->method('verifyWebhookSignature')
            ->willReturn(true);

        $this->webhookRepositoryMock
            ->method('findByEventId')
            ->willReturn(null);

        $this->webhookRepositoryMock
            ->expects($this->once())
            ->method('create');

        $this->webhookRepositoryMock
            ->expects($this->once())
            ->method('markProcessing')
            ->with('pay_123');

        $this->webhookRepositoryMock
            ->expects($this->once())
            ->method('markProcessed')
            ->with(
                'pay_123',
                $this->isInt()
            );

        $this->paymentCapturedHandlerMock
            ->expects($this->once())
            ->method('handleEvent');

        $this->handler->handle($payload, $signature);
    }

    public function test_handle_routes_to_order_paid_handler(): void
    {
        $payload = json_encode([
            'entity' => 'event',
            'account_id' => 'acc_123',
            'event' => 'order.paid',
            'created_at' => time(),
            'payload' => [
                'order' => [
                    'entity' => [
                        'id' => 'order_rzp_123',
                        'entity' => 'order',
                        'amount' => 50000,
                        'amount_paid' => 50000,
                        'amount_due' => 0,
                        'currency' => 'INR',
                        'status' => 'paid',
                        'receipt' => 'rcpt_1',
                        'notes' => [],
                        'created_at' => time(),
                    ],
                ],
                'payment' => [
                    'entity' => $this->getPaymentEntityData('pay_123'),
                ],
            ],
        ]);

        $signature = 'valid_sig';

        $this->verificationServiceMock
            ->method('verifyWebhookSignature')
            ->willReturn(true);

        $this->webhookRepositoryMock
            ->method('findByEventId')
            ->willReturn(null);

        $this->orderPaidHandlerMock
            ->expects($this->once())
            ->method('handleEvent');

        $this->handler->handle($payload, $signature);
    }

    public function test_handle_returns_early_for_unknown_event(): void
    {
        $payload = json_encode([
            'entity' => 'event',
            'account_id' => 'acc_123',
            'event' => 'payment.dispute.created',
            'created_at' => time(),
            'payload' => [],
        ]);

        $signature = 'valid_sig';

        $this->verificationServiceMock
            ->method('verifyWebhookSignature')
            ->willReturn(true);

        $this->loggerMock
            ->expects($this->once())
            ->method('debug');

        $this->handler->handle($payload, $signature);
    }

    private function createPaymentWebhookJson(
        string $event,
        string $paymentId
    ): string {
        return json_encode([
            'entity' => 'event',
            'account_id' => 'acc_123',
            'event' => $event,
            'created_at' => time(),
            'payload' => [
                'payment' => [
                    'entity' => $this->getPaymentEntityData($paymentId),
                ],
            ],
        ]);
    }

    private function getPaymentEntityData(string $paymentId): array
    {
        return [
            'id' => $paymentId,
            'entity' => 'payment',
            'amount' => 50000,
            'currency' => 'INR',
            'status' => 'captured',
            'method' => 'card',
            'order_id' => 'order_rzp_123',
            'fee' => 100,
            'tax' => 18,
            'description' => 'Test',
            'notes' => [],
            'vpa' => null,
            'email' => 'test@example.com',
            'contact' => '+919999999999',
            'created_at' => time(),
            'error' => null,
        ];
    }
}
