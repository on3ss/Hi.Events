<?php

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\RazorpayTransferDomainObject;

interface RazorpayTransferRepositoryInterface extends RepositoryInterface
{
    public function findByRazorpayTransferId(string $transferId): ?RazorpayTransferDomainObject;

    public function findByRazorpayPaymentId(string $paymentId): ?RazorpayTransferDomainObject;

    public function findByRazorpayOrderId(string $orderId): ?RazorpayTransferDomainObject;

    public function updateByRazorpayTransferId(string $transferId, array $attributes): void;
}