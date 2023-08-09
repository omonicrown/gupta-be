<?php

namespace App\Services\Account;

use Exception;
use App\Models\User;
use App\Exceptions\HttpException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountService
{

    /**
     * @param  string  $userId
     * @return mixed
     */
    public function getProfile(string $userId)
    {
        return static::findUser($userId)->first();
    }

    /**
     * @param  string  $userId
     * @param  array  $values
     * @return bool
     */
    public function updateProfile(string $userId, array $values): bool
    {
         
       
        $updateUser = User::find($userId);
        $updateUser->name = $values['name'];
        $updateUser->email = $values['email'];
        $updateUser->phone_number = $values['phone_number'];
        $updateUser->account_type = $values['account_type'];
        $updateUser->country = $values['country'];
        $updateUser->business_category = $values['business_category'];
        $updateUser->save();
        return true;
    }

    /**
     * @throws HttpException
     */
    public function updatePassword(string $userId, array $values): bool
    {
        $user = static::findUser($userId)->first();

        if (!password_verify($values['old_password'], $user->password)) {
            throw new HttpException('Old password was entered incorrectly.');
        }

        return $user->update([
            'password' => Hash::make($values['password']),
        ]);
    }

    /**
     * @param  int|string  $userId
     * @return mixed
     */
    public static function findUser($userId)
    {
        return User::query()->whereId($userId);
    }
}
