<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Account\UserResource;
use App\Services\Account\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetProfileController extends Controller
{
    public function __invoke(Request $request, AccountService $accountService): JsonResponse
    {
        return $this->success(
            __('User profile retrieved.'),
            new UserResource($accountService->getProfile($request->user()->id))
        );
    }
}
