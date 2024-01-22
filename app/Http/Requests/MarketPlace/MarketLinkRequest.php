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
            'brand_primary_color' =>['string'],
            'brand_description' =>[''],
            'facebook_url' =>[''],
            'instagram_url' =>[''],
            'tiktok_url' =>[''],
            'brand_logo' =>['']


            
            // 'image' =>'required|image|mimes:jpeg,png,jpg,gif,svg'
        ];
    }
}