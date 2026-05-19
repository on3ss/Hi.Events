<?php

namespace HiEvents\Services\Domain\Payment\Razorpay;

use HiEvents\Exceptions\Razorpay\CreateOrderFailedException;
use HiEvents\Services\Domain\Order\OrderApplicationFeeCalculationService;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\CreateRazorpayOrderRequestDTO;
use HiEvents\Services\Domain\Payment\Razorpay\DTOs\CreateRazorpayOrderResponseDTO;
use HiEvents\Services\Infrastructure\Razorpay\RazorpayClientFactory;
use Illuminate\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Razorpay\Api\Errors\Error;
use Throwable;

class RazorpayOrderCreationService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Repository $config,
        private readonly ConnectionInterface $dbConnection,
        private readonly OrderApplicationFeeCalculationService $orderApplicationFeeCalculationService,
        private readonly RazorpayClientFactory $razorpayClientFactory,
    ) {
    }

    /**
     * @throws CreateOrderFailedException
     * @throws Throwable
     */
    public function createOrder(CreateRazorpayOrderRequestDTO $orderDTO): CreateRazorpayOrderResponseDTO
    {
        try {
            $this->dbConnection->beginTransaction();

            $razorpayClient = $this->razorpayClientFactory->create();

            $amountInSmallestUnit = $orderDTO->amount->toMinorUnit();

            $orderData = [
                'amount' => $amountInSmallestUnit,
                'currency' => $orderDTO->currencyCode,
                'receipt' => $orderDTO->order->getShortId(),
                'payment_capture' => 1, // Auto-capture payment
                'notes' => [
                    'order_id' => $orderDTO->order->getId(),
                    'event_id' => $orderDTO->order->getEventId(),
                    'order_short_id' => $orderDTO->order->getShortId(),
                    'account_id' => $orderDTO->account->getId(),
                ],
            ];

            $applicationFee = $this->orderApplicationFeeCalculationService->calculateApplicationFee(
                accountConfiguration: $orderDTO->account->getConfiguration(),
                order: $orderDTO->order,
                vatSettings: $orderDTO->account->getAccountVatSetting()
            );

            $connectedAccountId = $orderDTO->account->getRazorpayPlatform()?->getRazorpayAccountId();

            if ($connectedAccountId && $applicationFee && $this->config->get('services.razorpay.application_fee_enabled')) {
                $grossAmountMinor = $amountInSmallestUnit;
                $applicationFeeMinor = $applicationFee->grossApplicationFee->toMinorUnit();

                $destinationAmountMinor = $grossAmountMinor - $applicationFeeMinor;

                if ($destinationAmountMinor < 0) {
                    $destinationAmountMinor = 0;
                }

                $orderData['transfers'] = [
                    [
                        'account' => $connectedAccountId,
                        'amount' => $destinationAmountMinor,
                        'currency' => $orderDTO->currencyCode,
                        'notes' => [
                            'type' => 'event_organizer_payout',
                            'order_id' => $orderDTO->order->getId(),
                        ],
                    ],
                ];
            }

            $razorpayOrder = $razorpayClient->createOrder($orderData);

            $this->logger->debug('Razorpay order created', [
                'razorpayOrderId' => $razorpayOrder->id,
                'orderDTO' => $orderDTO->toArray(['account']),
            ]);

            $this->dbConnection->commit();

            return new CreateRazorpayOrderResponseDTO(
                id: $razorpayOrder->id,
                keyId: $this->config->get('services.razorpay.key_id'),
                amount: $razorpayOrder->amount,
                currency: $razorpayOrder->currency,
                receipt: $razorpayOrder->receipt,
            );
        } catch (Error $exception) {
            $this->logger->error("Razorpay order creation failed: {$exception->getMessage()}", [
                'exception' => $exception,
                'orderDTO' => $orderDTO->toArray(['account']),
            ]);

            $this->dbConnection->rollBack();

            throw new CreateOrderFailedException(
                __('There was an error communicating with the payment provider. Please try again later.')
            );
        } catch (Throwable $exception) {
            $this->dbConnection->rollBack();

            throw $exception;
        }
    }
}