<?php

namespace App\Http\Requests\MarketPlace;

use Illuminate\Foundation\Http\FormRequest;

class MarketLinkRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'link_name' =>['required', 'string'],
            // 'image' =>'required|image|mimes:jpeg,png,jpg,gif,svg'
        ];
    }
}
