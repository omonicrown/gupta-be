<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MakePayment extends FormRequest
{
   
    public function rules()
    {
        return [
            'amount' => 'required',
        ];
    }
}
