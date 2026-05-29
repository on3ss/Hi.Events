<?php

namespace Tests\Unit\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\RazorpayOrderDomainObject;
use HiEvents\Repository\Interfaces\RazorpayOrdersRepositoryInterface;
use HiEvents\Services\Domain\Order\FinalizePaidOrderService;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayPaymentDTO;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayPaymentPayload;
use HiEvents\Services\Domain\Payment\Razorpay\EventHandlers\RazorpayPaymentCapturedHandler;
use Illuminate\Log\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class RazorpayPaymentCapturedHandlerTest extends TestCase
{
    private RazorpayOrdersRepositoryInterface&MockObject $razorpayOrdersRepository;
    private FinalizePaidOrderService&MockObject $finalizePaidOrderService;
    private Logger&MockObject $logger;

    private RazorpayPaymentCapturedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->razorpayOrdersRepository = $this->createMock(
            RazorpayOrdersRepositoryInterface::class
        );

        $this->finalizePaidOrderService = $this->createMock(
            FinalizePaidOrderService::class
        );

        $this->logger = $this->createMock(Logger::class);

        $this->handler = new RazorpayPaymentCapturedHandler(
            $this->razorpayOrdersRepository,
            $this->finalizePaidOrderService,
            $this->logger,
        );
    }

    public function testReturnsEarlyWhenRazorpayOrderNotFound(): void
    {
        $payload = $this->createPayload();

        $this->razorpayOrdersRepository
            ->expects($this->once())
            ->method('findByPaymentId')
            ->with('pay_123')
            ->willReturn(null);

        $this->razorpayOrdersRepository
            ->expects($this->never())
            ->method('updateByOrderId');

        $this->finalizePaidOrderService
            ->expects($this->never())
            ->method('finalize');

        $this->handler->handleEvent($payload);
    }

    public function testUpdatesRazorpayOrderAndFinalizesOrder(): void
    {
        $payload = $this->createPayload();

        $razorpayOrder = $this->createMock(
            RazorpayOrderDomainObject::class
        );

        $razorpayOrder
            ->method('getOrderId')
            ->willReturn(10);

        $this->razorpayOrdersRepository
            ->expects($this->once())
            ->method('findByPaymentId')
            ->with('pay_123')
            ->willReturn($razorpayOrder);

        $this->razorpayOrdersRepository
            ->expects($this->once())
            ->method('updateByOrderId')
            ->with(
                10,
                [
                    'razorpay_payment_id' => 'pay_123',
                    'status' => 'captured',
                    'method' => 'card',
                    'amount' => 50000,
                    'currency' => 'INR',
                    'fee' => 100,
                    'tax' => 18,
                ]
            );

        $order = $this->createMock(OrderDomainObject::class);

        $order->method('getId')
            ->willReturn(10);

        $order->method('getTotalGross')
            ->willReturn((float) 500);

        $order->method('getCurrency')
            ->willReturn('INR');

        $this->finalizePaidOrderService
            ->expects($this->once())
            ->method('finalize')
            ->with(
                orderId: 10,
                applicationFeeAmountMinorUnit: 100,
                currency: 'INR',
                provider: \HiEvents\DomainObjects\Enums\PaymentProviders::RAZORPAY,
            )
            ->willReturn($order);

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->handler->handleEvent($payload);
    }

    private function createPayload(): RazorpayPaymentPayload
    {
        $payment = new RazorpayPaymentDTO(
            id: 'pay_123',
            entity: 'payment',
            amount: 50000,
            currency: 'INR',
            status: 'captured',
            method: 'card',
            order_id: 'order_rzp_123',
            fee: 100,
            tax: 18,
            description: 'Test payment',
            notes: [],
            vpa: null,
            email: 'test@example.com',
            contact: '+919999999999',
            created_at: time(),
            error: null,
        );

        return new RazorpayPaymentPayload(
            payment: $payment,
        );
    }
}