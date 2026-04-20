<?php

namespace HiEvents\Services\Infrastructure\Razorpay;

interface RazorpayClientInterface
{
    public function createOrder(array $data): object;

    public function fetchPayment(string $paymentId): object;

    public function refundPayment(array $params, ?string $idempotencyKey = null): object;

    public function createLinkedAccount(array $data): object;

    public function fetchLinkedAccount(string $accountId): object;

    public function createProductConfiguration(string $accountId, array $data): object;

    public function fetchProductConfiguration(string $accountId, string $productId): object;

    public function updateProductConfiguration(string $accountId, string $productId, array $data): object;
}