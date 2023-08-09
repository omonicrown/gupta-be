<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email'],
            'phone_number' => ['string'],
            'account_type' => ['string'],
            'country' => ['string'],
            'business_category' => ['string'],
        ];
    }
}
