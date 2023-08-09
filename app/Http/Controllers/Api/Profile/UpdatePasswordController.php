<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Services\Account\AccountService;
use Exception;
use Illuminate\Http\JsonResponse;

class UpdatePasswordController extends Controller
{
    public function __invoke(UpdatePasswordRequest $request, AccountService $accountService): JsonResponse
    {
        try {
            $accountService->updatePassword($request->user()->id, $request->validated());

            return $this->success(__('Password updated.'));
        } catch (Exception $exception) {
            return $this->exception($exception);
        }
    }
}
