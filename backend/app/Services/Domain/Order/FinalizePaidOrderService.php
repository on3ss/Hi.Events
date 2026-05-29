<?php

namespace HiEvents\Services\Domain\Order;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\DomainObjects\Status\OrderApplicationFeeStatus;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Events\OrderStatusChangedEvent;
use HiEvents\Models\Order;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\AffiliateRepositoryInterface;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use HiEvents\Services\Infrastructure\DomainEvents\DomainEventDispatcherService;
use HiEvents\Services\Infrastructure\DomainEvents\Enums\DomainEventType;
use HiEvents\Services\Infrastructure\DomainEvents\Events\OrderEvent;
use Illuminate\Database\ConnectionInterface;
use Throwable;

class FinalizePaidOrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AffiliateRepositoryInterface $affiliateRepository,
        private readonly ProductQuantityUpdateService $quantityUpdateService,
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly DomainEventDispatcherService $domainEventDispatcherService,
        private readonly OrderApplicationFeeService $orderApplicationFeeService,
        private readonly ConnectionInterface $dbConnection,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function finalize(
        int $orderId,
        int $applicationFeeAmountMinorUnit,
        string $currency,
        PaymentProviders $provider,
    ): OrderDomainObject {
        return $this->dbConnection->transaction(function () use (
            $orderId,
            $applicationFeeAmountMinorUnit,
            $currency,
            $provider,
        ) {

            Order::query()
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $order = $this->orderRepository
                ->loadRelation(new Relationship(OrderItemDomainObject::class))
                ->findById($orderId);

            if (!$order) {
                throw new \RuntimeException(
                    sprintf('Order [%s] not found', $orderId)
                );
            }

            if (
                $order->getPaymentStatus() ===
                OrderPaymentStatus::PAYMENT_RECEIVED->name
            ) {
                return $order;
            }

            $updatedOrder = $this->orderRepository
                ->updateFromArray($order->getId(), [
                    OrderDomainObjectAbstract::PAYMENT_STATUS =>
                        OrderPaymentStatus::PAYMENT_RECEIVED->name,

                    OrderDomainObjectAbstract::STATUS =>
                        OrderStatus::COMPLETED->name,

                    OrderDomainObjectAbstract::PAYMENT_PROVIDER =>
                        $provider->value,
                ]);

            $this->attendeeRepository->updateWhere(
                attributes: [
                    'status' => AttendeeStatus::ACTIVE->name,
                ],
                where: [
                    'order_id' => $updatedOrder->getId(),
                    'status' => AttendeeStatus::AWAITING_PAYMENT->name,
                ],
            );

            $this->quantityUpdateService
                ->updateQuantitiesFromOrder($updatedOrder);

            $orderArray = $updatedOrder->toArray();
            $affiliateId = $orderArray['affiliate_id'] ?? null;

            if ($affiliateId) {
                $this->affiliateRepository->incrementSales(
                    affiliateId: $affiliateId,
                    amount: $updatedOrder->getTotalGross()
                );
            }

            $this->orderApplicationFeeService->createOrderApplicationFee(
                orderId: $updatedOrder->getId(),
                applicationFeeAmountMinorUnit: $applicationFeeAmountMinorUnit,
                orderApplicationFeeStatus: OrderApplicationFeeStatus::PAID,
                paymentMethod: $provider,
                currency: $currency,
            );

            OrderStatusChangedEvent::dispatch($updatedOrder);

            $this->domainEventDispatcherService->dispatch(
                new OrderEvent(
                    type: DomainEventType::ORDER_CREATED,
                    orderId: $updatedOrder->getId(),
                )
            );

            return $updatedOrder;
        });
    }
}