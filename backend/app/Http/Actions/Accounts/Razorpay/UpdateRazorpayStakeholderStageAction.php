<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\Http\Requests\Accounts\Razorpay\RazorpayStakeholderStageRequest;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\UpdateRazorpayStakeholderStageHandler;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\UpdateRazorpayStakeholderStageDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\StakeholderDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\ResidentialAddressDTO;
use Illuminate\Http\JsonResponse;

class UpdateRazorpayStakeholderStageAction
{
    public function __construct(
        private readonly UpdateRazorpayStakeholderStageHandler $handler
    ) {
    }

    public function __invoke(RazorpayStakeholderStageRequest $request, int $accountId): JsonResponse
    {
        $dto = new UpdateRazorpayStakeholderStageDTO(
            accountId: $accountId,
            stakeholder: new StakeholderDTO(
                name: $request->validated('stakeholder.name'),
                email: $request->validated('stakeholder.email'),
                residentialAddress: new ResidentialAddressDTO(
                    street: $request->validated('stakeholder.residentialAddress.street'),
                    city: $request->validated('stakeholder.residentialAddress.city'),
                    state: $request->validated('stakeholder.residentialAddress.state'),
                    postalCode: $request->validated('stakeholder.residentialAddress.postalCode'),
                    country: $request->validated('stakeholder.residentialAddress.country'),
                ),
                pan: $request->validated('stakeholder.pan'),
            )
        );

        $response = $this->handler->handle($dto);

        return response()->json($response->toArray());
    }
}
