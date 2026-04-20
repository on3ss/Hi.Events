<?php

namespace HiEvents\Resources\Account\Razorpay;

use HiEvents\Resources\Account\AccountResource;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\CreateRazorpayLinkedAccountResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CreateRazorpayLinkedAccountResponse
 */
class RazorpayLinkedAccountResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'razorpay_linked_account_type' => $this->razorpayLinkedAccountType,
            'razorpay_account_id' => $this->razorpayAccountId,
            'is_connect_setup_complete' => $this->isConnectSetupComplete,
            'connect_url' => $this->connectUrl,
            'account' => new AccountResource($this->account),
        ];
    }
}
