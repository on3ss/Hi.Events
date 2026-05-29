<?php

namespace HiEvents\Services\Domain\Payment\Razorpay\EventHandlers;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\Repository\Interfaces\RazorpayOrdersRepositoryInterface;
use HiEvents\Services\Domain\Order\FinalizePaidOrderService;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\RazorpayPaymentPayload;
use Illuminate\Log\Logger;
use Throwable;

class RazorpayPaymentCapturedHandler
{
    public function __construct(
        private readonly RazorpayOrdersRepositoryInterface $razorpayOrdersRepository,
        private readonly FinalizePaidOrderService $finalizePaidOrderService,
        private readonly Logger $logger,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handleEvent(RazorpayPaymentPayload $event): void
    {
        $paymentEntity = $event->payment;

        $razorpayOrder = $this->razorpayOrdersRepository
            ->findByPaymentId($paymentEntity->id);

        if (!$razorpayOrder) {
            $this->logger->warning('Razorpay order not found for webhook', [
                'razorpay_payment_id' => $paymentEntity->id,
            ]);

            return;
        }

        $orderId = $razorpayOrder->getOrderId();

        $this->razorpayOrdersRepository->updateByOrderId(
            $orderId,
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
            orderId: $orderId,
            applicationFeeAmountMinorUnit: $paymentEntity->fee ?? 0,
            currency: $paymentEntity->currency,
            provider: PaymentProviders::RAZORPAY,
        );

        $this->logger->info('Razorpay payment captured via webhook', [
            'razorpay_payment_id' => $paymentEntity->id,
            'order_id' => $order->getId(),
            'amount' => $order->getTotalGross(),
            'currency' => $order->getCurrency(),
        ]);
    }
}