<?php

namespace HiEvents\Resources\Account\Razorpay;

use HiEvents\Resources\BaseResource;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\GetRazorpayLinkedAccountsResponseDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\RazorpayLinkedAccountDTO;
use Illuminate\Http\Request;

/**
 * @mixin GetRazorpayLinkedAccountsResponseDTO
 */
class RazorpayLinkedAccountsResponseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'account' => [
                'id' => $this->account->getId(),

            ],
            'razorpay_linked_accounts' => $this->razorpayLinkedAccounts->map(function (RazorpayLinkedAccountDTO $account) {
                return [
                    'razorpay_account_id' => $account->razorpayAccountId,
                    'connect_url' => $account->connectUrl,
                    'is_setup_complete' => $account->isSetupComplete,

                    'account_type' => $account->accountType,
                    'is_primary' => $account->isPrimary,
                    'country' => $account->country,
                ];
            })->toArray(),
            'primary_razorpay_account_id' => $this->primaryRazorpayAccountId,
            'has_completed_setup' => $this->hasCompletedSetup,
        ];
    }
}
