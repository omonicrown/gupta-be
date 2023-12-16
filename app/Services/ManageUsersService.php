<?php

namespace App\Services;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Link;
use App\Models\MarketPlaceLink;
use Faker\Core\File;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ManageUsersService extends BaseController
{
    public function getAllUsers($request)
    {
        try {
            $type = $request->query('search');
            if ($type !== '') {
                $getData = User::query()
                    ->where('name', 'LIKE', "%{$type}%")
                    ->orWhere('email', 'LIKE', "%{$type}%")
                    ->orWhere('sub_status', 'LIKE', "%{$type}%")
                    ->paginate(20);
            } else {
                $getData = User::paginate(20);
            }

            return $this->sendResponse($getData, 'Fetched Successfully');
        } catch (\Throwable $th) {
            return $this->sendError('Something went wrong');
        }
    }


    public function updateUserStatus($data, $id)
    {
        $updateUser = User::find($id);
        $updateUser->sub_status = $data['sub_status'];
        $updateUser->save();
        return $this->sendResponse($updateUser, 'Updated successful');
    }

    public function updateUserRole($data, $id)
    {
        $updateUser = User::find($id);
        $updateUser->role = $data['role'];
        $updateUser->save();
        return $this->sendResponse($updateUser, 'Updated successful');
    }

    public function getUserDetails($id)
    {
        return $this->sendResponse(User::where('id', $id)->first(), 'Fetched successful');
    }

    public function getUserWhatsappLinks($id)
    {
        return $this->sendResponse(Link::where('user_id', $id)->whereIn('type', ['message', 'catalog'])->paginate(10), 'Fetched successful');
    }

    public function getUserUrlLinks($id)
    {
        return $this->sendResponse(Link::where('user_id', $id)->whereIn('type', ['url'])->paginate(10), 'Fetched successful');
    }

    public function getUserMultiLinks($id)
    {
        return $this->sendResponse(Link::where('user_id', $id)->whereIn('type', ['tiered'])->paginate(10), 'Fetched successful');
    }

    public function getUserMarketLinks($id)
    {
        return $this->sendResponse(MarketPlaceLink::where('user_id', $id)->paginate(10), 'Fetched successful');
    }

    // public function deletePartnerCourse($id)
    // {
    //     DB::beginTransaction();
    //     return $this->sendResponse(PartnerCourses::where('id', $id)->delete(), 'Deleted successfully');
    // }

    //Create partner school updatePartnerSchool


}
