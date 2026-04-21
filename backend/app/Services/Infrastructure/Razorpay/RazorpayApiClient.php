<?php

namespace HiEvents\Services\Infrastructure\Razorpay;

use Razorpay\Api\Api;

class RazorpayApiClient implements RazorpayClientInterface
{
    private Api $api;

    public function __construct(string $keyId, string $keySecret, ?Api $api = null)
    {
        $this->api = $api ?? new Api($keyId, $keySecret);
    }

    public function createOrder(array $data): object
    {
        return $this->api->order->create($data);
    }

    public function fetchPayment(string $paymentId): object
    {
        return $this->api->payment->fetch($paymentId);
    }

    public function refundPayment(array $params, string|null $idempotencyKey = null): object
    {
        $paymentId = $params['payment_id'];
        unset($params['payment_id']);
        $payment = $this->api->payment->fetch($paymentId);
        return $payment->refund($params, $idempotencyKey);
    }

    public function createLinkedAccount(array $data): object
    {
        return $this->api->request->request('POST', 'v2/accounts', $data);
    }

    public function fetchLinkedAccount(string $accountId): object
    {
        return $this->api->request->request('GET', 'v2/accounts/' . $accountId);
    }

    public function createStakeholder(string $accountId, array $data): object
    {
        return $this->api->request->request('POST', 'v2/accounts/' . $accountId . '/stakeholders', $data);
    }

    public function fetchStakeholder(string $accountId, string $stakeholderId): object
    {
        return $this->api->request->request('GET', 'v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId);
    }

    public function updateStakeholder(string $accountId, string $stakeholderId, array $data): object
    {
        return $this->api->request->request('PATCH', 'v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId, $data);
    }

    public function createProductConfiguration(string $accountId, array $data): object
    {
        return $this->api->request->request('POST', 'v2/accounts/' . $accountId . '/products', $data);
    }

    public function fetchProductConfiguration(string $accountId, string $productId): object
    {
        return $this->api->request->request('GET', 'v2/accounts/' . $accountId . '/products/' . $productId);
    }

    public function updateProductConfiguration(string $accountId, string $productId, array $data): object
    {
        return $this->api->request->request('PATCH', 'v2/accounts/' . $accountId . '/products/' . $productId, $data);
    }
}