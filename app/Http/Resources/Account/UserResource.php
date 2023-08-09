<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => Str::ucfirst($this->name),
            'email' => $this->email,
            'email_verified_at' => (bool) $this->email_verified_at,
            'created_at' => $this->created_at,
        ];
    }
}
