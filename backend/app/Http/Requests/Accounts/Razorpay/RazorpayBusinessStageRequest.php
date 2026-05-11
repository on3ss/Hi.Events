<?php

namespace HiEvents\Http\Requests\Accounts\Razorpay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RazorpayBusinessStageRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'pan.size' => __('The PAN must be exactly 10 characters.'),
            'registeredAddress.country.in' => __('Only Indian addresses are supported.'),
        ];
    }
}
