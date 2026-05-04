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
        return $this->api->account->create($data);
    }

    public function listLinkedAccounts(array $params = []): array
    {
        $response = $this->api->account->all($params);
        return $response->items ?? [];
    }

    public function fetchLinkedAccount(string $accountId): object
    {
        return $this->api->account->fetch($accountId);
    }

    public function updateLinkedAccount(string $accountId, array $data): object
    {
        $account = $this->fetchLinkedAccount($accountId);
        return $account->edit($data);
    }

    public function createStakeholder(string $accountId, array $data): object
    {
        return $this->api->account->fetch($accountId)->stakeholders()->create($data);
    }

    public function updateStakeholder(string $accountId, string $stakeholderId, array $data): object
    {
        $stakeholder = $this->api->account->fetch($accountId)->stakeholders()->fetch($stakeholderId);
        return $stakeholder->edit($data);
    }

    public function requestProductConfiguration(string $accountId, array $data): object
    {
        $account = $this->api->account->fetch($accountId);
        return $account->requestProductConfiguration($data);
    }

    public function fetchProductConfiguration(string $accountId, string $productId): object
    {
        $account = $this->api->account->fetch($accountId);
        return $account->products()->fetch($productId);
    }

    public function updateProductConfiguration(string $accountId, string $productId, array $data): object
    {
        $account = $this->api->account->fetch($accountId);
        return $account->products()->edit($productId, $data);
    }
}