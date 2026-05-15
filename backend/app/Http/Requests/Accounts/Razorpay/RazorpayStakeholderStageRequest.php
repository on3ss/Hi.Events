<?php

namespace HiEvents\Http\Requests\Accounts\Razorpay;

use Illuminate\Foundation\Http\FormRequest;

class RazorpayStakeholderStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stakeholder' => ['required', 'array'],
            'stakeholder.name' => ['required', 'string', 'max:255'],
            'stakeholder.email' => ['required', 'email'],
            'stakeholder.pan' => ['required', 'string', 'size:10'],
            'stakeholder.residentialAddress' => ['required', 'array'],
            'stakeholder.residentialAddress.street' => ['required', 'string', 'max:255'],
            'stakeholder.residentialAddress.city' => ['required', 'string', 'max:255'],
            'stakeholder.residentialAddress.state' => ['required', 'string', 'max:255'],
            'stakeholder.residentialAddress.postalCode' => ['required', 'string', 'max:10'],
            'stakeholder.residentialAddress.country' => ['required', 'string', 'size:2', 'in:IN'],
        ];
    }

    public function messages(): array
    {
        return [
            'stakeholder.pan.size' => __('The stakeholder PAN must be exactly 10 characters.'),
            'stakeholder.residentialAddress.country.in' => __('Only Indian addresses are supported.'),
        ];
    }
}
