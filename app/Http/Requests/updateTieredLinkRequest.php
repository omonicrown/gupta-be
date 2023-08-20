<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createTieredLinkRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' =>['required', 'string'],
            'title' =>  ['required', 'string'],
            'id' =>  '',
            'bio' => ['required', 'string'],
            'attach_links' => ['required','string'],
            'business_website' => ['required', 'string'],
            'business_policy' => ['required', 'string'],
            'redirect_link' => ['required', 'string'],
        ];
    }
}
