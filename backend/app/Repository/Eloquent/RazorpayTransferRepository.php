<?php

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\RazorpayTransferDomainObject;
use HiEvents\Models\RazorpayTransfer;
use HiEvents\Repository\Interfaces\RazorpayTransferRepositoryInterface;

class RazorpayTransferRepository extends BaseRepository implements RazorpayTransferRepositoryInterface
{
    protected function getModel(): string
    {
        return RazorpayTransfer::class;
    }

    public function getDomainObject(): string
    {
        return RazorpayTransferDomainObject::class;
    }

    public function findByRazorpayTransferId(string $transferId): ?RazorpayTransferDomainObject
    {
        return $this->findFirstWhere([
            'razorpay_transfer_id' => $transferId,
        ]);
    }

    public function findByRazorpayPaymentId(string $paymentId): ?RazorpayTransferDomainObject
    {
        return $this->findFirstWhere([
            'razorpay_payment_id' => $paymentId,
        ]);
    }

    public function findByRazorpayOrderId(string $orderId): ?RazorpayTransferDomainObject
    {
        return $this->findFirstWhere([
            'razorpay_order_id' => $orderId,
        ]);
    }

    public function updateByRazorpayTransferId(string $transferId, array $attributes): void
    {
        $this->updateWhere(
            attributes: $attributes,
            where: [
                'razorpay_transfer_id' => $transferId,
            ],
        );
    }
}