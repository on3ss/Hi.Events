<?php
$file = 'backend/tests/Unit/Services/Domain/Payment/Razorpay/RazorpayOrderCreationServiceTest.php';
$content = file_get_contents($file);

$saasLogic = <<<PHP
    private function createMockedRequestDTO(
        string \$currencyCode = 'INR',
        int \$minorUnit = 50000,
        float \$floatAmount = 500.00
    ): CreateRazorpayOrderRequestDTO {
        \$amountMock = \$this->createMock(MoneyValue::class);
        \$amountMock->method('toMinorUnit')->willReturn(\$minorUnit);
        \$amountMock->method('toFloat')->willReturn(\$floatAmount);

        \$orderMock = \$this->createMock(OrderDomainObject::class);
        \$orderMock->method('getShortId')->willReturn('SHORT_123');
        \$orderMock->method('getId')->willReturn(1);
        \$orderMock->method('getEventId')->willReturn(99);

        \$accountMock = \$this->createMock(AccountDomainObject::class);
        \$accountMock->method('getId')->willReturn(5);
        \$accountMock->method('getActiveRazorpayAccountId')->willReturn('acc_123');

        return new CreateRazorpayOrderRequestDTO(
            amount: \$amountMock,
            currencyCode: \$currencyCode,
            account: \$accountMock,
            order: \$orderMock
        );
    }
PHP;

$content = preg_replace(
    '/private function createMockedRequestDTO\(.*?\}\n/s',
    $saasLogic,
    $content
);

file_put_contents($file, $content);
