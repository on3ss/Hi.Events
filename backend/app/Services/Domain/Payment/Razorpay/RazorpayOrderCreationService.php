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

            if ($this->config->get('app.saas_mode_enabled')) {
                $razorpayAccountId = $orderDTO->account->getActiveRazorpayAccountId();
                if ($razorpayAccountId === null) {
                    $this->logger->error(
                        'Razorpay account not found for the event organizer, order creation failed.
                        You will need to connect your Razorpay account to receive payments.',
                        ['orderDTO' => $orderDTO->toArray(['account'])]
                    );

                    throw new CreateOrderFailedException(
                        __('Razorpay Linked account not found for the event organizer')
                    );
                }

                $accountConfiguration = $orderDTO->account->getConfiguration();
                $bypassApplicationFees = $accountConfiguration?->getBypassApplicationFees() ?? false;

                $applicationFee = $this->orderApplicationFeeCalculationService->calculateApplicationFee(
                    accountConfiguration: $accountConfiguration,
                    order: $orderDTO->order,
                    vatSettings: $orderDTO->account->getAccountVatSetting(),
                );

                if ($applicationFee && !$bypassApplicationFees) {
                    $feeMinorUnit = $applicationFee->grossApplicationFee->toMinorUnit();

                    $orderData['transfers'] = [
                        [
                            'account' => $razorpayAccountId,
                            'amount' => $amountInSmallestUnit - $feeMinorUnit,
                            'currency' => $orderDTO->currencyCode,
                            'notes' => [
                                'order_id' => $orderDTO->order->getId(),
                                'type' => 'vendor_transfer'
                            ],
                            'on_hold' => 0
                        ]
                    ];
                } else {
                    $orderData['transfers'] = [
                        [
                            'account' => $razorpayAccountId,
                            'amount' => $amountInSmallestUnit,
                            'currency' => $orderDTO->currencyCode,
                            'notes' => [
                                'order_id' => $orderDTO->order->getId(),
                                'type' => 'vendor_transfer'
                            ],
                            'on_hold' => 0
                        ]
                    ];
                }
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