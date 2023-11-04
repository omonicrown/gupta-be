<?php

namespace App\Http\Requests\MarketPlace;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'link_id' =>['required', 'string'],
            'product_name' =>['required', 'string'],
            'product_description' =>['required', 'string'],
            'phone_number' =>['required', 'string'],
            'no_of_items' =>['required', 'string'],
            'product_price' =>['required', 'string'],
            'product_image_id_1' =>'string',
            'product_image_id_2' =>'string',
            'product_image_id_3' =>'string',
            'id' =>'',
            // 'cover_photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'product_image_1' =>'image|mimes:jpeg,png,jpg,gif,svg',
            'product_image_2' =>'image|mimes:jpeg,png,jpg,gif,svg',
            'product_image_3' =>'image|mimes:jpeg,png,jpg,gif,svg',
        ];
    }
}
