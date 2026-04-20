<?php
$file = 'backend/app/Services/Infrastructure/Razorpay/RazorpayClientInterface.php';
$content = file_get_contents($file);
$methods = <<<METHODS
    public function createLinkedAccount(array \$data): object;

    public function fetchLinkedAccount(string \$accountId): object;

    public function createProductConfiguration(string \$accountId, array \$data): object;

    public function fetchProductConfiguration(string \$accountId, string \$productId): object;

    public function updateProductConfiguration(string \$accountId, string \$productId, array \$data): object;
METHODS;
$content = str_replace('    public function refundPayment(array $params, ?string $idempotencyKey = null): object;', "    public function refundPayment(array \$params, ?string \$idempotencyKey = null): object;\n\n" . $methods, $content);
file_put_contents($file, $content);
