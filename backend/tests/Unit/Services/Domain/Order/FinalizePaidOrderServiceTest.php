<?php

namespace Tests\Unit\Services\Domain\Order;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\AffiliateRepositoryInterface;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Order\FinalizePaidOrderService;
use HiEvents\Services\Domain\Order\OrderApplicationFeeService;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use HiEvents\Services\Infrastructure\DomainEvents\DomainEventDispatcherService;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FinalizePaidOrderServiceTest extends TestCase
{
    private OrderRepositoryInterface&MockObject $orderRepository;
    private AffiliateRepositoryInterface&MockObject $affiliateRepository;
    private ProductQuantityUpdateService&MockObject $quantityUpdateService;
    private AttendeeRepositoryInterface&MockObject $attendeeRepository;
    private DomainEventDispatcherService&MockObject $domainEventDispatcher;
    private OrderApplicationFeeService&MockObject $orderApplicationFeeService;
    private ConnectionInterface&MockObject $connection;

    private FinalizePaidOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->affiliateRepository = $this->createMock(AffiliateRepositoryInterface::class);
        $this->quantityUpdateService = $this->createMock(ProductQuantityUpdateService::class);
        $this->attendeeRepository = $this->createMock(AttendeeRepositoryInterface::class);
        $this->domainEventDispatcher = $this->createMock(DomainEventDispatcherService::class);
        $this->orderApplicationFeeService = $this->createMock(OrderApplicationFeeService::class);
        $this->connection = $this->createMock(ConnectionInterface::class);

        $this->connection
            ->method('transaction')
            ->willReturnCallback(fn ($callback) => $callback());

        $this->service = new FinalizePaidOrderService(
            $this->orderRepository,
            $this->affiliateRepository,
            $this->quantityUpdateService,
            $this->attendeeRepository,
            $this->domainEventDispatcher,
            $this->orderApplicationFeeService,
            $this->connection,
        );
    }

    public function testReturnsImmediatelyWhenOrderAlreadyPaid(): void
    {
        $order = $this->createMock(OrderDomainObject::class);

        $order->method('getPaymentStatus')
            ->willReturn(
                OrderPaymentStatus::PAYMENT_RECEIVED->name
            );

        $this->orderRepository
            ->expects($this->once())
            ->method('loadRelation')
            ->with(
                $this->callback(
                    fn ($relationship) =>
                        $relationship instanceof Relationship
                )
            )
            ->willReturnSelf();

        $this->orderRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($order);

        $this->orderRepository
            ->expects($this->never())
            ->method('updateFromArray');

        $this->attendeeRepository
            ->expects($this->never())
            ->method('updateWhere');

        $this->quantityUpdateService
            ->expects($this->never())
            ->method('updateQuantitiesFromOrder');

        $this->affiliateRepository
            ->expects($this->never())
            ->method('incrementSales');

        $this->orderApplicationFeeService
            ->expects($this->never())
            ->method('createOrderApplicationFee');

        $this->domainEventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $result = $this->service->finalize(
            orderId: 1,
            applicationFeeAmountMinorUnit: 100,
            currency: 'INR',
            provider: PaymentProviders::RAZORPAY,
        );

        $this->assertSame($order, $result);
    }
}