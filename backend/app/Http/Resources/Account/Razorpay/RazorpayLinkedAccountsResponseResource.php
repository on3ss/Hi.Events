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
            'razorpay_accounts' => $dto->razorpayAccounts->map(fn($acc) => [
                'id'                    => $acc->id,
                'is_onboarding_complete' => $acc->isSetupComplete,
                'country'               => $acc->country,
            ])->values()->toArray(),
            'account' => [
                'razorpay_platform' => $dto->primaryRazorpayAccountId ? 'razorpay' : null,
            ],
        ];
    }
}