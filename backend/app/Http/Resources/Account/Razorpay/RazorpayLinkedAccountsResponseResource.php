<?php

namespace HiEvents\Http\Resources\Account\Razorpay;

use HiEvents\Resources\BaseResource;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\GetRazorpayLinkedAccountsResponseDTO;

class RazorpayLinkedAccountsResponseResource extends BaseResource
{
    public function toArray($request): array
    {
        /** @var GetRazorpayLinkedAccountsResponseDTO $dto */
        $dto = $this->resource;

        return [
            'razorpay_accounts' => $dto->razorpayAccounts
                ->map(fn($acc) => [
                    'id' => $acc->id,
                    'status' => $acc->status,
                    'razorpay_account_id' => $acc->razorpayAccountId,
                    'email' => $acc->email,
                    'legal_business_name' => $acc->legalBusinessName,
                    'country' => $acc->country,
                ])
                ->values()
                ->toArray(),
                
            'account' => [
                'razorpay_platform' => $dto->razorpayAccounts->isNotEmpty()
                    ? 'razorpay'
                    : null,
            ],
        ];
    }
}