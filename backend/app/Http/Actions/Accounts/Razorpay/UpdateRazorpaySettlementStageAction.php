<?php

namespace HiEvents\Http\Actions\Accounts\Razorpay;

use HiEvents\Http\Requests\Accounts\Razorpay\RazorpaySettlementStageRequest;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\UpdateRazorpaySettlementStageHandler;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\UpdateRazorpaySettlementStageDTO;
use HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO\SettlementDTO;
use Illuminate\Http\JsonResponse;

class UpdateRazorpaySettlementStageAction
{
    public function __construct(
        private readonly UpdateRazorpaySettlementStageHandler $handler
    ) {
    }

    public function __invoke(RazorpaySettlementStageRequest $request, int $accountId): JsonResponse
    {
        $dto = new UpdateRazorpaySettlementStageDTO(
            accountId: $accountId,
            settlement: new SettlementDTO(
                accountNumber: $request->validated('settlement.accountNumber'),
                ifscCode: $request->validated('settlement.ifscCode'),
                beneficiaryName: $request->validated('settlement.beneficiaryName'),
            )
        );

        $response = $this->handler->handle($dto);

        return response()->json($response->toArray());
    }
}
