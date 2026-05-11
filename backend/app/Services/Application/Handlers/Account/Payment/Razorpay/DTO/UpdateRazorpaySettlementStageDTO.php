<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class UpdateRazorpaySettlementStageDTO extends BaseDTO
{
    public function __construct(
        public readonly int $accountId,
        public readonly SettlementDTO $settlement,
    ) {
    }
}
