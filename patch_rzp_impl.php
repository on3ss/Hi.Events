<?php
$file = 'backend/app/Services/Infrastructure/Razorpay/RazorpayApiClient.php';
$content = file_get_contents($file);
$methods = <<<METHODS
    public function createLinkedAccount(array \$data): object
    {
        return \$this->api->request->request('POST', 'v2/accounts', \$data);
    }

    public function fetchLinkedAccount(string \$accountId): object
    {
        return \$this->api->request->request('GET', 'v2/accounts/' . \$accountId);
    }

    public function createProductConfiguration(string \$accountId, array \$data): object
    {
        return \$this->api->request->request('POST', 'v2/accounts/' . \$accountId . '/products', \$data);
    }

    public function fetchProductConfiguration(string \$accountId, string \$productId): object
    {
        return \$this->api->request->request('GET', 'v2/accounts/' . \$accountId . '/products/' . \$productId);
    }

    public function updateProductConfiguration(string \$accountId, string \$productId, array \$data): object
    {
        return \$this->api->request->request('PATCH', 'v2/accounts/' . \$accountId . '/products/' . \$productId, \$data);
    }
METHODS;
$content = str_replace('        return $payment->refund($params, $idempotencyKey);
    }', "        return \$payment->refund(\$params, \$idempotencyKey);\n    }\n\n" . $methods, $content);
file_put_contents($file, $content);
