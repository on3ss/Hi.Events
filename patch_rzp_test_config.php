<?php
$file = 'backend/tests/Unit/Services/Domain/Payment/Razorpay/RazorpayOrderCreationServiceTest.php';
$content = file_get_contents($file);

$saasLogic = <<<PHP
        \$this->configMock->method('get')
            ->willReturnMap([
                ['services.razorpay.key_id', null, 'test_key_id'],
                ['app.saas_mode_enabled', null, false]
            ]);
PHP;

$content = preg_replace(
    '/\$this->configMock->method\(\'get\'\).*?->willReturn\(\'test_key_id\'\);/s',
    $saasLogic,
    $content
);

file_put_contents($file, $content);
