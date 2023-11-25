<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ManageUsersService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManageUsersController extends Controller
{ 
    public function getAllUsers(Request $request,ManageUsersService $userService): JsonResponse
    {
        try {
            return 
                $userService->getAllUsers($request);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    

    public function getUserDetails(ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->getUserDetails($id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    } 

    public function getUserWhatsappLinks(ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->getUserWhatsappLinks($id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    } 

    public function getUserUrlLinks(ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->getUserUrlLinks($id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    } 
    

    public function updateUserStatus(Request $request,ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->updateUserStatus($request,$id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function updateUserRole(Request $request,ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->updateUserRole($request,$id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function getUserMultiLinks(ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->getUserMultiLinks($id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }

    public function getUserMarketLinks(ManageUsersService $userService,$id): JsonResponse
    {
        try {
            return 
                $userService->getUserMarketLinks($id);
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }
}
