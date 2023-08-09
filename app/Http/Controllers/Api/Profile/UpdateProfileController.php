<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\Account\AccountService;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, AccountService $accountService): JsonResponse
    {
        $accountService->updateProfile($request->user()->id, $request->validated());

        return $this->success(__('Profile updated.'));
    }
}
