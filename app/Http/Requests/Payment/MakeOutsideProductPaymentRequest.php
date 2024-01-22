<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MakeOutsideProductPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'amount' => 'required',
            'customer_full_name' => 'required',
            'customer_email' => 'required',
            'pay_for' => 'required',
            'store_id' => '',
            'product_qty' => 'string',
            'customer_phone_number' => 'required',
        ];
    }
}
