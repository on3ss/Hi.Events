<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\Http\Requests\Accounts\Razorpay\RazorpayBusinessStageRequest;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\UpdateRazorpayBusinessStageHandler;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\UpdateRazorpayBusinessStageDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\RegisteredAddressDTO;
use Illuminate\Http\JsonResponse;

class UpdateRazorpayBusinessStageAction
{
    public function __construct(
        private readonly UpdateRazorpayBusinessStageHandler $handler
    ) {
    }

    public function __invoke(RazorpayBusinessStageRequest $request, int $accountId): JsonResponse
    {
        $dto = new UpdateRazorpayBusinessStageDTO(
            accountId: $accountId,
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            legalBusinessName: $request->validated('legalBusinessName'),
            businessType: $request->validated('businessType'),
            contactName: $request->validated('contactName'),
            registeredAddress: new RegisteredAddressDTO(
                street1: $request->validated('registeredAddress.street1'),
                street2: $request->validated('registeredAddress.street2'),
                city: $request->validated('registeredAddress.city'),
                state: $request->validated('registeredAddress.state'),
                postalCode: $request->validated('registeredAddress.postalCode'),
                country: $request->validated('registeredAddress.country'),
            ),
            pan: $request->validated('pan'),
            gst: $request->validated('gst'),
        );

        $response = $this->handler->handle($dto);

        return response()->json($response->toArray());
    }
}
