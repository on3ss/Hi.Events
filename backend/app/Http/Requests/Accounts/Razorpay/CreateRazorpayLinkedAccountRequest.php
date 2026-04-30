<?php

namespace HiEvents\Http\Requests\Accounts\Razorpay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRazorpayLinkedAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'min:10'],
            'legalBusinessName' => ['required', 'string', 'max:255'],
            'businessType' => ['required', Rule::in(['partnership', 'proprietorship', 'private_limited', 'public_limited'])],
            'contactName' => ['required', 'string', 'max:255'],
            'registeredAddress' => ['required', 'array'],
            'registeredAddress.street1' => ['required', 'string', 'max:255'],
            'registeredAddress.street2' => ['nullable', 'string', 'max:255'],
            'registeredAddress.city' => ['required', 'string', 'max:255'],
            'registeredAddress.state' => ['required', 'string', 'max:255'],
            'registeredAddress.postalCode' => ['required', 'string', 'max:10'],
            'registeredAddress.country' => ['required', 'string', 'size:2', 'in:IN'],
            'pan' => ['required', 'string', 'size:10'],
            'gst' => ['nullable', 'string', 'max:15'],
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
            'settlement' => ['required', 'array'],
            'settlement.accountNumber' => ['required', 'string', 'max:30'],
            'settlement.ifscCode' => ['required', 'string', 'size:11'],
            'settlement.beneficiaryName' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'pan.size' => __('The PAN must be exactly 10 characters.'),
            'stakeholder.pan.size' => __('The stakeholder PAN must be exactly 10 characters.'),
            'settlement.ifscCode.size' => __('The IFSC code must be exactly 11 characters.'),
            'registeredAddress.country.in' => __('Only Indian addresses are supported.'),
        ];
    }
}