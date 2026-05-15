<?php

namespace HiEvents\Http\Requests\Accounts\Razorpay;

use Illuminate\Foundation\Http\FormRequest;

class RazorpaySettlementStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settlement' => ['required', 'array'],
            'settlement.accountNumber' => ['required', 'string', 'max:30'],
            'settlement.ifscCode' => ['required', 'string', 'size:11'],
            'settlement.beneficiaryName' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'settlement.ifscCode.size' => __('The IFSC code must be exactly 11 characters.'),
        ];
    }
}
