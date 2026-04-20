<?php
$file = 'backend/app/Services/Domain/Payment/Razorpay/RazorpayOrderCreationService.php';
$content = file_get_contents($file);

$saasLogic = <<<PHP
            if (\$this->config->get('app.saas_mode_enabled')) {
                \$razorpayAccountId = \$orderDTO->account->getActiveRazorpayAccountId();
                if (\$razorpayAccountId === null) {
                    \$this->logger->error(
                        'Razorpay account not found for the event organizer, order creation failed.
                        You will need to connect your Razorpay account to receive payments.',
                        ['orderDTO' => \$orderDTO->toArray(['account'])]
                    );

                    throw new CreateOrderFailedException(
                        __('Razorpay Linked account not found for the event organizer')
                    );
                }

                \$accountConfiguration = \$orderDTO->account->getConfiguration();
                \$bypassApplicationFees = \$accountConfiguration?->getBypassApplicationFees() ?? false;

                \$applicationFee = \$this->orderApplicationFeeCalculationService->calculateApplicationFee(
                    accountConfiguration: \$accountConfiguration,
                    order: \$orderDTO->order,
                    vatSettings: \$orderDTO->account->getAccountVatSetting(),
                );

                if (\$applicationFee && !\$bypassApplicationFees) {
                    \$feeMinorUnit = \$applicationFee->grossApplicationFee->toMinorUnit();

                    \$orderData['transfers'] = [
                        [
                            'account' => \$razorpayAccountId,
                            'amount' => \$amountInSmallestUnit - \$feeMinorUnit,
                            'currency' => \$orderDTO->currencyCode,
                            'notes' => [
                                'order_id' => \$orderDTO->order->getId(),
                                'type' => 'vendor_transfer'
                            ],
                            'on_hold' => 0
                        ]
                    ];
                } else {
                    \$orderData['transfers'] = [
                        [
                            'account' => \$razorpayAccountId,
                            'amount' => \$amountInSmallestUnit,
                            'currency' => \$orderDTO->currencyCode,
                            'notes' => [
                                'order_id' => \$orderDTO->order->getId(),
                                'type' => 'vendor_transfer'
                            ],
                            'on_hold' => 0
                        ]
                    ];
                }
            }
PHP;

$content = str_replace(<<<'PHP'
            // Calculate application fee for Razorpay
            // $applicationFee = $this->orderApplicationFeeCalculationService->calculateApplicationFee(
            //     accountConfiguration: $orderDTO->account->getConfiguration(),
            //     order: $orderDTO->order,
            //     vatSettings: $orderDTO->account->getAccountVatSetting(),
            // );
PHP, '', $content);

$content = preg_replace(
    '/\/\/ TODO: Fix for saas mode.*?\/\/ \}/s',
    $saasLogic,
    $content
);

file_put_contents($file, $content);
