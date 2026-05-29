<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\Repository\Interfaces\RazorpayOrdersRepositoryInterface;
use HiEvents\Services\Domain\Order\FinalizePaidOrderService;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayOrderPaidPayload;
use Illuminate\Log\Logger;
use Throwable;

class RazorpayOrderPaidHandler
{
    public function __construct(
        private readonly RazorpayOrdersRepositoryInterface $razorpayOrdersRepository,
        private readonly FinalizePaidOrderService $finalizePaidOrderService,
        private readonly Logger $logger,
    ) {}

    /**
     * @throws Throwable
     */
    public function handleEvent(RazorpayOrderPaidPayload $event): void
    {
        $orderEntity = $event->order;
        $paymentEntity = $event->payment;

        $razorpayOrder = $this->razorpayOrdersRepository
            ->findByRazorpayOrderId($orderEntity->id);

        if (! $razorpayOrder) {
            $this->logger->warning(
                'Razorpay order not found for order.paid webhook',
                [
                    'razorpay_order_id' => $orderEntity->id,
                ]
            );

            return;
        }

        $localOrderId = $razorpayOrder->getOrderId();

        $this->razorpayOrdersRepository->updateByOrderId(
            $localOrderId,
            [
                'razorpay_payment_id' => $paymentEntity->id,
                'status' => $paymentEntity->status,
                'method' => $paymentEntity->method,
                'amount' => $paymentEntity->amount,
                'currency' => $paymentEntity->currency,
                'fee' => $paymentEntity->fee,
                'tax' => $paymentEntity->tax,
            ]
        );

        $order = $this->finalizePaidOrderService->finalize(
            orderId: $localOrderId,
            applicationFeeAmountMinorUnit: $paymentEntity->fee ?? 0,
            currency: $paymentEntity->currency,
            provider: PaymentProviders::RAZORPAY,
        );

        $this->logger->info(
            'Razorpay order.paid webhook processed successfully',
            [
                'razorpay_order_id' => $orderEntity->id,
                'razorpay_payment_id' => $paymentEntity->id,
                'local_order_id' => $order->getId(),
            ]
        );
    }
}
