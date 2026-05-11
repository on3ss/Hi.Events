<?php

namespace HiEvents\Services\Application\Handlers\Account\Payment\Razorpay\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class UpdateRazorpayStakeholderStageDTO extends BaseDTO
{
    public function __construct(
        public readonly int $accountId,
        public readonly StakeholderDTO $stakeholder,
    ) {
    }
}
